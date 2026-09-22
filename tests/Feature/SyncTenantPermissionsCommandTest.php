<?php

use App\Models\Module;
use App\Models\ModulePermission;
use App\Models\Tenant;
use App\Models\Permission;

test('the tenants:sync-permissions command can force sync a module to a specific tenant', function () {
    $module = Module::factory()->create(['slug' => 'inventory']);
    ModulePermission::factory()->create(['module_id' => $module->id, 'slug' => 'inventory.create']);

    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);

    $this->artisan('tenants:sync-permissions', [
        'tenant' => $tenant->getTenantKey(),
        '--module' => ['inventory'],
        '--force' => true,
    ])->assertSuccessful();

    $tenant->run(function () {
        expect(Permission::where('name', 'inventory.create')->exists())->toBeTrue();
    });

    $tenant->delete();
});

test('the command fails without a tenant id or --all', function () {
    $module = Module::factory()->create(['slug' => 'reports']);
    ModulePermission::factory()->create(['module_id' => $module->id, 'slug' => 'reports.view']);

    $this->artisan('tenants:sync-permissions', ['--module' => ['reports']])
        ->assertFailed();
});

test('the command fails for an unknown module slug', function () {
    $tenant = Tenant::create(['id' => 'tenant-'.uniqid()]);

    $this->artisan('tenants:sync-permissions', [
        'tenant' => $tenant->getTenantKey(),
        '--module' => ['does-not-exist'],
    ])->assertFailed();

    $tenant->delete();
});
