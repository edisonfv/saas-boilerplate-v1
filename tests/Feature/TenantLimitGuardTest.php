<?php

use App\Enums\BillingPeriod;
use App\Exceptions\TenantLimitExceeded;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\TenantLimitGuard;
use App\Services\TenantPlanSubscriber;
use Database\Seeders\CatalogSeeder;

afterEach(function () {
    tenancy()->end();
});

test('it allows consumption within a tenant plan limit', function () {
    $this->seed(CatalogSeeder::class);

    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);
    $plan = Plan::where('slug', 'starter')->sole();

    app(TenantPlanSubscriber::class)->subscribe($tenant, $plan, BillingPeriod::Monthly());

    expect(app(TenantLimitGuard::class)->allows($tenant, 'users', currentUsage: 4))->toBeTrue();

    $tenant->delete();
});

test('it throws when requested consumption exceeds a tenant plan limit', function () {
    $this->seed(CatalogSeeder::class);

    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);
    $plan = Plan::where('slug', 'starter')->sole();

    app(TenantPlanSubscriber::class)->subscribe($tenant, $plan, BillingPeriod::Monthly());

    expect(fn () => app(TenantLimitGuard::class)->assertCanConsume($tenant, 'users', currentUsage: 5))
        ->toThrow(TenantLimitExceeded::class);

    $tenant->delete();
});

test('it denies unknown limits by default', function () {
    $this->seed(CatalogSeeder::class);

    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);
    $plan = Plan::where('slug', 'starter')->sole();

    app(TenantPlanSubscriber::class)->subscribe($tenant, $plan, BillingPeriod::Monthly());

    expect(app(TenantLimitGuard::class)->allows($tenant, 'unknown-limit', currentUsage: 0))->toBeFalse();

    $tenant->delete();
});
