<?php

use App\Enums\BillingPeriod;
use App\Enums\ModuleActivationAction;
use App\Enums\ModuleSource;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Events\SubscriptionModuleChanged;
use App\Models\Module;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\TenantEntitlements;
use App\Services\TenantPlanSubscriber;
use Database\Seeders\CatalogSeeder;

test('entitlements reflect the modules, features and limits of the subscribed plan', function () {
    $this->seed(CatalogSeeder::class);

    $plan = Plan::where('slug', 'starter')->sole();
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);

    app(TenantPlanSubscriber::class)->subscribe($tenant, $plan, BillingPeriod::Monthly());

    $entitlements = app(TenantEntitlements::class);

    expect($entitlements->activeModules($tenant)->sort()->values()->all())->toBe(['crm', 'projects'])
        ->and($entitlements->activeFeatures($tenant)->all())->toBe(['basic_reports'])
        ->and($entitlements->effectiveLimits($tenant))->toBe(['users' => 5, 'storage_gb' => 10]);

    $tenant->delete();
});

test('the entitlements cache is invalidated when a module is activated', function () {
    $this->seed(CatalogSeeder::class);

    $plan = Plan::where('slug', 'starter')->sole();
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);

    app(TenantPlanSubscriber::class)->subscribe($tenant, $plan, BillingPeriod::Monthly());

    $entitlements = app(TenantEntitlements::class);

    expect($entitlements->activeModules($tenant)->contains('inventory'))->toBeFalse();

    $inventory = Module::where('slug', 'inventory')->sole();
    $subscription = $tenant->subscription()->sole();
    $subscription->modules()->create([
        'module_id' => $inventory->id,
        'source' => ModuleSource::Addon(),
        'starts_at' => now(),
    ]);

    SubscriptionModuleChanged::dispatch($tenant, $inventory, ModuleActivationAction::Activated());

    expect($entitlements->activeModules($tenant)->contains('inventory'))->toBeTrue();

    $tenant->delete();
});

test('inactive subscriptions do not grant tenant entitlements', function () {
    $this->seed(CatalogSeeder::class);

    $plan = Plan::where('slug', 'starter')->sole();
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);
    $subscription = app(TenantPlanSubscriber::class)->subscribe($tenant, $plan, BillingPeriod::Monthly());

    $subscription->update(['status' => SubscriptionStatus::Cancelled()]);
    app(TenantEntitlements::class)->forget($tenant);

    $entitlements = app(TenantEntitlements::class);

    expect($entitlements->activeModules($tenant))->toBeEmpty()
        ->and($entitlements->activeFeatures($tenant))->toBeEmpty()
        ->and($entitlements->effectiveLimits($tenant))->toBe([]);

    $tenant->delete();
});

test('suspended tenants do not grant tenant entitlements', function () {
    $this->seed(CatalogSeeder::class);

    $plan = Plan::where('slug', 'starter')->sole();
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);
    app(TenantPlanSubscriber::class)->subscribe($tenant, $plan, BillingPeriod::Monthly());

    $tenant->update(['status' => TenantStatus::Suspended()]);
    app(TenantEntitlements::class)->forget($tenant);

    $entitlements = app(TenantEntitlements::class);

    expect($entitlements->activeModules($tenant))->toBeEmpty()
        ->and($entitlements->activeFeatures($tenant))->toBeEmpty()
        ->and($entitlements->effectiveLimits($tenant))->toBe([]);

    $tenant->delete();
});
