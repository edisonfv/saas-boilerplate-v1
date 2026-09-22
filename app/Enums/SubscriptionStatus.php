<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self Trialing()
 * @method static self Active()
 * @method static self PastDue()
 * @method static self Cancelled()
 * @method static self Expired()
 */
final class SubscriptionStatus extends Enum
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
            'Trialing' => 'En prueba',
            'Active' => 'Activa',
            'PastDue' => 'Pago pendiente',
            'Cancelled' => 'Cancelada',
            'Expired' => 'Expirada',
        ];
    }
}
