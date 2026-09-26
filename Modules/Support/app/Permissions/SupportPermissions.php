<?php

namespace Modules\Support\Permissions;

use App\Enums\Action;

/**
 * Tenant-side permission catalog of the sellable Support module. Seeded into
 * module_permissions by Database\Seeders\SupportModuleSeeder and synced into
 * a tenant's own ACL when the module is activated for it.
 *
 * "view-all" lets a tenant admin see every ticket of the company; without
 * it a user only sees the tickets they opened.
 */
final class SupportPermissions
{
    /**
     * @return array<string, array{label: string, actions: Action[], special?: array<string, string>}>
     */
    public static function resources(): array
    {
        return [
            'support-tickets' => [
                'label' => 'Tickets de soporte',
                'actions' => [Action::View(), Action::Create()],
                'special' => ['view-all' => 'Ver todos los tickets de la empresa'],
            ],
            'support-appointments' => [
                'label' => 'Citas de soporte',
                'actions' => [Action::View(), Action::Create()],
            ],
        ];
    }
}
