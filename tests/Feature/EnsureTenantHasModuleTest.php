<?php

use App\Enums\BillingPeriod;
use App\Http\Middleware\EnsureTenantHasModule;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\TenantPlanSubscriber;
use Database\Seeders\CatalogSeeder;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('the middleware allows the request through for a module the tenant has', function () {
    $this->seed(CatalogSeeder::class);

    $plan = Plan::where('slug', 'starter')->sole();
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);
    app(TenantPlanSubscriber::class)->subscribe($tenant, $plan, BillingPeriod::Monthly());

    $tenant->run(function () {
        $middleware = app(EnsureTenantHasModule::class);

        $result = $middleware->handle(request(), fn ($request) => response('passed'), 'crm');

        expect($result->getContent())->toBe('passed');
    });

    $tenant->delete();
});

test('the middleware aborts with 403 for a module the tenant does not have', function () {
    $this->seed(CatalogSeeder::class);

    $plan = Plan::where('slug', 'starter')->sole();
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);
    app(TenantPlanSubscriber::class)->subscribe($tenant, $plan, BillingPeriod::Monthly());

    $tenant->run(function () {
        $middleware = app(EnsureTenantHasModule::class);

        expect(fn () => $middleware->handle(request(), fn ($request) => 'passed', 'inventory'))
            ->toThrow(HttpException::class);
    });

    $tenant->delete();
});
