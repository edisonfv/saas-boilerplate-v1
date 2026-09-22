<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Services\TenantModulePermissionSyncer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class TenantDatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed a tenant's own database: base roles, and (locally) a demo owner
     * user. Run via config('tenancy.seeder_parameters') whenever a tenant is
     * created or re-seeded (php artisan tenants:seed).
     *
     * Runs already inside the tenant's own database context (stancl's
     * `tenants:seed` switches connection before calling this) — so the
     * "general" module catalog is read from central explicitly via
     * tenancy()->central(), then applied locally via
     * TenantModulePermissionSyncer::applyForCurrentTenant().
     *
     * GeneralModuleSeeder is called here (not just from DatabaseSeeder) so
     * that provisioning a tenant never depends on the central database
     * having been seeded beforehand — it's idempotent (firstOrCreate /
     * updateOrCreate throughout), safe to run on every tenant creation.
     */
    public function run(): void
    {
        $catalog = tenancy()->central(function () {
            $this->call(GeneralModuleSeeder::class);

            return Module::where('slug', 'general')->firstOrFail()->permissions()->get(['slug', 'label']);
        });

        app(TenantModulePermissionSyncer::class)->applyForCurrentTenant($catalog);

        Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
    }
}
