<?php

use App\Models\CentralUser;
use App\Models\Feature;
use App\Models\LimitType;
use App\Models\Module;
use App\Models\Plan;
use Database\Seeders\CentralAclSeeder;

function planManager(): CentralUser
{
    /** @var CentralUser $user */
    $user = CentralUser::factory()->create();
    $user->givePermissionTo(['central.plans.view', 'central.plans.create', 'central.plans.update']);

    return $user;
}

test('a central user without permission cannot manage plans', function () {
    $this->seed(CentralAclSeeder::class);

    $user = CentralUser::factory()->create();

    $response = $this->actingAs($user, 'central')->get(route('central.plans.create'));

    $response->assertForbidden();
});

test('a plan can be created with prices, modules, features and limits', function () {
    $this->seed(CentralAclSeeder::class);

    $module = Module::factory()->create();
    $feature = Feature::factory()->create();
    $limitType = LimitType::factory()->create();

    $response = $this->actingAs(planManager(), 'central')->post(route('central.plans.store'), [
        'name' => 'Growth',
        'slug' => 'growth',
        'trial_days' => 14,
        'prices' => [
            'Monthly' => ['enabled' => '1', 'price' => '49.00', 'currency' => 'USD'],
        ],
        'modules' => [$module->id],
        'features' => [$feature->id],
        'limits' => [$limitType->id => 10],
    ]);

    $plan = Plan::firstWhere('slug', 'growth');

    $response->assertRedirect(route('central.plans.show', $plan));

    expect($plan)->not->toBeNull()
        ->and($plan->trial_days)->toBe(14)
        ->and($plan->prices()->count())->toBe(1)
        ->and($plan->modules()->pluck('modules.id')->all())->toBe([$module->id])
        ->and($plan->features()->pluck('features.id')->all())->toBe([$feature->id])
        ->and($plan->limits()->first()->pivot->value)->toBe(10);
});

test('updating a plan resyncs its prices, modules, features and limits', function () {
    $this->seed(CentralAclSeeder::class);

    $moduleA = Module::factory()->create();
    $moduleB = Module::factory()->create();
    $limitType = LimitType::factory()->create();

    $plan = Plan::factory()->create(['slug' => 'starter-test']);
    $plan->modules()->attach($moduleA->id);
    $plan->limits()->attach($limitType->id, ['value' => 5]);

    $response = $this->actingAs(planManager(), 'central')->patch(route('central.plans.update', $plan), [
        'name' => $plan->name,
        'slug' => $plan->slug,
        'trial_days' => null,
        'prices' => [],
        'modules' => [$moduleB->id],
        'features' => [],
        'limits' => [$limitType->id => 20],
    ]);

    $response->assertRedirect(route('central.plans.show', $plan));

    $plan->refresh();

    expect($plan->modules()->pluck('modules.id')->all())->toBe([$moduleB->id])
        ->and($plan->limits()->first()->pivot->value)->toBe(20);
});

test('toggling a plan flips its active state', function () {
    $this->seed(CentralAclSeeder::class);

    $plan = Plan::factory()->create(['is_active' => true]);

    $this->actingAs(planManager(), 'central')->patch(route('central.plans.toggle-active', $plan));

    expect($plan->fresh()->is_active)->toBeFalse();
});

test('the plans index supports search, sort and pagination', function () {
    $this->seed(CentralAclSeeder::class);

    Plan::factory()->count(20)->create();
    Plan::factory()->create(['name' => 'Zzyzx Plan']);

    $viewer = CentralUser::factory()->create();
    $viewer->givePermissionTo('central.plans.view');

    $searched = $this->actingAs($viewer, 'central')->get(route('central.plans.index', ['filter' => ['search' => 'Zzyzx']]));
    $searchedPlans = $searched->inertiaProps('plans');
    expect($searchedPlans['total'])->toBe(1)
        ->and($searchedPlans['data'][0]['name'])->toBe('Zzyzx Plan');

    $sorted = $this->actingAs($viewer, 'central')->get(route('central.plans.index', ['sort' => 'name']));
    $names = collect($sorted->inertiaProps('plans')['data'])->pluck('name');
    expect($names->all())->toBe($names->sort()->values()->all());

    $paginated = $this->actingAs($viewer, 'central')->get(route('central.plans.index'));
    expect($paginated->inertiaProps('plans')['data'])->toHaveCount(15);
});
