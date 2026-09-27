<?php

namespace App\Enums;

use Spatie\Enum\Laravel\Enum;

/**
 * Movement in a tenant's signature ledger (append-only). Units and amounts
 * are signed: a purchase adds units, a consumption removes a unit (prepaid)
 * or adds debt (credit), a refund reverses a consumption, a payment lowers
 * the credit used, and an adjustment is a manual correction by staff.
 *
 * @method static self PackagePurchase()
 * @method static self Consumption()
 * @method static self Refund()
 * @method static self Payment()
 * @method static self Adjustment()
 */
final class SignatureLedgerEntryType extends Enum
{
    /**
     * @return array<string, string>
     */
    protected static function labels(): array
    {
        return [
            'PackagePurchase' => 'Compra de paquete',
            'Consumption' => 'Consumo',
            'Refund' => 'Reverso',
            'Payment' => 'Abono',
            'Adjustment' => 'Ajuste manual',
        ];
    }
}
