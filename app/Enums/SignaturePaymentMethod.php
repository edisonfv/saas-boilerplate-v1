<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * How an end customer paid the tenant for a signature.
 *
 * @method static self BankTransfer()
 * @method static self Deposit()
 * @method static self Cash()
 */
final class SignaturePaymentMethod extends Enum
{
    /**
     * Methods a customer can report on their own (with a receipt).
     *
     * @return list<self>
     */
    public static function selfReported(): array
    {
        return [self::BankTransfer(), self::Deposit()];
    }

    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'BankTransfer' => 'Transferencia bancaria',
            'Deposit' => 'Depósito bancario',
            'Cash' => 'Efectivo',
        ];
    }
}
