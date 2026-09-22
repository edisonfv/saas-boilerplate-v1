<?php

use App\Enums\BillingPeriod;
use App\Http\Middleware\EnsureTenantHasFeature;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\TenantPlanSubscriber;
use Database\Seeders\CatalogSeeder;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('the middleware allows the request through for a feature the tenant has', function () {
    $this->seed(CatalogSeeder::class);

    $plan = Plan::where('slug', 'starter')->sole();
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);
    app(TenantPlanSubscriber::class)->subscribe($tenant, $plan, BillingPeriod::Monthly());

    $tenant->run(function () {
        $middleware = app(EnsureTenantHasFeature::class);

        $result = $middleware->handle(request(), fn ($request) => response('passed'), 'basic_reports');

        expect($result->getContent())->toBe('passed');
    });

    $tenant->delete();
});

test('the middleware aborts with 403 for a feature the tenant does not have', function () {
    $this->seed(CatalogSeeder::class);

    $plan = Plan::where('slug', 'starter')->sole();
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);
    app(TenantPlanSubscriber::class)->subscribe($tenant, $plan, BillingPeriod::Monthly());

    $tenant->run(function () {
        $middleware = app(EnsureTenantHasFeature::class);

        expect(fn () => $middleware->handle(request(), fn ($request) => 'passed', 'advanced_reports'))
            ->toThrow(HttpException::class);
    });

    $tenant->delete();
});
