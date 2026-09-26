<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * Prioridad de un ticket. Escala el SLA base del plan (ver App\Services\Support\SupportSla).
 *
 * @method static self Low()
 * @method static self Normal()
 * @method static self High()
 * @method static self Urgent()
 */
final class TicketPriority extends Enum
{
    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'Low' => 'Baja',
            'Normal' => 'Normal',
            'High' => 'Alta',
            'Urgent' => 'Urgente',
        ];
    }
}
