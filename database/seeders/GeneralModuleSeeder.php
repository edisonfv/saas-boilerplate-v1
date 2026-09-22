<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModulePermission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\General\Permissions\GeneralPermissions;

/**
 * Registers the "general" module in the central catalog (login, dashboard,
 * users, roles) and its module_permissions blueprint. Unlike a business
 * module, it is not sellable_as_addon and is never attached via plan_module
 * — App\Services\TenantModulePermissionSyncer syncs it directly and
 * unconditionally for every tenant (see TenantDatabaseSeeder), since it is
 * baseline infrastructure every tenant has regardless of plan.
 */
class GeneralModuleSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['slug' => 'general'],
            ['name' => 'General', 'is_active' => true, 'sellable_as_addon' => false],
        );

        foreach (GeneralPermissions::resources() as $resource => $definition) {
            foreach ($definition['actions'] as $action) {
                $this->upsert(
                    $module,
                    "tenant.{$resource}.".Str::lower((string) $action->value),
                    "{$action->label} ".Str::lower($definition['label']),
                );
            }

            foreach ($definition['special'] ?? [] as $key => $label) {
                $this->upsert($module, "tenant.{$resource}.{$key}", $label);
            }
        }
    }

    private function upsert(Module $module, string $slug, string $label): void
    {
        ModulePermission::updateOrCreate(
            ['module_id' => $module->id, 'slug' => $slug],
            ['label' => $label],
        );
    }
}
