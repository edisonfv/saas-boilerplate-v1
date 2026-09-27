<?php

use App\Models\SignaturePackage;
use App\Models\SignatureProviderRequest;
use App\Models\SignatureRequest;
use App\Services\Signatures\SignatureIssuance;
use App\Services\Signatures\SignatureWallet;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    seedSignatures();
    Http::fake(['uanataca.test/*' => fn () => Http::response(['result' => true, 'token' => 'TKN-'.uniqid()])]);
});

afterEach(function () {
    tenancy()->end();
});

function sellSignature($tenant): SignatureRequest
{
    $draft = signatureDraft($tenant);

    return $tenant->run(function () use ($tenant, $draft) {
        $request = SignatureRequest::with('documents')->findOrFail($draft->id);
        app(SignatureIssuance::class)->submit($tenant, $request, 'Operador');

        return $request->fresh();
    });
}

test('the settlement compares what customers paid with the credit cost of each signature', function () {
    [$tenant, $domain] = signatureTenant('Credit', creditLimit: 100);
    sellSignature($tenant);
    sellSignature($tenant);
    $owner = supportTenantUser($tenant);

    $this->actingAs($owner, 'web')->get("http://{$domain}/firmas-electronicas/liquidacion")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Signatures/Settlement/Index')
            ->where('summary.sold', 2)
            ->where('summary.revenue', '60.00')
            ->where('summary.cost', '30.00')
            ->where('summary.margin', '30.00')
            ->where('summary.margin_percent', 50)
            ->where('products.0.sold', 2)
            ->where('account.credit_used', '30.00')
            ->has('rows.data', 2)
            ->has('rows.links'));

    $tenant->delete();
});

test('prepaid signatures cost the average unit price of the packages bought', function () {
    [$tenant, $domain] = signatureTenant('Prepaid');
    $package = SignaturePackage::query()->where('signature_product_id', signatureProduct()->id)->orderBy('quantity')->firstOrFail();
    app(SignatureWallet::class)->purchasePackage($tenant->signatureAccount()->first(), $package);
    sellSignature($tenant);
    $owner = supportTenantUser($tenant);

    $unitCost = number_format(((float) $package->price) / $package->quantity, 2, '.', '');

    $this->actingAs($owner, 'web')->get("http://{$domain}/firmas-electronicas/liquidacion")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.sold', 1)
            ->where('summary.cost', $unitCost)
            ->where('account.balances.0.available_units', $package->quantity - 1));

    $tenant->delete();
});

test('reversed signatures are listed but not settled', function () {
    [$tenant, $domain] = signatureTenant('Credit', creditLimit: 100);
    sellSignature($tenant);
    sellSignature($tenant);
    app(SignatureWallet::class)->refund(SignatureProviderRequest::query()->firstOrFail()->consumption, 'Rechazada');
    $owner = supportTenantUser($tenant);

    $this->actingAs($owner, 'web')->get("http://{$domain}/firmas-electronicas/liquidacion")
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.sold', 1)
            ->where('summary.reversed', 1)
            ->where('summary.cost', '15.00')
            ->has('rows.data', 2));

    $tenant->delete();
});

test('only signatures sent within the period are settled', function () {
    [$tenant, $domain] = signatureTenant('Credit', creditLimit: 100);
    sellSignature($tenant);
    $owner = supportTenantUser($tenant);

    $this->actingAs($owner, 'web')->get("http://{$domain}/firmas-electronicas/liquidacion?desde=2020-01-01&hasta=2020-01-31")
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.sold', 0)
            ->where('period.from', '2020-01-01'));

    $tenant->delete();
});

test('the settlement can be exported as CSV', function () {
    [$tenant, $domain] = signatureTenant('Credit', creditLimit: 100);
    $sold = sellSignature($tenant);
    $owner = supportTenantUser($tenant);

    $response = $this->actingAs($owner, 'web')->get("http://{$domain}/firmas-electronicas/liquidacion/exportar");

    $response->assertOk()->assertDownload();
    expect($response->streamedContent())
        ->toContain($sold->code())
        ->toContain('Margen');

    $tenant->delete();
});

test('users without the settlement permission cannot see it', function () {
    [$tenant, $domain] = signatureTenant('Credit', creditLimit: 100);
    $member = supportTenantUser($tenant, 'member', ['tenant.signature-requests.view']);

    $this->actingAs($member, 'web')->get("http://{$domain}/firmas-electronicas/liquidacion")->assertForbidden();

    $tenant->delete();
});

test('the public storefront keeps taking orders while the subscription is lapsed', function () {
    [$tenant, $domain] = signatureTenant('Credit', creditLimit: 100);
    $owner = supportTenantUser($tenant);

    $this->travel(2)->months();

    $this->get("http://{$domain}/solicitud")->assertOk();
    $this->actingAs($owner, 'web')->get("http://{$domain}/firmas-electronicas")->assertForbidden();

    $tenant->delete();
});
