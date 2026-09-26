<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * Estado de cobro de un ticket.
 *
 * @method static self NotApplicable()
 * @method static self Pending()
 * @method static self Invoiced()
 */
final class TicketBillingStatus extends Enum
{
    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'NotApplicable' => 'No aplica',
            'Pending' => 'Pendiente de facturar',
            'Invoiced' => 'Facturado',
        ];
    }
}
