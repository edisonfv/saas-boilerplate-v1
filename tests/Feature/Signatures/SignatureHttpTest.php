<?php

use App\Enums\SignatureRequestSource;
use App\Enums\SignatureRequestStatus;
use App\Models\SignaturePackage;
use App\Models\SignatureProduct;
use App\Models\SignatureRequest;
use App\Models\SignatureStorefront;
use App\Services\Signatures\SignatureWallet;
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

    Http::assertNothingSent();
});

// --- Public storefront -------------------------------------------------------

test('the storefront is hidden until the tenant publishes it', function () {
    [$tenant, $domain] = signatureTenant();

    $this->get("http://{$domain}/firmas")->assertNotFound();

    $tenant->run(fn () => SignatureStorefront::current()->update([
        'is_published' => true,
        'prices' => [signatureProduct()->id => '29.90'],
    ]));

    $this->get("http://{$domain}/firmas")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Signatures/Storefront/Show')
            ->where('storefront.company_name', 'Acme S.A.')
            ->where('products', fn ($products) => collect($products)->firstWhere('id', signatureProduct()->id)['price'] === '29.90'));
});

test('a customer applies on the storefront and it lands as a draft for review', function () {
    Http::fake();

    [$tenant, $domain] = signatureTenant();
    $tenant->run(fn () => SignatureStorefront::current()->update(['is_published' => true]));

    $this->post("http://{$domain}/firmas/solicitar", [
        ...signatureApplicant(['sale_price' => '1.00']),
        'accepts_terms' => '1',
        'documents' => signatureDocuments(),
    ])->assertRedirect(route('tenant.signatures.storefront.show'));

    $request = $tenant->run(fn () => SignatureRequest::sole());

    expect($request->source->equals(SignatureRequestSource::Storefront()))->toBeTrue()
        ->and($request->status->equals(SignatureRequestStatus::Draft()))->toBeTrue()
        // The customer can't set their own price.
        ->and($request->sale_price)->toBe('25.00');

    Http::assertNothingSent();
});

test('the storefront requires the data-processing authorization', function () {
    [$tenant, $domain] = signatureTenant();
    $tenant->run(fn () => SignatureStorefront::current()->update(['is_published' => true]));

    $this->post("http://{$domain}/firmas/solicitar", [...signatureApplicant(), 'documents' => signatureDocuments()])
        ->assertSessionHasErrors('accepts_terms');
});

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
