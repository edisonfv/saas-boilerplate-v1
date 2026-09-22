<?php

use App\Enums\BillingPeriod;
use App\Enums\SubscriptionChangeStatus;
use App\Enums\SubscriptionChangeType;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\TenantPlanSubscriber;
use Database\Seeders\CatalogSeeder;

test('the command applies due (past effective_at) pending changes, but leaves future ones untouched', function () {
    $this->seed(CatalogSeeder::class);

    $starter = Plan::where('slug', 'starter')->sole();
    $pro = Plan::where('slug', 'pro')->sole();

    $dueTenant = Tenant::create(['id' => 'tenant-'.uniqid()]);
    $subscriber = app(TenantPlanSubscriber::class);
    $subscriber->subscribe($dueTenant, $pro, BillingPeriod::Monthly());
    $dueChange = $subscriber->changePlan($dueTenant, $starter, SubscriptionChangeType::Downgrade());
    $dueChange->update(['effective_at' => now()->subDay()]);

    $futureTenant = Tenant::create(['id' => 'tenant-'.uniqid()]);
    $subscriber->subscribe($futureTenant, $pro, BillingPeriod::Monthly());
    $futureChange = $subscriber->changePlan($futureTenant, $starter, SubscriptionChangeType::Downgrade());

    $this->artisan('subscriptions:apply-scheduled-changes')->assertSuccessful();

    $dueChange->refresh();
    $futureChange->refresh();

    expect($dueChange->status->equals(SubscriptionChangeStatus::Applied()))->toBeTrue()
        ->and($dueChange->applied_at)->not->toBeNull()
        ->and($dueTenant->subscription()->sole()->plan_id)->toBe($starter->id);

    expect($futureChange->status->equals(SubscriptionChangeStatus::Pending()))->toBeTrue()
        ->and($futureTenant->subscription()->sole()->plan_id)->toBe($pro->id);

    $inventoryEnded = $dueTenant->subscription()->sole()->modules()
        ->whereHas('module', fn ($q) => $q->where('slug', 'inventory'))
        ->whereNotNull('ends_at')
        ->exists();

    expect($inventoryEnded)->toBeTrue();

    $dueTenant->delete();
    $futureTenant->delete();
});
