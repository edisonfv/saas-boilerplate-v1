<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * Where the issued certificate is delivered: a downloadable .p12 file or
 * kept in the provider's cloud (remote signing).
 *
 * @method static self File()
 * @method static self Cloud()
 */
final class SignatureContainer extends Enum
{
    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'File' => 'Archivo .p12',
            'Cloud' => 'En la nube',
        ];
    }
}
