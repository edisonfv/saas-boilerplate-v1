<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * Sex as printed on the applicant's identity document.
 *
 * @method static self Male()
 * @method static self Female()
 */
final class Gender extends Enum
{
    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'Male' => 'Hombre',
            'Female' => 'Mujer',
        ];
    }
}
