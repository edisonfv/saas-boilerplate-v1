<?php

use App\Enums\SignatureRequestSource;
use App\Enums\SignatureRequestStatus;
use App\Models\SignaturePackage;
use App\Models\SignatureProduct;
use App\Models\SignatureRequest;
use App\Models\SignatureStorefront;
use App\Services\Signatures\SignatureWallet;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    seedSignatures();
});

afterEach(function () {
    tenancy()->end();
});

// --- Tenant workspace --------------------------------------------------------

test('a tenant without the signatures module cannot open it', function () {
    [$tenant, $domain] = supportTenant('starter');
    $owner = supportTenantUser($tenant);

    $this->actingAs($owner)
        ->get("http://{$domain}/firmas-electronicas")
        ->assertForbidden();
});

test('users need the signature permissions', function () {
    [$tenant, $domain] = signatureTenant();
    $member = supportTenantUser($tenant, role: 'member');

    $this->actingAs($member)
        ->get("http://{$domain}/firmas-electronicas")
        ->assertForbidden();
});

test('an operator registers a sale with its documents and sends it to Uanataca', function () {
    Http::fake(['uanataca.test/*' => Http::response(['result' => true, 'token' => 'TKN-42'])]);

    [$tenant, $domain] = signatureTenant('Credit', creditLimit: 50);
    $owner = supportTenantUser($tenant);

    $response = $this->actingAs($owner)->post("http://{$domain}/firmas-electronicas/solicitudes", [
        ...signatureApplicant(),
        'documents' => signatureDocuments(),
    ]);

    $request = $tenant->run(fn () => SignatureRequest::with('documents')->sole());
    $response->assertRedirect(route('tenant.signatures.requests.show', $request));

    expect($request->status->equals(SignatureRequestStatus::Draft()))->toBeTrue()
        ->and($request->documents)->toHaveCount(3)
        ->and($request->created_by)->toBe((string) $owner->id);

    $this->actingAs($owner)
        ->get("http://{$domain}/firmas-electronicas/solicitudes/{$request->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Signatures/Requests/Show')
            ->where('request.code', 'FE-000001')
            ->where('request.missing_documents', [])
            ->where('account.credit_available', '50.00')
            ->where('can.submit', true));

    $this->actingAs($owner)
        ->post("http://{$domain}/firmas-electronicas/solicitudes/{$request->id}/enviar")
        ->assertRedirect()
        ->assertSessionHas('status', 'signature-request-submitted');

    expect($tenant->run(fn () => $request->fresh()->provider_token))->toBe('TKN-42')
        ->and($tenant->signatureAccount->fresh()->credit_used)->toBe('15.00');
});

test('an invalid cédula and missing documents are rejected', function () {
    [$tenant, $domain] = signatureTenant();

    $this->actingAs(supportTenantUser($tenant))
        ->post("http://{$domain}/firmas-electronicas/solicitudes", signatureApplicant(['document_number' => '1710034060']))
        ->assertSessionHasErrors(['document_number', 'documents.IdFront', 'documents.Selfie']);
});

test('a legal representative needs company data and documents', function () {
    [$tenant, $domain] = signatureTenant();

    $this->actingAs(supportTenantUser($tenant))
        ->post("http://{$domain}/firmas-electronicas/solicitudes", [
            ...signatureApplicant(['applicant_type' => 'LegalRepresentative']),
            'documents' => signatureDocuments(),
        ])
        ->assertSessionHasErrors(['company_name', 'company_ruc', 'position', 'documents.RucCopy', 'documents.CompanyConstitution']);
});

test('without quota the submission is refused with a clear message', function () {
    Http::fake();

    [$tenant, $domain] = signatureTenant('Prepaid');
    $owner = supportTenantUser($tenant);
    $draft = signatureDraft($tenant);

    $this->actingAs($owner)
        ->post("http://{$domain}/firmas-electronicas/solicitudes/{$draft->id}/enviar")
        ->assertSessionHasErrors(['submit' => 'No tienes firmas disponibles de «Firma 1 año». Adquiere un nuevo paquete para continuar vendiendo.']);

    // Nothing reaches the provider (Inertia SSR may use the HTTP client in dev).
    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'uanataca'));
});

// --- Public website -----------------------------------------------------------

test('the root of a tenant subdomain is its public signatures website', function () {
    [$tenant, $domain] = signatureTenant();
    $tenant->run(fn () => SignatureStorefront::current()->update([
        'prices' => [signatureProduct()->id => '29.90'],
        'whatsapp' => '+593 99 123 4567',
        'whatsapp_message' => 'Hola, quiero mi firma',
    ]));

    $this->get("http://{$domain}/")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Signatures/Storefront/Show')
            ->where('storefront.company_name', 'Acme S.A.')
            ->where('storefront.whatsapp_url', 'https://wa.me/593991234567?text=Hola%2C%20quiero%20mi%20firma')
            ->where('products', fn ($products) => collect($products)->firstWhere('id', signatureProduct()->id)['price'] === '29.90')
            ->has('requirements', 3));

    $this->get("http://{$domain}/firmas")->assertRedirect('/');
});

test('tenants without the signatures module land on their login', function () {
    [, $domain] = supportTenant('starter');

    $this->get("http://{$domain}/")->assertRedirect('/login');
    $this->get("http://{$domain}/solicitud")->assertForbidden();
});

test('a customer applies step by step and it lands as a draft for review', function () {
    Http::fake();

    [$tenant, $domain] = signatureTenant();
    $tenant->run(fn () => SignatureStorefront::current()->update(['whatsapp' => '593991234567']));

    $this->get("http://{$domain}/solicitud?firma=".signatureProduct('TwoYears')->id)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Signatures/Storefront/Apply')
            ->where('selectedProductId', signatureProduct('TwoYears')->id));

    $this->post("http://{$domain}/solicitud", [
        ...signatureApplicant(['sale_price' => '1.00']),
        'accepts_terms' => '1',
        'documents' => signatureDocuments(),
    ])->assertRedirect(route('tenant.signatures.storefront.received'));

    $request = $tenant->run(fn () => SignatureRequest::sole());

    expect($request->source->equals(SignatureRequestSource::Storefront()))->toBeTrue()
        ->and($request->status->equals(SignatureRequestStatus::Draft()))->toBeTrue()
        // The customer can not set their own price.
        ->and($request->sale_price)->toBe('25.00');

    $this->get("http://{$domain}/solicitud/enviada")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Signatures/Storefront/Received')
            ->where('code', 'FE-000001')
            ->where('whatsappUrl', fn (string $url) => str_contains($url, 'FE-000001')));

    // Nothing reaches the provider (Inertia SSR may use the HTTP client in dev).
    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'uanataca'));
});

test('the confirmation page needs a fresh application', function () {
    [, $domain] = signatureTenant();

    $this->get("http://{$domain}/solicitud/enviada")->assertRedirect('/');
});

test('the application requires the data-processing authorization', function () {
    [, $domain] = signatureTenant();

    $this->post("http://{$domain}/solicitud", [...signatureApplicant(), 'documents' => signatureDocuments()])
        ->assertSessionHasErrors('accepts_terms');
});

test('the tenant configures its WhatsApp contact', function () {
    [$tenant, $domain] = signatureTenant();

    $this->actingAs(supportTenantUser($tenant))
        ->put("http://{$domain}/firmas-electronicas/sitio-web", [
            ...storefrontSettings(),
            'whatsapp' => '593991234567',
            'whatsapp_message' => 'Hola, necesito una firma',
        ])
        ->assertSessionHasNoErrors();

    expect($tenant->run(fn () => SignatureStorefront::current()->whatsappUrl()))
        ->toBe('https://wa.me/593991234567?text=Hola%2C%20necesito%20una%20firma');
});

test('the public site shows the default copy until the tenant edits it', function () {
    [, $domain] = signatureTenant();

    $this->get("http://{$domain}/")
        ->assertInertia(fn (Assert $page) => $page
            ->where('uses', SignatureStorefront::DefaultUses)
            ->where('steps', SignatureStorefront::DefaultSteps)
            ->where('faqs', SignatureStorefront::DefaultFaqs));
});

test('the tenant edits the sections of its site and can hide one', function () {
    [$tenant, $domain] = signatureTenant();

    $this->actingAs(supportTenantUser($tenant))
        ->put("http://{$domain}/firmas-electronicas/sitio-web", [
            ...storefrontSettings(),
            'uses' => [['title' => ' Facturación SRI ', 'text' => 'Factura desde hoy.', 'extra' => 'x']],
            'steps' => [],
            'faqs' => [['question' => '¿Atienden sábados?', 'answer' => 'Sí, de 9 a 13h.']],
        ])
        ->assertSessionHasNoErrors();

    $this->get("http://{$domain}/")
        ->assertInertia(fn (Assert $page) => $page
            ->where('uses', [['title' => 'Facturación SRI', 'text' => 'Factura desde hoy.']])
            ->where('steps', [])
            ->where('faqs', [['question' => '¿Atienden sábados?', 'answer' => 'Sí, de 9 a 13h.']]));
});

test('each section entry needs its texts', function () {
    [$tenant, $domain] = signatureTenant();

    $this->actingAs(supportTenantUser($tenant))
        ->put("http://{$domain}/firmas-electronicas/sitio-web", [
            ...storefrontSettings(),
            'faqs' => [['question' => '¿Precio?', 'answer' => '']],
        ])
        ->assertSessionHasErrors('faqs.0.answer');
});

/**
 * A valid storefront settings payload (the editor always sends every list).
 *
 * @return array<string, mixed>
 */
function storefrontSettings(): array
{
    return [
        'headline' => 'Tu firma hoy',
        'uses' => SignatureStorefront::DefaultUses,
        'steps' => SignatureStorefront::DefaultSteps,
        'faqs' => SignatureStorefront::DefaultFaqs,
    ];
}

// --- Central console ---------------------------------------------------------

test('billing staff affiliates a tenant and sells it a prepaid package', function () {
    [$tenant] = supportTenant('pro');
    $staff = supportStaff('billing');
    $package = SignaturePackage::query()->where('quantity', 10)->where('price', 130)->sole();

    $this->actingAs($staff, 'central')
        ->put(route('central.signatures.accounts.configure', $tenant), [
            'affiliation_mode' => 'Prepaid',
            'is_active' => '1',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($staff, 'central')
        ->post(route('central.signatures.accounts.packages.store', $tenant), [
            'signature_package_id' => $package->id,
            'reference' => 'FAC-001-001-000123',
        ])
        ->assertSessionHas('status', 'signature-package-sold');

    expect(app(SignatureWallet::class)->sellableUnits($tenant->signatureAccount()->first(), signatureProduct()))->toBe(10);

    $this->actingAs($staff, 'central')
        ->get(route('central.signatures.accounts.show', $tenant))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Central/Signatures/Accounts/Show')
            ->where('account.affiliation_mode', 'Prepaid')
            ->where('ledger.data.0.type', 'PackagePurchase')
            ->where('ledger.data.0.reference', 'FAC-001-001-000123'));
});

test('a credit tenant needs a credit limit', function () {
    [$tenant] = supportTenant('pro');

    $this->actingAs(supportStaff(), 'central')
        ->put(route('central.signatures.accounts.configure', $tenant), [
            'affiliation_mode' => 'Credit',
            'is_active' => '1',
        ])
        ->assertSessionHasErrors('credit_limit');
});

test('sales staff can look at accounts but not move quota', function () {
    [$tenant] = signatureTenant('Prepaid');
    $sales = supportStaff('sales');

    $this->actingAs($sales, 'central')
        ->get(route('central.signatures.accounts.index'))
        ->assertOk();

    $this->actingAs($sales, 'central')
        ->post(route('central.signatures.accounts.adjustments.store', $tenant), [
            'signature_product_id' => signatureProduct()->id,
            'units' => 5,
            'description' => 'Regalo',
        ])
        ->assertForbidden();
});

test('staff manage the product catalog and its packages', function () {
    $staff = supportStaff();

    $this->actingAs($staff, 'central')
        ->post(route('central.signatures.products.store'), [
            'name' => 'Firma 4 años',
            'validity' => 'FourYears',
            'container' => 'File',
            'credit_unit_price' => '42.50',
        ])
        ->assertRedirect();

    $product = SignatureProduct::query()->where('validity', 'FourYears')->sole();

    $this->actingAs($staff, 'central')
        ->post(route('central.signatures.products.packages.store', $product), [
            'name' => '5 firmas de 4 años',
            'quantity' => 5,
            'price' => '190',
        ])
        ->assertSessionHas('status', 'signature-package-created');

    // Validity + container is unique.
    $this->actingAs($staff, 'central')
        ->post(route('central.signatures.products.store'), [
            'name' => 'Duplicada',
            'validity' => 'FourYears',
            'container' => 'File',
            'credit_unit_price' => '40',
        ])
        ->assertSessionHasErrors('container');

    expect($product->packages()->sole()->quantity)->toBe(5);
});
