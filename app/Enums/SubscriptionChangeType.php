<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self Upgrade()
 * @method static self Downgrade()
 */
final class SubscriptionChangeType extends Enum
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
            'Upgrade' => 'Mejora de plan',
            'Downgrade' => 'Reducción de plan',
        ];
    }
}
