<?php

use App\Models\CentralUser;
use Database\Seeders\CentralAclSeeder;
use App\Models\Permission;
use App\Models\Role;

function roleManager(): CentralUser
{
    /** @var CentralUser $user */
    $user = CentralUser::factory()->create();
    $user->givePermissionTo(['central.roles.view', 'central.roles.create', 'central.roles.update', 'central.roles.delete']);

    return $user;
}

test('a central user without permission cannot manage roles', function () {
    $this->seed(CentralAclSeeder::class);

    $user = CentralUser::factory()->create();

    $response = $this->actingAs($user, 'central')->get(route('central.roles.create'));

    $response->assertForbidden();
});

test('a role can be created with permissions', function () {
    $this->seed(CentralAclSeeder::class);

    $permissionIds = Permission::where('guard_name', 'central')->pluck('id')->take(2);

    $response = $this->actingAs(roleManager(), 'central')->post(route('central.roles.store'), [
        'name' => 'custom-editor',
        'permissions' => $permissionIds->all(),
    ]);

    $response->assertRedirect(route('central.roles.index'));

    $role = Role::where('name', 'custom-editor')->where('guard_name', 'central')->sole();

    expect($role->permissions()->count())->toBe(2);
});

test('editing a role resyncs its permissions', function () {
    $this->seed(CentralAclSeeder::class);

    $role = Role::create(['name' => 'custom-viewer', 'guard_name' => 'central']);
    $role->givePermissionTo('central.tenants.view');

    $newPermissionId = Permission::where('name', 'central.billing.view')->value('id');

    $response = $this->actingAs(roleManager(), 'central')->patch(route('central.roles.update', $role), [
        'name' => 'custom-viewer',
        'permissions' => [$newPermissionId],
    ]);

    $response->assertRedirect(route('central.roles.index'));

    expect($role->fresh()->permissions()->pluck('name')->all())->toBe(['central.billing.view']);
});

test('the super-admin role cannot be edited or deleted', function () {
    $this->seed(CentralAclSeeder::class);

    $role = Role::where('name', 'super-admin')->where('guard_name', 'central')->sole();
    $manager = roleManager();

    $this->actingAs($manager, 'central')->get(route('central.roles.edit', $role))->assertForbidden();
    $this->actingAs($manager, 'central')->patch(route('central.roles.update', $role), ['name' => 'renamed-admin', 'permissions' => []])->assertForbidden();
    $this->actingAs($manager, 'central')->delete(route('central.roles.destroy', $role))->assertForbidden();

    expect($role->fresh())->not->toBeNull();
});

test('a role assigned to users cannot be deleted', function () {
    $this->seed(CentralAclSeeder::class);

    $role = Role::create(['name' => 'custom-in-use', 'guard_name' => 'central']);
    $user = CentralUser::factory()->create();
    $user->assignRole($role);

    $response = $this->actingAs(roleManager(), 'central')->delete(route('central.roles.destroy', $role));

    $response->assertSessionHasErrors('role');
    expect(Role::where('name', 'custom-in-use')->exists())->toBeTrue();
});

test('an unused role can be deleted', function () {
    $this->seed(CentralAclSeeder::class);

    $role = Role::create(['name' => 'custom-unused', 'guard_name' => 'central']);

    $response = $this->actingAs(roleManager(), 'central')->delete(route('central.roles.destroy', $role));

    $response->assertRedirect(route('central.roles.index'));
    expect(Role::where('name', 'custom-unused')->exists())->toBeFalse();
});

test('the roles index supports search, sort and pagination', function () {
    $this->seed(CentralAclSeeder::class);

    foreach (range(1, 20) as $i) {
        Role::create(['name' => "role-{$i}", 'guard_name' => 'central']);
    }
    Role::create(['name' => 'zzyzx-role', 'guard_name' => 'central']);

    $viewer = roleManager();

    $searched = $this->actingAs($viewer, 'central')->get(route('central.roles.index', ['filter' => ['search' => 'zzyzx']]));
    $searchedRoles = $searched->inertiaProps('roles');
    expect($searchedRoles['total'])->toBe(1)
        ->and($searchedRoles['data'][0]['name'])->toBe('zzyzx-role');

    $sorted = $this->actingAs($viewer, 'central')->get(route('central.roles.index', ['sort' => 'name']));
    $names = collect($sorted->inertiaProps('roles')['data'])->pluck('name');
    expect($names->all())->toBe($names->sort()->values()->all());

    $paginated = $this->actingAs($viewer, 'central')->get(route('central.roles.index'));
    expect($paginated->inertiaProps('roles')['data'])->toHaveCount(15);
});
