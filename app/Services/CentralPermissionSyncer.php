<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Str;
use Modules\Central\Permissions\CentralPermissions;

/**
 * Builds the central ACL catalog (guard: central) from
 * Modules\Central\Permissions\CentralPermissions::resources(), and grants
 * the full, current catalog to the super-admin role. Idempotent — safe to
 * run repeatedly (e.g. after a resource/action is added later).
 */
class CentralPermissionSyncer
{
    public function sync(): void
    {
        foreach (CentralPermissions::resources() as $resource => $definition) {
            foreach ($definition['actions'] as $action) {
                $this->upsert(
                    "central.{$resource}.".Str::lower((string) $action->value),
                    "{$action->label} ".Str::lower($definition['label']),
                );
            }

            foreach ($definition['special'] ?? [] as $key => $label) {
                $this->upsert("central.{$resource}.{$key}", $label);
            }
        }

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'central'])
            ->syncPermissions(Permission::where('guard_name', 'central')->get());
    }

    private function upsert(string $slug, string $label): void
    {
        Permission::updateOrCreate(
            ['name' => $slug, 'guard_name' => 'central'],
            ['label' => $label],
        );
    }
}
