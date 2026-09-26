<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * Tipo de solicitud que abre el cliente.
 *
 * @method static self Question()
 * @method static self Incident()
 * @method static self Request()
 * @method static self Training()
 * @method static self Billing()
 * @method static self Other()
 */
final class TicketCategory extends Enum
{
    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'Question' => 'Consulta',
            'Incident' => 'Incidente',
            'Request' => 'Requerimiento',
            'Training' => 'Capacitación',
            'Billing' => 'Facturación',
            'Other' => 'Otro',
        ];
    }
}
