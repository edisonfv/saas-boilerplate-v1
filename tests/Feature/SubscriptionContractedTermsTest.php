<?php

use App\Enums\BillingPeriod;
use App\Enums\ModuleActivationAction;
use App\Enums\ModuleSource;
use App\Enums\SubscriptionChangeType;
use App\Events\SubscriptionModuleChanged;
use App\Models\Feature;
use App\Models\LimitType;
use App\Models\Module;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\TenantEntitlements;
use App\Services\TenantPlanSubscriber;
use Database\Seeders\CatalogSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Event;

test('subscribing freezes the plan price, features and limits on the subscription', function () {
    $this->seed(CatalogSeeder::class);

    $starter = Plan::where('slug', 'starter')->sole();
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);

    $subscription = app(TenantPlanSubscriber::class)->subscribe($tenant, $starter, BillingPeriod::Monthly());

    expect($subscription->price)->toEqual('29.00')
        ->and($subscription->currency)->toBe('USD')
        ->and($subscription->features()->pluck('slug')->all())->toBe(['basic_reports'])
        ->and($subscription->limits()->get()->mapWithKeys(fn ($limit) => [$limit->key => $limit->pivot->value])->sortKeys()->all())
        ->toBe(['storage_gb' => 10, 'users' => 5]);

    $tenant->delete();
});

test('editing a plan does not alter what existing tenants contracted', function () {
    $this->seed(CatalogSeeder::class);

    $starter = Plan::where('slug', 'starter')->sole();
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);
    $subscription = app(TenantPlanSubscriber::class)->subscribe($tenant, $starter, BillingPeriod::Monthly());

    $starter->prices()->where('billing_period', BillingPeriod::Monthly()->value)->update(['price' => 99]);
    $starter->features()->sync([Feature::where('slug', 'api_access')->sole()->id]);
    $starter->limits()->sync([LimitType::where('key', 'users')->sole()->id => ['value' => 1]]);
    app(TenantEntitlements::class)->forget($tenant);

    $entitlements = app(TenantEntitlements::class);

    expect($subscription->refresh()->price)->toEqual('29.00')
        ->and($entitlements->activeFeatures($tenant)->all())->toBe(['basic_reports'])
        ->and($entitlements->effectiveLimits($tenant))->toBe(['users' => 5, 'storage_gb' => 10]);

    $tenant->delete();
});

test('a plan change replaces the contracted terms with the target plan current ones', function () {
    $this->seed(CatalogSeeder::class);

    $starter = Plan::where('slug', 'starter')->sole();
    $pro = Plan::where('slug', 'pro')->sole();
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);

    $subscriber = app(TenantPlanSubscriber::class);
    $subscription = $subscriber->subscribe($tenant, $starter, BillingPeriod::Monthly());

    $pro->prices()->where('billing_period', BillingPeriod::Monthly()->value)->update(['price' => 89]);

    $subscriber->changePlan($tenant, $pro, SubscriptionChangeType::Upgrade());

    $entitlements = app(TenantEntitlements::class);

    expect($subscription->refresh()->price)->toEqual('89.00')
        ->and($entitlements->activeFeatures($tenant)->sort()->values()->all())->toBe(['advanced_reports', 'api_access'])
        ->and($entitlements->effectiveLimits($tenant))->toBe(['users' => 50, 'storage_gb' => 100]);

    $tenant->delete();
});

test('a plan change keeps addons and absorbs the ones the new plan includes without interrupting access', function () {
    $this->seed(CatalogSeeder::class);

    $starter = Plan::where('slug', 'starter')->sole();
    $pro = Plan::where('slug', 'pro')->sole();
    $inventory = Module::where('slug', 'inventory')->sole();
    $signatures = Module::factory()->sellableAsAddon()->create(['slug' => 'signatures', 'name' => 'Firmas']);
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);

    $subscriber = app(TenantPlanSubscriber::class);
    $subscription = $subscriber->subscribe($tenant, $starter, BillingPeriod::Monthly());

    foreach ([$inventory, $signatures] as $addon) {
        $subscription->modules()->create([
            'module_id' => $addon->id,
            'source' => ModuleSource::Addon(),
            'unit_price' => 15,
            'currency' => 'USD',
            'starts_at' => now()->subMinute(),
        ]);
    }

    Event::fake([SubscriptionModuleChanged::class]);

    $subscriber->changePlan($tenant, $pro, SubscriptionChangeType::Upgrade());

    $activeRows = $subscription->modules()->whereNull('ends_at')->with('module')->get();

    expect($activeRows->filter(fn ($row) => $row->source->equals(ModuleSource::Addon()))->pluck('module.slug')->all())
        ->toBe(['signatures'])
        ->and($activeRows->filter(fn ($row) => $row->source->equals(ModuleSource::Plan()))->pluck('module.slug')->sort()->values()->all())
        ->toBe(['crm', 'inventory', 'projects'])
        ->and(app(TenantEntitlements::class)->activeModules($tenant)->sort()->values()->all())
        ->toBe(['crm', 'inventory', 'projects', 'signatures']);

    Event::assertNotDispatched(
        SubscriptionModuleChanged::class,
        fn (SubscriptionModuleChanged $event) => $event->module->is($inventory)
            && $event->action->equals(ModuleActivationAction::Deactivated()),
    );

    $tenant->delete();
});

test('a plan without an active price for the billing period cannot be subscribed', function () {
    $this->seed(CatalogSeeder::class);

    $starter = Plan::where('slug', 'starter')->sole();
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);

    expect(fn () => app(TenantPlanSubscriber::class)->subscribe($tenant, $starter, BillingPeriod::Quarterly()))
        ->toThrow(RuntimeException::class);

    expect($tenant->subscription()->exists())->toBeFalse();

    $tenant->delete();
});

test('a module contracted by a tenant cannot be deleted', function () {
    $this->seed(CatalogSeeder::class);

    $starter = Plan::where('slug', 'starter')->sole();
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);
    app(TenantPlanSubscriber::class)->subscribe($tenant, $starter, BillingPeriod::Monthly());

    expect(fn () => Module::where('slug', 'crm')->sole()->delete())->toThrow(QueryException::class);

    $tenant->delete();
});
