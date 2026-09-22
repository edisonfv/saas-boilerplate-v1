<?php

namespace App\Services;

use App\Models\Module;
use App\Models\ModulePermission;
use App\Models\Tenant;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds a tenant's own ACL with the permissions a module declares in the
 * central catalog (App\Models\ModulePermission), and grants them to the
 * tenant's "owner" role. Idempotent — safe to run repeatedly.
 *
 * Invoked by the SubscriptionModuleChanged listener and by the manual
 * tenants:sync-permissions command for catalog backfills.
 */
class TenantModulePermissionSyncer
{
    /**
     * Call from central context (default connection). Reads the module's
     * catalog centrally, then switches into the tenant's own database to
     * apply it.
     */
    public function sync(Tenant $tenant, Module $module): void
    {
        $catalog = $module->permissions()->get(['slug', 'label']);

        $tenant->run(fn () => $this->apply($catalog));
    }

    /**
     * Call when already inside the tenant's own database context (e.g. from
     * a tenant seeder run via stancl/tenancy's `tenants:seed`, which already
     * switches connection before running). $catalog must have been read from
     * central beforehand — e.g. via `tenancy()->central(fn () => ...)`.
     *
     * @param  Collection<int, ModulePermission>  $catalog
     */
    public function applyForCurrentTenant(Collection $catalog): void
    {
        $this->apply($catalog);
    }

    /**
     * @param  Collection<int, ModulePermission>  $catalog
     */
    private function apply(Collection $catalog): void
    {
        DB::transaction(function () use ($catalog): void {
            $newPermissions = $catalog->map(
                fn (ModulePermission $permission) => Permission::updateOrCreate(
                    ['name' => $permission->slug, 'guard_name' => 'web'],
                    ['label' => $permission->label],
                )
            );

            $owner = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
            $owner->givePermissionTo($newPermissions);
        }, attempts: 3);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
