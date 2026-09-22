<?php

namespace Modules\General\Permissions;

use App\Enums\Action;

/**
 * Declares the permission catalog for the General module — login, dashboard,
 * user management and role management, the baseline every tenant has
 * regardless of plan. Same shape as
 * Modules\Central\Permissions\CentralPermissions, consumed by a seeder that
 * populates the module_permissions catalog (blueprint), which
 * App\Services\TenantModulePermissionSyncer then uses to seed real
 * permissions into each tenant's own database.
 */
final class GeneralPermissions
{
    /**
     * @return array<string, array{label: string, actions: Action[], special?: array<string, string>}>
     */
    public static function resources(): array
    {
        return [
            // No "Create": tenant users aren't created from an admin form —
            // only listed and assigned roles. Matches UserController, which
            // has no create/store action.
            'users' => [
                'label' => 'Usuarios',
                'actions' => [Action::View(), Action::Update()],
            ],
            'roles' => [
                'label' => 'Roles',
                'actions' => [Action::View(), Action::Create(), Action::Update(), Action::Delete()],
            ],
        ];
    }
}
