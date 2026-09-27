<?php

namespace Database\Seeders;

use App\Models\CentralUser;
use App\Models\Permission;
use App\Models\Role;
use App\Services\CentralPermissionSyncer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CentralAclSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the platform's own roles and permissions (guard: central).
     *
     * Independent from the module permission catalog (App\Models\ModulePermission)
     * and from each tenant's own ACL — see
     * docs/architecture/plans-modules-permissions.md section 5.
     */
    public function run(): void
    {
        app(CentralPermissionSyncer::class)->sync();

        $byName = fn (array $names) => Permission::where('guard_name', 'central')->whereIn('name', $names)->get();

        Role::firstOrCreate(['name' => 'support', 'guard_name' => 'central'])
            ->syncPermissions($byName([
                'central.tenants.view', 'central.tenants.impersonate',
                'central.support-tickets.view', 'central.support-tickets.create', 'central.support-tickets.update',
                'central.support-tickets.assign', 'central.support-schedule.view', 'central.support-reports.view',
            ]));

        Role::firstOrCreate(['name' => 'billing', 'guard_name' => 'central'])
            ->syncPermissions($byName([
                'central.plans.view', 'central.plans.create', 'central.plans.update',
                'central.subscriptions.view',
                'central.billing.view',
                'central.tenants.view', 'central.tenants.create', 'central.tenants.update',
                'central.support-tickets.view', 'central.support-tickets.billing', 'central.support-reports.view',
                'central.signature-products.view', 'central.signature-products.create', 'central.signature-products.update',
                'central.signature-accounts.view', 'central.signature-accounts.update', 'central.signature-accounts.transactions',
            ]));

        Role::firstOrCreate(['name' => 'sales', 'guard_name' => 'central'])
            ->syncPermissions($byName([
                'central.tenants.view',
                'central.signature-products.view', 'central.signature-accounts.view',
            ]));

        $admin = CentralUser::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Platform Admin', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );
        $admin->assignRole('super-admin');
    }
}
