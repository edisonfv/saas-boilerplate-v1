<?php

use App\Enums\SignaturePaymentReview;
use App\Enums\SignaturePaymentStatus;
use App\Models\SignatureInvitation;
use App\Models\SignaturePayment;
use App\Models\SignatureRequest;
use App\Models\SignatureStorefront;
use App\Models\Tenant;
use App\Notifications\Signatures\InvitationSent;
use App\Notifications\Signatures\PaymentLinkSent;
use App\Notifications\Signatures\PaymentReviewed;
use App\Services\Signatures\SignatureWallet;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    seedSignatures();
    Notification::fake();
    Http::fake(['uanataca.test/*' => Http::response(['result' => true, 'token' => 'TKN-PAID'])]);
});

afterEach(function () {
    tenancy()->end();
});

/**
 * Relative signed path of a customer link (as SignatureLinks builds it).
 */
function signedPath(Tenant $tenant, string $route, array $parameters, $expiresAt = null): string
{
    return $tenant->run(fn () => URL::temporarySignedRoute($route, $expiresAt ?? now()->addDays(7), $parameters, absolute: false));
}

function applyOnStorefront(string $domain): void
{
    test()->post("http://{$domain}/solicitud", [
        ...signatureApplicant(),
        'accepts_terms' => '1',
        'documents' => signatureDocuments(),
    ])->assertRedirect(route('tenant.signatures.storefront.received'));
}

function receiptUpload(): array
{
    return [
        'method' => 'BankTransfer',
        'reference' => 'TRX-000123',
        'receipt' => UploadedFile::fake()->image('comprobante.jpg'),
    ];
}

test('an online application waits for payment and emails the payment link', function () {
    [$tenant, $domain] = signatureTenant();

    $this->post("http://{$domain}/solicitud", [...signatureApplicant(), 'accepts_terms' => '1', 'documents' => signatureDocuments()]);

    $request = $tenant->run(fn () => SignatureRequest::sole());

    expect($request->payment_status->equals(SignaturePaymentStatus::Pending()))->toBeTrue();

    Notification::assertSentOnDemand(PaymentLinkSent::class, fn (PaymentLinkSent $notification, array $channels, object $notifiable) => $notifiable->routes['mail'] === 'maria@example.test'
        && $notification->code === 'FE-000001'
        && str_contains($notification->link, '/solicitud/'.$request->id.'/pago?expires='));

    $this->get("http://{$domain}/solicitud/enviada")
        ->assertInertia(fn (Assert $page) => $page
            ->component('Signatures/Storefront/Received')
            ->where('isPaid', false)
            ->where('paymentUrl', fn (string $url) => str_contains($url, '/pago?expires=')));
});

test('the payment page needs a valid signed link and shows the bank accounts', function () {
    [$tenant, $domain] = signatureTenant();
    $tenant->run(fn () => SignatureStorefront::current()->update(['bank_accounts' => [
        ['bank' => 'Banco Pichincha', 'account_type' => 'Ahorros', 'number' => '2200112233', 'holder' => 'Acme S.A.', 'holder_id' => '1790011223001'],
    ]]));
    $request = signatureDraft($tenant, paid: false);

    $this->get("http://{$domain}/solicitud/{$request->id}/pago")->assertForbidden();

    $this->get('http://'.$domain.signedPath($tenant, 'tenant.signatures.storefront.payment.show', ['signatureRequest' => $request->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Signatures/Storefront/Payment')
            ->where('request.code', 'FE-000001')
            ->where('request.payment_status', 'Pending')
            ->where('bankAccounts.0.number', '2200112233'));

    $this->get('http://'.$domain.signedPath($tenant, 'tenant.signatures.storefront.payment.show', ['signatureRequest' => $request->id], now()->subMinute()))
        ->assertForbidden();
});

test('the customer uploads a receipt once, staff confirms it and only then it can be sent', function () {
    [$tenant, $domain] = signatureTenant('Credit', creditLimit: 100);
    $owner = supportTenantUser($tenant);
    $request = signatureDraft($tenant, paid: false);
    $path = signedPath($tenant, 'tenant.signatures.storefront.payment.store', ['signatureRequest' => $request->id]);

    // Not paid: the operator can't send it nor spend quota.
    $this->actingAs($owner)
        ->post("http://{$domain}/firmas-electronicas/solicitudes/{$request->id}/enviar")
        ->assertSessionHasErrors(['submit' => 'La solicitud aún no está pagada. Confirma el pago del cliente antes de enviarla.']);
    Http::assertNotSent(fn (Request $sent) => str_contains($sent->url(), 'uanataca'));

    auth()->guard('web')->logout();

    $this->post("http://{$domain}{$path}", receiptUpload())->assertSessionHasNoErrors();
    $this->post("http://{$domain}{$path}", receiptUpload())->assertSessionHasErrors('receipt');

    $payment = $tenant->run(fn () => SignaturePayment::sole());
    expect($tenant->run(fn () => $request->fresh()->payment_status->equals(SignaturePaymentStatus::UnderReview())))->toBeTrue()
        ->and($payment->review->equals(SignaturePaymentReview::Pending()))->toBeTrue()
        ->and($payment->amount)->toBe('30.00');

    $this->actingAs($owner)
        ->get("http://{$domain}/firmas-electronicas/solicitudes/{$request->id}/pagos/{$payment->id}/comprobante")
        ->assertOk();

    $this->actingAs($owner)
        ->post("http://{$domain}/firmas-electronicas/solicitudes/{$request->id}/pagos/{$payment->id}/confirmar")
        ->assertSessionHas('status', 'signature-payment-approved');

    Notification::assertSentOnDemand(PaymentReviewed::class, fn (PaymentReviewed $notification) => $notification->approved);

    $this->actingAs($owner)
        ->post("http://{$domain}/firmas-electronicas/solicitudes/{$request->id}/enviar")
        ->assertSessionHas('status', 'signature-request-submitted');

    expect($tenant->signatureAccount->fresh()->credit_used)->toBe('15.00');
});

test('a rejected receipt reopens the payment with the reason', function () {
    [$tenant, $domain] = signatureTenant();
    $owner = supportTenantUser($tenant);
    $request = signatureDraft($tenant, paid: false);
    $path = signedPath($tenant, 'tenant.signatures.storefront.payment.store', ['signatureRequest' => $request->id]);

    $this->post("http://{$domain}{$path}", receiptUpload());
    $payment = $tenant->run(fn () => SignaturePayment::sole());

    $this->actingAs($owner)
        ->post("http://{$domain}/firmas-electronicas/solicitudes/{$request->id}/pagos/{$payment->id}/rechazar", ['reason' => 'El monto no coincide'])
        ->assertSessionHas('status', 'signature-payment-rejected');

    expect($tenant->run(fn () => $request->fresh()->payment_status->equals(SignaturePaymentStatus::Pending())))->toBeTrue();
    Notification::assertSentOnDemand(PaymentReviewed::class, fn (PaymentReviewed $notification) => ! $notification->approved
        && $notification->reason === 'El monto no coincide'
        && $notification->link !== null);

    // Reviewing twice is refused.
    $this->actingAs($owner)
        ->post("http://{$domain}/firmas-electronicas/solicitudes/{$request->id}/pagos/{$payment->id}/confirmar")
        ->assertSessionHasErrors('payment');

    auth()->guard('web')->logout();

    $this->get('http://'.$domain.signedPath($tenant, 'tenant.signatures.storefront.payment.show', ['signatureRequest' => $request->id]))
        ->assertInertia(fn (Assert $page) => $page->where('request.rejection_reason', 'El monto no coincide'));

    $this->post("http://{$domain}{$path}", receiptUpload())->assertSessionHasNoErrors();
});

test('staff registers a payment at the point of sale', function () {
    [$tenant, $domain] = signatureTenant();
    $request = signatureDraft($tenant, paid: false);

    $this->actingAs(supportTenantUser($tenant))
        ->post("http://{$domain}/firmas-electronicas/solicitudes/{$request->id}/pagos", [
            'method' => 'Cash',
            'amount' => '30',
        ])
        ->assertSessionHas('status', 'signature-payment-registered');

    $payment = $tenant->run(fn () => SignaturePayment::sole());

    expect($tenant->run(fn () => $request->fresh()->isPaid()))->toBeTrue()
        ->and($payment->review->equals(SignaturePaymentReview::Approved()))->toBeTrue();
});

test('only users with the payments permission handle payments', function () {
    [$tenant, $domain] = signatureTenant();
    $request = signatureDraft($tenant, paid: false);
    $clerk = supportTenantUser($tenant, role: null, permissions: ['tenant.signature-requests.view', 'tenant.signature-requests.submit']);

    $this->actingAs($clerk)
        ->post("http://{$domain}/firmas-electronicas/solicitudes/{$request->id}/pagos", ['method' => 'Cash', 'amount' => '30'])
        ->assertForbidden();
});

test('a prepaid link creates an already paid application, only once', function () {
    [$tenant, $domain] = signatureTenant('Credit', creditLimit: 100);
    $owner = supportTenantUser($tenant);
    $product = signatureProduct('TwoYears');

    $this->actingAs($owner)
        ->post("http://{$domain}/firmas-electronicas/enlaces-prepagados", [
            'signature_product_id' => $product->id,
            'customer_name' => 'María Pérez',
            'customer_email' => 'maria@example.test',
            'customer_phone' => '0991234567',
            'amount' => '38.50',
            'method' => 'BankTransfer',
            'reference' => 'TRX-555',
        ])
        ->assertSessionHas('status', 'signature-invitation-created');

    $invitation = $tenant->run(fn () => SignatureInvitation::sole());
    Notification::assertSentOnDemand(InvitationSent::class, fn (InvitationSent $notification) => str_contains($notification->link, "/solicitud/invitacion/{$invitation->id}"));

    auth()->guard('web')->logout();
    $path = signedPath($tenant, 'tenant.signatures.storefront.invitation.store', ['invitation' => $invitation->id], $invitation->expires_at);

    $this->get("http://{$domain}{$path}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Signatures/Storefront/Apply')
            ->where('invitation.is_usable', true)
            ->has('products', 1)
            ->where('products.0.id', $product->id));

    $application = [
        // The product in the form is ignored: the invitation decides it.
        ...signatureApplicant(['signature_product_id' => signatureProduct()->id]),
        'accepts_terms' => '1',
        'documents' => signatureDocuments(),
    ];

    $this->post("http://{$domain}{$path}", $application)->assertRedirect(route('tenant.signatures.storefront.received'));
    $this->post("http://{$domain}{$path}", $application)->assertSessionHasErrors('invitation');

    $request = $tenant->run(fn () => SignatureRequest::sole());

    expect($request->isPaid())->toBeTrue()
        ->and($request->signature_product_id)->toBe($product->id)
        ->and($request->sale_price)->toBe('38.50')
        ->and($tenant->run(fn () => SignatureInvitation::sole()->consumed_at))->not->toBeNull()
        ->and($tenant->run(fn () => SignaturePayment::sole()->signature_request_id))->toBe($request->id);

    Notification::assertNotSentTo(Notification::route('mail', 'maria@example.test'), PaymentLinkSent::class);

    // Paid, so the operator can send it right away.
    $this->actingAs($owner)
        ->post("http://{$domain}/firmas-electronicas/solicitudes/{$request->id}/enviar")
        ->assertSessionHas('status', 'signature-request-submitted');
});

test('an expired prepaid link is refused', function () {
    [$tenant, $domain] = signatureTenant();
    $invitation = $tenant->run(fn () => SignatureInvitation::create([
        'signature_product_id' => signatureProduct()->id,
        'product_name' => 'Firma 1 año',
        'customer_name' => 'María',
        'amount' => 30,
        'expires_at' => now()->subDay(),
    ]));

    $this->get("http://{$domain}".signedPath($tenant, 'tenant.signatures.storefront.invitation.show', ['invitation' => $invitation->id], now()->subDay()))
        ->assertForbidden();
});

test('applying online does not touch the quota', function () {
    [$tenant, $domain] = signatureTenant('Prepaid');
    app(SignatureWallet::class)->adjustUnits($tenant->signatureAccount, signatureProduct(), 1, 'Cortesía');

    applyOnStorefront($domain);

    expect(app(SignatureWallet::class)->sellableUnits($tenant->signatureAccount, signatureProduct()))->toBe(1);
    Http::assertNotSent(fn (Request $sent) => str_contains($sent->url(), 'uanataca'));
});
