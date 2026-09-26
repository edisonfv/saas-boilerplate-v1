<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * Estado de un ticket de soporte. "New" llega sin triar; "Open" ya está triado y en cola.
 *
 * @method static self New()
 * @method static self Open()
 * @method static self InProgress()
 * @method static self WaitingOnCustomer()
 * @method static self Resolved()
 * @method static self Closed()
 */
final class TicketStatus extends Enum
{
    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'New' => 'Nuevo',
            'Open' => 'Abierto',
            'InProgress' => 'En progreso',
            'WaitingOnCustomer' => 'Esperando al cliente',
            'Resolved' => 'Resuelto',
            'Closed' => 'Cerrado',
        ];
    }
}
