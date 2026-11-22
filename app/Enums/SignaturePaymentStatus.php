<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * Whether the end customer has paid the tenant for a signature request,
 * independent of its provider status. A request can only be sent to the
 * certification authority (consuming the tenant's quota) once Paid.
 *
 * @method static self Pending()
 * @method static self UnderReview()
 * @method static self Paid()
 */
final class SignaturePaymentStatus extends Enum
{
    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'Pending' => 'Pendiente de pago',
            'UnderReview' => 'Comprobante en revisión',
            'Paid' => 'Pagada',
        ];
    }
}
