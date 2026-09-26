<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * Cómo se cobra un ticket. "Included" consume las horas del plan y solo el excedente se factura a la tarifa global.
 *
 * @method static self Included()
 * @method static self Hourly()
 * @method static self Fixed()
 * @method static self NonBillable()
 */
final class TicketBillingMode extends Enum
{
    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'Included' => 'Incluido en el plan',
            'Hourly' => 'Por horas',
            'Fixed' => 'Monto fijo',
            'NonBillable' => 'No facturable',
        ];
    }
}
