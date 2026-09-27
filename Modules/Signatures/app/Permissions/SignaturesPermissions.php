<?php

namespace Modules\Signatures\Permissions;

use App\Enums\Action;

/**
 * Tenant-side permission catalog of the sellable Signatures module. Seeded
 * into module_permissions by Database\Seeders\SignaturesModuleSeeder and
 * synced into a tenant's own ACL when the module is activated for it.
 *
 * "submit" is separate from "create" because sending a request to the
 * certification authority consumes the tenant's quota (units or credit).
 */
final class SignaturesPermissions
{
    /**
     * @return array<string, array{label: string, actions: Action[], special?: array<string, string>}>
     */
    public static function resources(): array
    {
        return [
            'signature-requests' => [
                'label' => 'Solicitudes de firma electrónica',
                'actions' => [Action::View(), Action::Create(), Action::Update(), Action::Delete()],
                'special' => ['submit' => 'Enviar solicitudes de firma a la entidad certificadora'],
            ],
            'signature-storefront' => [
                'label' => 'Sitio web de firmas',
                'actions' => [Action::Update()],
            ],
        ];
    }
}
