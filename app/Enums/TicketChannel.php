<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * Por dónde entró el ticket.
 *
 * @method static self TenantPanel()
 * @method static self PublicWeb()
 * @method static self TenantPublic()
 * @method static self Staff()
 */
final class TicketChannel extends Enum
{
    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'TenantPanel' => 'Panel del tenant',
            'PublicWeb' => 'Formulario público',
            'TenantPublic' => 'Formulario en login del tenant',
            'Staff' => 'Registrado por staff',
        ];
    }
}
