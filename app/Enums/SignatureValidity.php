<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * Validity period of an electronic signature certificate.
 *
 * @method static self SevenDays()
 * @method static self ThirtyDays()
 * @method static self OneYear()
 * @method static self TwoYears()
 * @method static self ThreeYears()
 * @method static self FourYears()
 * @method static self FiveYears()
 */
final class SignatureValidity extends Enum
{
    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'SevenDays' => '7 días',
            'ThirtyDays' => '30 días',
            'OneYear' => '1 año',
            'TwoYears' => '2 años',
            'ThreeYears' => '3 años',
            'FourYears' => '4 años',
            'FiveYears' => '5 años',
        ];
    }
}
