<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * Estado de una cita de soporte agendada.
 *
 * @method static self Scheduled()
 * @method static self Completed()
 * @method static self Cancelled()
 * @method static self NoShow()
 */
final class AppointmentStatus extends Enum
{
    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'Scheduled' => 'Agendada',
            'Completed' => 'Atendida',
            'Cancelled' => 'Cancelada',
            'NoShow' => 'No asistió',
        ];
    }
}
