<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\Permission;
use App\Models\Role;

function createRoleTestTenant(): array
{
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);
    $domain = $tenant->id.'.tenant-role-test.local';
    $tenant->createDomain($domain);

    return [$tenant, $domain];
}

function createRoleTestOwner(): User
{
    $owner = User::factory()->create();
    $owner->assignRole('owner');

    return $owner;
}

afterEach(function () {
    tenancy()->end();
});

test('a tenant user without permission cannot manage roles', function () {
    [$tenant, $domain] = createRoleTestTenant();

    $user = $tenant->run(fn () => User::factory()->create(['password' => bcrypt('password')]));

    $response = $this->actingAs($user, 'web')->get("http://{$domain}/roles/create");

    $response->assertForbidden();

    $tenant->delete();
});

test('a role can be created with permissions synced from a subscribed module', function () {
    [$tenant, $domain] = createRoleTestTenant();

    $owner = $tenant->run(function () {
        Permission::firstOrCreate(['name' => 'crm.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'crm.create', 'guard_name' => 'web']);

        return createRoleTestOwner();
    });

    $permissionIds = $tenant->run(fn () => Permission::where('guard_name', 'web')->pluck('id')->take(2));

    $response = $this->actingAs($owner, 'web')->post("http://{$domain}/roles", [
        'name' => 'crm-agent',
        'permissions' => $permissionIds->all(),
    ]);

    $response->assertRedirect("http://{$domain}/roles");

    $tenant->run(function () {
        $role = Role::where('name', 'crm-agent')->where('guard_name', 'web')->sole();
        expect($role->permissions()->count())->toBe(2);
    });

    $tenant->delete();
});

test('the owner role cannot be edited or deleted', function () {
    [$tenant, $domain] = createRoleTestTenant();

    $owner = $tenant->run(fn () => createRoleTestOwner());
    $ownerRoleId = $tenant->run(fn () => Role::where('name', 'owner')->where('guard_name', 'web')->value('id'));

    $this->actingAs($owner, 'web')->get("http://{$domain}/roles/{$ownerRoleId}/edit")->assertForbidden();
    $this->actingAs($owner, 'web')->patch("http://{$domain}/roles/{$ownerRoleId}", ['name' => 'renamed-owner', 'permissions' => []])->assertForbidden();
    $this->actingAs($owner, 'web')->delete("http://{$domain}/roles/{$ownerRoleId}")->assertForbidden();

    $tenant->run(function () {
        expect(Role::where('name', 'owner')->where('guard_name', 'web')->exists())->toBeTrue();
    });

    $tenant->delete();
});

test('a role assigned to a tenant user cannot be deleted', function () {
    [$tenant, $domain] = createRoleTestTenant();

    $owner = $tenant->run(function () {
        $role = Role::create(['name' => 'in-use-role', 'guard_name' => 'web']);
        $member = User::factory()->create();
        $member->assignRole($role);

        return createRoleTestOwner();
    });

    $roleId = $tenant->run(fn () => Role::where('name', 'in-use-role')->value('id'));

    $response = $this->actingAs($owner, 'web')->delete("http://{$domain}/roles/{$roleId}");

    $response->assertSessionHasErrors('role');
    $tenant->run(function () {
        expect(Role::where('name', 'in-use-role')->exists())->toBeTrue();
    });

    $tenant->delete();
});

test('the roles index only shows the baseline tenant permissions for a tenant with no modules', function () {
    [$tenant, $domain] = createRoleTestTenant();

    $owner = $tenant->run(fn () => createRoleTestOwner());

    $response = $this->actingAs($owner, 'web')->get("http://{$domain}/roles/create");

    $response->assertOk();

    $groups = $response->inertiaProps('permissions');
    expect($groups)->toHaveCount(2);

    $allPermissionNames = collect($groups)->flatMap(fn (array $group) => collect($group['items'])->pluck('name'))
        ->sort()->values()->all();

    expect($allPermissionNames)->toBe([
        'tenant.roles.create', 'tenant.roles.delete', 'tenant.roles.update', 'tenant.roles.view',
        'tenant.users.update', 'tenant.users.view',
    ]);

    $tenant->delete();
});

test('the roles index supports search, sort and pagination', function () {
    [$tenant, $domain] = createRoleTestTenant();

    $owner = $tenant->run(function () {
        foreach (range(1, 20) as $i) {
            Role::create(['name' => "role-{$i}", 'guard_name' => 'web']);
        }
        Role::create(['name' => 'zzyzx-role', 'guard_name' => 'web']);

        return createRoleTestOwner();
    });

    $searched = $this->actingAs($owner, 'web')->get("http://{$domain}/roles?filter[search]=zzyzx");
    $searchedRoles = $searched->inertiaProps('roles');
    expect($searchedRoles['total'])->toBe(1)
        ->and($searchedRoles['data'][0]['name'])->toBe('zzyzx-role');

    $sorted = $this->actingAs($owner, 'web')->get("http://{$domain}/roles?sort=name");
    $names = collect($sorted->inertiaProps('roles')['data'])->pluck('name');
    expect($names->all())->toBe($names->sort()->values()->all());

    $paginated = $this->actingAs($owner, 'web')->get("http://{$domain}/roles");
    expect($paginated->inertiaProps('roles')['data'])->toHaveCount(15);

    $tenant->delete();
});
