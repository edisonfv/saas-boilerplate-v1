<?php

use App\Models\SignatureLedgerEntry;
use App\Models\SignaturePackage;
use App\Models\SignatureProviderRequest;
use App\Models\SignatureRequest;
use App\Models\SignatureStorefront;
use App\Models\Tenant;
use App\Services\Signatures\SignatureIssuance;
use App\Services\Signatures\SignatureSalesReport;
use App\Services\Signatures\SignatureWallet;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    seedSignatures();
    Http::fake(['uanataca.test/*' => Http::sequence()
        ->push(['result' => true, 'token' => 'TKN-1'])
        ->push(['result' => true, 'token' => 'TKN-2'])
        ->push(['result' => true, 'token' => 'TKN-3'])
        ->push(['result' => true, 'token' => 'TKN-4']),
    ]);
    signatureProduct()->update(['provider_cost' => '9.00']);
});

afterEach(function () {
    tenancy()->end();
});

/**
 * Sells one paid OneYear/File signature for the tenant at the given retail price.
 */
function sellSignatureFor(Tenant $tenant, string $salePrice = '30.00'): SignatureProviderRequest
{
    $draft = signatureDraft($tenant);

    $tenant->run(function () use ($tenant, $draft, $salePrice) {
        $request = SignatureRequest::with('documents')->findOrFail($draft->id);
        $request->forceFill(['sale_price' => $salePrice])->save();
        app(SignatureIssuance::class)->submit($tenant, $request, 'Operador');
    });

    return SignatureProviderRequest::query()->where('tenant_request_id', $draft->id)->sole();
}

test('a sale snapshots the provider cost, the distributor price and the retail price', function () {
    [$tenant] = signatureTenant('Credit', creditLimit: 100);

    $sale = sellSignatureFor($tenant, '32.00');

    expect($sale->unit_cost)->toBe('9.00')
        ->and($sale->unit_price)->toBe('15.00')
        ->and($sale->sale_price)->toBe('32.00');
});

test('a prepaid sale is priced at the average of the packages the distributor bought', function () {
    [$tenant] = signatureTenant('Prepaid');
    $wallet = app(SignatureWallet::class);
    $account = $tenant->signatureAccount()->first();

    // 10 for 130 + 25 for 300 = 430 / 35 units.
    foreach (SignaturePackage::query()->where('signature_product_id', signatureProduct()->id)->get() as $package) {
        $wallet->purchasePackage($account, $package);
    }

    expect(sellSignatureFor($tenant)->unit_price)->toBe('12.29');
});

test('the report adds up revenue and margins and leaves refunded sales out', function () {
    [$tenant] = signatureTenant('Credit', creditLimit: 100);
    sellSignatureFor($tenant, '30.00');
    sellSignatureFor($tenant, '40.00');
    $refunded = sellSignatureFor($tenant, '25.00');
    app(SignatureWallet::class)->refund(SignatureLedgerEntry::query()->findOrFail($refunded->consumption_entry_id), 'Rechazada');

    $summary = app(SignatureSalesReport::class)->summary(now()->startOfMonth(), now()->endOfDay());

    expect($summary)->toMatchArray([
        'units' => 2,
        'revenue' => '30.00',
        'provider_cost' => '18.00',
        'central_profit' => '12.00',
        'central_margin' => 40.0,
        'retail' => '70.00',
        'distributor_profit' => '40.00',
        'average_sale_price' => '35.00',
        'refunded' => 1,
        'without_cost' => 0,
    ]);

    $ranking = app(SignatureSalesReport::class)->byTenant(now()->startOfMonth(), now()->endOfDay());
    expect($ranking)->toHaveCount(1)
        ->and($ranking->first())->toMatchArray(['tenant_id' => $tenant->id, 'units' => 2, 'share' => 100.0]);
});

test('accounts close to their credit limit or out of prepaid signatures raise alerts', function () {
    [$credit] = signatureTenant('Credit', creditLimit: 18);
    sellSignatureFor($credit);
    [$prepaid] = signatureTenant('Prepaid');

    $alerts = app(SignatureSalesReport::class)->alerts();

    expect($alerts->where('tenant_id', $credit->id)->pluck('kind'))->toContain('credit')
        ->and($alerts->where('tenant_id', $prepaid->id)->pluck('kind')->all())->toBe(['stock', 'idle']);
});

test('distributors cannot sell below the minimum retail price', function () {
    [$tenant, $domain] = signatureTenant('Credit', creditLimit: 100);
    $owner = supportTenantUser($tenant);
    $product = signatureProduct();
    $product->update(['min_retail_price' => '20.00']);

    $this->actingAs($owner)
        ->put("http://{$domain}/firmas-electronicas/sitio-web", [
            ...storefrontSettings(),
            'prices' => [$product->id => '18.50'],
        ])
        ->assertSessionHasErrors("prices.{$product->id}");

    $this->actingAs($owner)
        ->post("http://{$domain}/firmas-electronicas/solicitudes", [
            ...signatureApplicant(['sale_price' => '19.99']),
            'documents' => signatureDocuments(),
        ])
        ->assertSessionHasErrors('sale_price');

    // A price published before the floor was raised is lifted to the floor.
    expect($product->retailPriceFrom('15.00'))->toBe('20.00')
        ->and($product->retailPriceFrom('22.00'))->toBe('22.00')
        ->and($product->retailPriceFrom(null))->toBe('25.00');
});

test('the provider cost is never shown to tenants', function () {
    [$tenant, $domain] = signatureTenant('Credit', creditLimit: 100);

    $this->actingAs(supportTenantUser($tenant))
        ->get("http://{$domain}/firmas-electronicas/solicitudes/nueva")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.0', fn (Assert $product) => $product
                ->missing('provider_cost')
                ->missing('credit_unit_margin')
                ->etc()));
});

test('the business owner sees sales, margins and the ranking of distributors', function () {
    [$tenant] = signatureTenant('Credit', creditLimit: 100);
    sellSignatureFor($tenant, '30.00');

    $this->actingAs(supportStaff('sales'), 'central')
        ->get(route('central.signatures.sales.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Central/Signatures/Sales/Index')
            ->where('filters.period', 'ThisMonth')
            ->where('summary.units', 1)
            ->where('summary.central_profit', '6.00')
            ->where('tenants.0.tenant_id', $tenant->id)
            ->where('cash.credit_outstanding', '15.00')
            ->has('trend', 12));

    $this->actingAs(supportStaff('support'), 'central')
        ->get(route('central.signatures.sales.index'))
        ->assertForbidden();
});

test('the sales of a period can be exported to CSV', function () {
    [$tenant] = signatureTenant('Credit', creditLimit: 100);
    sellSignatureFor($tenant, '30.00');

    $csv = $this->actingAs(supportStaff(), 'central')
        ->get(route('central.signatures.sales.export', ['period' => 'ThisYear']))
        ->assertOk()
        ->streamedContent();

    expect($csv)->toContain('Utilidad central')
        ->toContain('"Acme S.A.","Firma 1 año",Enviada,9.00,15.00,30.00,6.00,15.00,TKN-1');
});

test('the tenant directory shows each distributor and filters by affiliation', function () {
    [$credit] = signatureTenant('Credit', creditLimit: 100);
    sellSignatureFor($credit);
    [$unaffiliated] = supportTenant('pro');

    $this->actingAs(supportStaff('billing'), 'central')
        ->get(route('central.tenants.index', ['filter' => ['affiliation' => 'Credit']]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Central/Tenants/Index')
            ->has('tenants.data', 1)
            ->where('tenants.data.0.id', $credit->id)
            ->where('tenants.data.0.signatures.affiliation_mode', 'Credit')
            ->where('tenants.data.0.signatures.sold_this_month', 1)
            ->where('stats.signaturesThisMonth', 1));

    $this->actingAs(supportStaff('billing'), 'central')
        ->get(route('central.tenants.index', ['filter' => ['affiliation' => 'None']]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('tenants.data', 1)
            ->where('tenants.data.0.id', $unaffiliated->id)
            ->where('tenants.data.0.signatures', null));
});

test('a tenant signature tab shows its performance and published prices', function () {
    [$tenant] = signatureTenant('Credit', creditLimit: 100);
    sellSignatureFor($tenant, '30.00');
    $product = signatureProduct();
    $tenant->run(fn () => SignatureStorefront::current()->update(['prices' => [$product->id => '28.00']]));

    $this->actingAs(supportStaff('billing'), 'central')
        ->get(route('central.tenants.signatures', $tenant))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Central/Tenants/Signatures')
            ->where('performance.month.units', 1)
            ->where('sales.0.sale_price', '30.00')
            ->where('pricing', fn ($pricing) => collect($pricing)->contains(fn ($row) => $row['product_id'] === $product->id
                && $row['unit_price'] === '15.00'
                && $row['retail_price'] === '28.00'
                && $row['uses_own_price'] === true
                && $row['central_margin'] === '6.00'
                && $row['distributor_margin'] === '13.00')));
});
