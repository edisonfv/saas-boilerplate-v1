<?php

use App\Models\CentralUser;
use App\Models\Module;
use App\Models\ModulePermission;
use Database\Seeders\CentralAclSeeder;
use App\Models\Permission;
use App\Models\Role;

test('a central user assigned a role inherits its permissions on the central guard', function () {
    $this->seed(CentralAclSeeder::class);

    $user = CentralUser::factory()->create();
    $user->assignRole('support');

    expect($user->hasRole('support'))->toBeTrue()
        ->and($user->can('central.tenants.view'))->toBeTrue()
        ->and($user->can('central.tenants.impersonate'))->toBeTrue()
        ->and($user->can('central.plans.view'))->toBeFalse();
});

test('super-admin receives every seeded central permission', function () {
    $this->seed(CentralAclSeeder::class);

    $role = Role::where('name', 'super-admin')->where('guard_name', 'central')->sole();

    expect($role->permissions()->count())->toBe(Permission::where('guard_name', 'central')->count());
});

test('the central ACL is independent from the module permission catalog', function () {
    $module = Module::factory()->create(['slug' => 'crm']);

    ModulePermission::factory()->create(['module_id' => $module->id, 'slug' => 'crm.create']);
    Permission::create(['name' => 'crm.create', 'guard_name' => 'central']);

    expect(ModulePermission::where('slug', 'crm.create')->count())->toBe(1)
        ->and(Permission::where('name', 'crm.create')->where('guard_name', 'central')->count())->toBe(1);
});
