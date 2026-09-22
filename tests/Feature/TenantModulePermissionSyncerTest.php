<?php

use App\Models\Permission;
use App\Models\Module;
use App\Models\ModulePermission;
use App\Models\Tenant;
use App\Models\Role;
use App\Services\TenantModulePermissionSyncer;

test('syncing a module seeds its permissions into the tenant and grants them to the owner role', function () {
    $module = Module::factory()->create(['slug' => 'crm']);
    ModulePermission::factory()->create(['module_id' => $module->id, 'slug' => 'crm.create', 'label' => 'Crear crm']);
    ModulePermission::factory()->create(['module_id' => $module->id, 'slug' => 'crm.view']);

    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);

    app(TenantModulePermissionSyncer::class)->sync($tenant, $module);

    $tenant->run(function () {
        expect(Permission::where('name', 'crm.create')->where('guard_name', 'web')->exists())->toBeTrue()
            ->and(Permission::where('name', 'crm.create')->value('label'))->toBe('Crear crm')
            ->and(Permission::where('name', 'crm.view')->where('guard_name', 'web')->exists())->toBeTrue();

        $owner = Role::where('name', 'owner')->where('guard_name', 'web')->sole();

        expect($owner->hasPermissionTo('crm.create'))->toBeTrue()
            ->and($owner->hasPermissionTo('crm.view'))->toBeTrue();
    });

    $tenant->delete();
});

test('syncing the same module twice is idempotent', function () {
    $module = Module::factory()->create(['slug' => 'projects']);
    ModulePermission::factory()->create(['module_id' => $module->id, 'slug' => 'projects.create']);

    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);
    $syncer = app(TenantModulePermissionSyncer::class);

    $syncer->sync($tenant, $module);
    $syncer->sync($tenant, $module);

    $tenant->run(function () {
        expect(Permission::where('name', 'projects.create')->count())->toBe(1);
    });

    $tenant->delete();
});

test('manual command skips modules without entitlement unless forced', function () {
    $module = Module::factory()->create(['slug' => 'crm']);
    ModulePermission::factory()->create(['module_id' => $module->id, 'slug' => 'crm.create']);

    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);

    $this->artisan('tenants:sync-permissions', ['--all' => true, '--module' => ['crm']])
        ->expectsOutputToContain("Skipped [crm] for tenant [{$tenant->getTenantKey()}]")
        ->assertExitCode(0);

    $tenant->run(function () {
        expect(Permission::where('name', 'crm.create')->exists())->toBeFalse();
    });

    $this->artisan('tenants:sync-permissions', ['--all' => true, '--module' => ['crm'], '--force' => true])
        ->expectsOutputToContain("Synced [crm] permissions for tenant [{$tenant->getTenantKey()}]")
        ->assertExitCode(0);

    $tenant->run(function () {
        expect(Permission::where('name', 'crm.create')->exists())->toBeTrue();
    });

    $tenant->delete();
});
