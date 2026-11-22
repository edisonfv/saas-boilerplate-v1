<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self Cedula()
 * @method static self Passport()
 */
final class IdentityDocumentType extends Enum
{
    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'Cedula' => 'Cédula',
            'Passport' => 'Pasaporte',
        ];
    }
}
