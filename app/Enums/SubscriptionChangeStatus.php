<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self Pending()
 * @method static self Applied()
 */
final class SubscriptionChangeStatus extends Enum
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
            'Pending' => 'Pendiente',
            'Applied' => 'Aplicado',
        ];
    }
}
