<?php

use App\Models\CentralUser;
use App\Models\Feature;
use App\Models\Module;
use Database\Seeders\CentralAclSeeder;

function featureManager(): CentralUser
{
    /** @var CentralUser $user */
    $user = CentralUser::factory()->create();
    $user->givePermissionTo(['central.features.view', 'central.features.create', 'central.features.update']);

    return $user;
}

test('a central user without permission cannot manage features', function () {
    $this->seed(CentralAclSeeder::class);

    $user = CentralUser::factory()->create();

    $response = $this->actingAs($user, 'central')->get(route('central.features.create'));

    $response->assertForbidden();
});

test('a feature can be created and linked to a module', function () {
    $this->seed(CentralAclSeeder::class);

    $module = Module::factory()->create();

    $response = $this->actingAs(featureManager(), 'central')->post(route('central.features.store'), [
        'name' => 'API Access',
        'slug' => 'api-access',
        'module_id' => $module->id,
    ]);

    $response->assertRedirect(route('central.features.index'));

    $feature = Feature::firstWhere('slug', 'api-access');

    expect($feature)->not->toBeNull()
        ->and($feature->module_id)->toBe($module->id);
});

test('a feature can be updated', function () {
    $this->seed(CentralAclSeeder::class);

    $feature = Feature::factory()->create(['name' => 'Old Name']);

    $response = $this->actingAs(featureManager(), 'central')->patch(route('central.features.update', $feature), [
        'name' => 'New Name',
        'slug' => $feature->slug,
        'module_id' => null,
    ]);

    $response->assertRedirect(route('central.features.index'));
    expect($feature->fresh()->name)->toBe('New Name');
});

test('toggling a feature flips its active state', function () {
    $this->seed(CentralAclSeeder::class);

    $feature = Feature::factory()->create(['is_active' => true]);

    $this->actingAs(featureManager(), 'central')->patch(route('central.features.toggle-active', $feature));

    expect($feature->fresh()->is_active)->toBeFalse();
});

test('the features index supports search, sort and pagination', function () {
    $this->seed(CentralAclSeeder::class);

    Feature::factory()->count(20)->create();
    Feature::factory()->create(['name' => 'Zzyzx Feature']);

    $viewer = featureManager();

    $searched = $this->actingAs($viewer, 'central')->get(route('central.features.index', ['filter' => ['search' => 'Zzyzx']]));
    $searchedFeatures = $searched->inertiaProps('features');
    expect($searchedFeatures['total'])->toBe(1)
        ->and($searchedFeatures['data'][0]['name'])->toBe('Zzyzx Feature');

    $sorted = $this->actingAs($viewer, 'central')->get(route('central.features.index', ['sort' => 'name']));
    $names = collect($sorted->inertiaProps('features')['data'])->pluck('name');
    expect($names->all())->toBe($names->sort()->values()->all());

    $paginated = $this->actingAs($viewer, 'central')->get(route('central.features.index'));
    expect($paginated->inertiaProps('features')['data'])->toHaveCount(15);
});
