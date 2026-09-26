<?php

namespace Modules\Central\Permissions;

use App\Enums\Action;

/**
 * Declares the central ACL catalog: which resources exist in the platform
 * admin panel, which subset of Action applies to each (no blind
 * cross-product — a resource only gets the actions it actually needs), and
 * any "special" permissions that don't follow the {resource}.{action}
 * pattern. Consumed by App\Services\CentralPermissionSyncer to build the
 * real Spatie permission rows (guard: central).
 */
final class CentralPermissions
{
    /**
     * @return array<string, array{label: string, actions: Action[], special?: array<string, string>}>
     */
    public static function resources(): array
    {
        return [
            'plans' => [
                'label' => 'Planes',
                'actions' => [Action::View(), Action::Create(), Action::Update()],
            ],
            'limit-types' => [
                'label' => 'Tipos de límite',
                'actions' => [Action::View(), Action::Create(), Action::Update()],
            ],
            'modules' => [
                'label' => 'Módulos',
                'actions' => [Action::View(), Action::Create(), Action::Update()],
            ],
            'features' => [
                'label' => 'Features',
                'actions' => [Action::View(), Action::Create(), Action::Update()],
            ],
            'tenants' => [
                'label' => 'Tenants',
                'actions' => [Action::View(), Action::Create(), Action::Update()],
                'special' => ['impersonate' => 'Impersonar tenants'],
            ],
            'staff' => [
                'label' => 'Staff',
                'actions' => [Action::View(), Action::Create(), Action::Update()],
            ],
            'roles' => [
                'label' => 'Roles',
                'actions' => [Action::View(), Action::Create(), Action::Update(), Action::Delete()],
            ],
            'support-tickets' => [
                'label' => 'Tickets de soporte',
                'actions' => [Action::View(), Action::Create(), Action::Update()],
                'special' => [
                    'assign' => 'Asignar tickets de soporte',
                    'billing' => 'Gestionar facturación de soporte',
                ],
            ],
            'support-schedule' => [
                'label' => 'Agenda y configuración de soporte',
                'actions' => [Action::View(), Action::Update()],
            ],
            'support-reports' => [
                'label' => 'Reportes de soporte',
                'actions' => [Action::View()],
            ],
            // Reservados: sin ruta/controlador todavía (no hay feature de
            // suscripciones ni billing construida), pero se sincronizan igual
            // para que el catálogo quede completo desde ya.
            'subscriptions' => [
                'label' => 'Suscripciones',
                'actions' => [Action::View()],
            ],
            'billing' => [
                'label' => 'Facturación',
                'actions' => [Action::View()],
            ],
        ];
    }
}
