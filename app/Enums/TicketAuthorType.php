<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * Quién escribió un mensaje o disparó un evento del ticket.
 *
 * @method static self Staff()
 * @method static self Requester()
 * @method static self System()
 */
final class TicketAuthorType extends Enum
{
    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'Staff' => 'Staff',
            'Requester' => 'Solicitante',
            'System' => 'Sistema',
        ];
    }
}
