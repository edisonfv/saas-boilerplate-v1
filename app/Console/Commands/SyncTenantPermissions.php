<?php

namespace App\Console\Commands;

use App\Models\Module;
use App\Models\Tenant;
use App\Services\TenantEntitlements;
use App\Services\TenantModulePermissionSyncer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('tenants:sync-permissions {tenant? : Tenant ID} {--all : Sync for every tenant} {--module=* : Module slug(s) to sync} {--force : Sync even when the tenant does not currently have the module entitlement}')]
#[Description('Seed a tenant\'s ACL with the permissions declared by one or more modules, and grant them to the owner role.')]
class SyncTenantPermissions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(TenantModulePermissionSyncer $syncer, TenantEntitlements $entitlements): int
    {
        $moduleSlugs = $this->option('module');
        $force = (bool) $this->option('force');

        if (empty($moduleSlugs)) {
            $this->error('Pass at least one --module=<slug> to sync.');

            return self::FAILURE;
        }

        $modules = Module::whereIn('slug', $moduleSlugs)->get();

        if ($modules->count() !== count($moduleSlugs)) {
            $missing = collect($moduleSlugs)->diff($modules->pluck('slug'));
            $this->error("Unknown module slug(s): {$missing->implode(', ')}");

            return self::FAILURE;
        }

        $tenantId = $this->argument('tenant');

        if (! $this->option('all') && $tenantId === null) {
            $this->error('Pass a tenant ID or --all.');

            return self::FAILURE;
        }

        $tenantQuery = Tenant::query()
            ->when($tenantId !== null, fn ($query) => $query->whereKey($tenantId))
            ->orderBy('id');

        if (! $tenantQuery->exists()) {
            $this->error($tenantId !== null ? "Tenant [{$tenantId}] not found." : 'No tenants found.');

            return self::FAILURE;
        }

        $failures = 0;

        foreach ($tenantQuery->cursor() as $tenant) {
            if (! $tenant instanceof Tenant) {
                continue;
            }

            foreach ($modules as $module) {
                if (! $this->tenantCanReceiveModule($tenant, $module, $entitlements, $force)) {
                    $this->warn("Skipped [{$module->slug}] for tenant [{$tenant->getTenantKey()}]: module is not currently entitled. Use --force to override.");

                    continue;
                }

                try {
                    $syncer->sync($tenant, $module);
                    $this->info("Synced [{$module->slug}] permissions for tenant [{$tenant->getTenantKey()}].");
                } catch (Throwable $exception) {
                    $failures++;
                    report($exception);
                    $this->error("Failed syncing [{$module->slug}] for tenant [{$tenant->getTenantKey()}]: {$exception->getMessage()}");
                }
            }
        }

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function tenantCanReceiveModule(Tenant $tenant, Module $module, TenantEntitlements $entitlements, bool $force): bool
    {
        return $force
            || $module->slug === 'general'
            || $entitlements->activeModules($tenant)->contains($module->slug);
    }
}
