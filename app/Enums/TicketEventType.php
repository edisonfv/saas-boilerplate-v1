<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * Entradas del historial auditable de un ticket.
 *
 * @method static self Created()
 * @method static self StatusChanged()
 * @method static self PriorityChanged()
 * @method static self Assigned()
 * @method static self TenantLinked()
 * @method static self BillingChanged()
 * @method static self Replied()
 * @method static self InternalNote()
 * @method static self TimeLogged()
 * @method static self Rated()
 * @method static self AppointmentBooked()
 * @method static self AppointmentUpdated()
 * @method static self AppointmentCancelled()
 * @method static self Reopened()
 */
final class TicketEventType extends Enum
{
    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'Created' => 'Creado',
            'StatusChanged' => 'Cambio de estado',
            'PriorityChanged' => 'Cambio de prioridad',
            'Assigned' => 'Asignado',
            'TenantLinked' => 'Tenant asociado',
            'BillingChanged' => 'Facturación actualizada',
            'Replied' => 'Respuesta',
            'InternalNote' => 'Nota interna',
            'TimeLogged' => 'Tiempo registrado',
            'Rated' => 'Calificado',
            'AppointmentBooked' => 'Cita agendada',
            'AppointmentUpdated' => 'Cita actualizada',
            'AppointmentCancelled' => 'Cita cancelada',
            'Reopened' => 'Reabierto',
        ];
    }
}
