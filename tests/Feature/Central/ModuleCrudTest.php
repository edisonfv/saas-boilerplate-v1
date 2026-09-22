<?php

use App\Models\CentralUser;
use App\Models\Module;
use Database\Seeders\CentralAclSeeder;

function moduleManager(): CentralUser
{
    /** @var CentralUser $user */
    $user = CentralUser::factory()->create();
    $user->givePermissionTo(['central.modules.view', 'central.modules.create', 'central.modules.update']);

    return $user;
}

test('a central user without permission cannot manage modules', function () {
    $this->seed(CentralAclSeeder::class);

    $user = CentralUser::factory()->create();

    $response = $this->actingAs($user, 'central')->get(route('central.modules.create'));

    $response->assertForbidden();
});

test('a module can be created with prices and permissions', function () {
    $this->seed(CentralAclSeeder::class);

    $response = $this->actingAs(moduleManager(), 'central')->post(route('central.modules.store'), [
        'name' => 'Billing',
        'slug' => 'billing',
        'sellable_as_addon' => '1',
        'prices' => [
            'Monthly' => ['enabled' => '1', 'price' => '9.00', 'currency' => 'USD'],
        ],
        'permissions' => ['Create', 'View'],
    ]);

    $response->assertRedirect(route('central.modules.index'));

    $module = Module::firstWhere('slug', 'billing');

    expect($module)->not->toBeNull()
        ->and($module->sellable_as_addon)->toBeTrue()
        ->and($module->prices()->count())->toBe(1)
        ->and($module->permissions()->pluck('slug')->sort()->values()->all())->toBe(['billing.create', 'billing.view']);
});

test('updating a module resyncs its permission checklist', function () {
    $this->seed(CentralAclSeeder::class);

    $module = Module::factory()->create(['slug' => 'crm-test']);
    $module->permissions()->create(['slug' => 'crm-test.create']);
    $module->permissions()->create(['slug' => 'crm-test.view']);

    $response = $this->actingAs(moduleManager(), 'central')->patch(route('central.modules.update', $module), [
        'name' => $module->name,
        'slug' => $module->slug,
        'sellable_as_addon' => '0',
        'prices' => [],
        'permissions' => ['Update'],
    ]);

    $response->assertRedirect(route('central.modules.index'));

    expect($module->permissions()->pluck('slug')->all())->toBe(['crm-test.update']);
});

test('toggling a module flips its active state', function () {
    $this->seed(CentralAclSeeder::class);

    $module = Module::factory()->create(['is_active' => true]);

    $this->actingAs(moduleManager(), 'central')->patch(route('central.modules.toggle-active', $module));

    expect($module->fresh()->is_active)->toBeFalse();
});

test('the modules index supports search, sort and pagination', function () {
    $this->seed(CentralAclSeeder::class);

    Module::factory()->count(20)->create();
    Module::factory()->create(['name' => 'Zzyzx Module']);

    $viewer = CentralUser::factory()->create();
    $viewer->givePermissionTo('central.modules.view');

    $searched = $this->actingAs($viewer, 'central')->get(route('central.modules.index', ['filter' => ['search' => 'Zzyzx']]));
    $searchedModules = $searched->inertiaProps('modules');
    expect($searchedModules['total'])->toBe(1)
        ->and($searchedModules['data'][0]['name'])->toBe('Zzyzx Module');

    $sorted = $this->actingAs($viewer, 'central')->get(route('central.modules.index', ['sort' => 'name']));
    $names = collect($sorted->inertiaProps('modules')['data'])->pluck('name');
    expect($names->all())->toBe($names->sort()->values()->all());

    $paginated = $this->actingAs($viewer, 'central')->get(route('central.modules.index'));
    expect($paginated->inertiaProps('modules')['data'])->toHaveCount(15);
});
