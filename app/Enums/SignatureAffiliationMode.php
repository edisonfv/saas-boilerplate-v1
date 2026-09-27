<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * How a tenant (distributor) pays for the electronic signatures it sells.
 * Credit: each issued signature is charged at the product's unit price
 * against a credit line (cupo). Prepaid: the tenant buys packages of
 * signatures in advance and can only sell the units it holds.
 *
 * @method static self Credit()
 * @method static self Prepaid()
 */
final class SignatureAffiliationMode extends Enum
{
    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'Credit' => 'Crédito',
            'Prepaid' => 'Prepago',
        ];
    }
}
