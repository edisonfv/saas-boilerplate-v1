<?php

namespace Modules\Signatures\Permissions;

use App\Enums\Action;

/**
 * Tenant-side permission catalog of the sellable Signatures module. Seeded
 * into module_permissions by Database\Seeders\SignaturesModuleSeeder and
 * synced into a tenant's own ACL when the module is activated for it.
 *
 * "submit" is separate from "create" because sending a request to the
 * certification authority consumes the tenant's quota (units or credit);
 * "payments" (confirming the customer paid) is separate too, so a cashier
 * and an operator can hold different duties.
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
                'special' => [
                    'submit' => 'Enviar solicitudes de firma a la entidad certificadora',
                    'payments' => 'Registrar, confirmar y rechazar pagos; crear enlaces prepagados',
                    'settlement' => 'Ver y exportar la liquidación y rentabilidad de la venta de firmas',
                ],
            ],
            'signature-storefront' => [
                'label' => 'Sitio web de firmas',
                'actions' => [Action::Update()],
            ],
        ];
    }
}
