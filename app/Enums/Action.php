<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self Create()
 * @method static self View()
 * @method static self Update()
 * @method static self Delete()
 * @method static self Manage()
 * @method static self Export()
 */
final class Action extends Enum
{
    /**
     * Display labels for the frontend. Never compare against these — use
     * ->equals()/->value for that. Labels are for presentation only.
     *
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'Create' => 'Crear',
            'View' => 'Ver',
            'Update' => 'Actualizar',
            'Delete' => 'Eliminar',
            'Manage' => 'Gestionar',
            'Export' => 'Exportar',
        ];
    }
}
