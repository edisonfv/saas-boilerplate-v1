<?php

use App\Enums\BillingPeriod;
use App\Enums\ModuleSource;
use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Tenant;
use App\Services\TenantPlanSubscriber;
use Database\Seeders\CatalogSeeder;
use App\Models\Permission;
use App\Models\Role;

test('subscribing a tenant to a plan creates its subscription, subscription modules, and seeds tenant permissions', function () {
    $this->seed(CatalogSeeder::class);

    $plan = Plan::where('slug', 'starter')->with('modules')->sole();
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);

    $subscription = app(TenantPlanSubscriber::class)->subscribe($tenant, $plan, BillingPeriod::Monthly());

    expect($subscription->tenant_id)->toBe($tenant->getTenantKey())
        ->and($subscription->status->equals(SubscriptionStatus::Trialing()))->toBeTrue()
        ->and($subscription->trial_ends_at)->not->toBeNull();

    $moduleSlugs = $subscription->modules()->with('module')->get()->pluck('module.slug')->sort()->values();
    expect($moduleSlugs->all())->toBe(['crm', 'projects'])
        ->and($subscription->modules->every(fn ($m) => $m->source->equals(ModuleSource::Plan())))->toBeTrue();

    $tenant->run(function () {
        expect(Permission::where('name', 'crm.create')->exists())->toBeTrue()
            ->and(Permission::where('name', 'projects.create')->exists())->toBeTrue();

        $owner = Role::where('name', 'owner')->where('guard_name', 'web')->sole();
        expect($owner->hasPermissionTo('crm.create'))->toBeTrue();
    });

    $tenant->delete();
});
