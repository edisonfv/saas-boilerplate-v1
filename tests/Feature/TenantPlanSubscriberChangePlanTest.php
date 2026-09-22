<?php

use App\Enums\BillingPeriod;
use App\Enums\ModuleSource;
use App\Enums\SubscriptionChangeStatus;
use App\Enums\SubscriptionChangeType;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\TenantPlanSubscriber;
use Database\Seeders\CatalogSeeder;
use App\Models\Permission;

test('an upgrade applies immediately: plan, modules and permissions update right away', function () {
    $this->seed(CatalogSeeder::class);

    $starter = Plan::where('slug', 'starter')->sole();
    $pro = Plan::where('slug', 'pro')->sole();
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);

    $subscriber = app(TenantPlanSubscriber::class);
    $subscription = $subscriber->subscribe($tenant, $starter, BillingPeriod::Monthly());

    $change = $subscriber->changePlan($tenant, $pro, SubscriptionChangeType::Upgrade());

    expect($change->status->equals(SubscriptionChangeStatus::Applied()))->toBeTrue()
        ->and($change->applied_at)->not->toBeNull();

    $subscription->refresh();
    expect($subscription->plan_id)->toBe($pro->id);

    $activeModuleSlugs = $subscription->modules()
        ->whereNull('ends_at')
        ->with('module')
        ->get()
        ->pluck('module.slug')
        ->sort()
        ->values();

    expect($activeModuleSlugs->all())->toBe(['crm', 'inventory', 'projects']);

    $inventoryModule = $subscription->modules()->whereHas('module', fn ($q) => $q->where('slug', 'inventory'))->sole();
    expect($inventoryModule->source->equals(ModuleSource::Plan()))->toBeTrue();

    $tenant->run(function () {
        expect(Permission::where('name', 'inventory.create')->exists())->toBeTrue();
    });

    $tenant->delete();
});

test('a downgrade is scheduled, not applied immediately', function () {
    $this->seed(CatalogSeeder::class);

    $starter = Plan::where('slug', 'starter')->sole();
    $pro = Plan::where('slug', 'pro')->sole();
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);

    $subscriber = app(TenantPlanSubscriber::class);
    $subscription = $subscriber->subscribe($tenant, $pro, BillingPeriod::Monthly());

    $change = $subscriber->changePlan($tenant, $starter, SubscriptionChangeType::Downgrade());

    expect($change->status->equals(SubscriptionChangeStatus::Pending()))->toBeTrue()
        ->and($change->applied_at)->toBeNull()
        ->and($change->effective_at->equalTo($subscription->current_period_end))->toBeTrue();

    $subscription->refresh();
    expect($subscription->plan_id)->toBe($pro->id);

    $inventoryStillActive = $subscription->modules()
        ->whereNull('ends_at')
        ->whereHas('module', fn ($q) => $q->where('slug', 'inventory'))
        ->exists();

    expect($inventoryStillActive)->toBeTrue();

    $tenant->delete();
});
