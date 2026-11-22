<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * Review outcome of one recorded payment (a receipt the customer uploaded,
 * or a payment staff registered directly, which is Approved at once).
 *
 * @method static self Pending()
 * @method static self Approved()
 * @method static self Rejected()
 */
final class SignaturePaymentReview extends Enum
{
    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'Pending' => 'Por revisar',
            'Approved' => 'Confirmado',
            'Rejected' => 'Rechazado',
        ];
    }
}
