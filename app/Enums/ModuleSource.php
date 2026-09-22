<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self Plan()
 * @method static self Addon()
 */
final class ModuleSource extends Enum
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
            'Plan' => 'Incluido en el plan',
            'Addon' => 'Addon contratado aparte',
        ];
    }
}
