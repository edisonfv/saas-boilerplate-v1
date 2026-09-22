<?php

use App\Models\CentralUser;
use App\Models\LimitType;
use Database\Seeders\CentralAclSeeder;

function limitTypeManager(): CentralUser
{
    /** @var CentralUser $user */
    $user = CentralUser::factory()->create();
    $user->givePermissionTo(['central.limit-types.view', 'central.limit-types.create', 'central.limit-types.update']);

    return $user;
}

test('a central user without permission cannot manage limit types', function () {
    $this->seed(CentralAclSeeder::class);

    $user = CentralUser::factory()->create();

    $response = $this->actingAs($user, 'central')->get(route('central.limit-types.create'));

    $response->assertForbidden();
});

test('a limit type can be created', function () {
    $this->seed(CentralAclSeeder::class);

    $response = $this->actingAs(limitTypeManager(), 'central')->post(route('central.limit-types.store'), [
        'key' => 'api-calls',
        'name' => 'API Calls',
        'unit' => 'calls/month',
    ]);

    $response->assertRedirect(route('central.limit-types.index'));

    $limitType = LimitType::firstWhere('key', 'api-calls');

    expect($limitType)->not->toBeNull()
        ->and($limitType->unit)->toBe('calls/month');
});

test('a limit type can be updated', function () {
    $this->seed(CentralAclSeeder::class);

    $limitType = LimitType::factory()->create(['name' => 'Old Name']);

    $response = $this->actingAs(limitTypeManager(), 'central')->patch(route('central.limit-types.update', $limitType), [
        'key' => $limitType->key,
        'name' => 'New Name',
        'unit' => $limitType->unit,
    ]);

    $response->assertRedirect(route('central.limit-types.index'));
    expect($limitType->fresh()->name)->toBe('New Name');
});

test('toggling a limit type flips its active state', function () {
    $this->seed(CentralAclSeeder::class);

    $limitType = LimitType::factory()->create(['is_active' => true]);

    $this->actingAs(limitTypeManager(), 'central')->patch(route('central.limit-types.toggle-active', $limitType));

    expect($limitType->fresh()->is_active)->toBeFalse();
});

test('the limit types index supports search, sort and pagination', function () {
    $this->seed(CentralAclSeeder::class);

    LimitType::factory()->count(20)->create();
    LimitType::factory()->create(['name' => 'Zzyzx Limit']);

    $viewer = limitTypeManager();

    $searched = $this->actingAs($viewer, 'central')->get(route('central.limit-types.index', ['filter' => ['search' => 'Zzyzx']]));
    $searchedLimitTypes = $searched->inertiaProps('limitTypes');
    expect($searchedLimitTypes['total'])->toBe(1)
        ->and($searchedLimitTypes['data'][0]['name'])->toBe('Zzyzx Limit');

    $sorted = $this->actingAs($viewer, 'central')->get(route('central.limit-types.index', ['sort' => 'name']));
    $names = collect($sorted->inertiaProps('limitTypes')['data'])->pluck('name');
    expect($names->all())->toBe($names->sort()->values()->all());

    $paginated = $this->actingAs($viewer, 'central')->get(route('central.limit-types.index'));
    expect($paginated->inertiaProps('limitTypes')['data'])->toHaveCount(15);
});
