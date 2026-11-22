<?php

namespace App\Services\Signatures;

/**
 * Exact arithmetic for the signature ledger's decimal(…, 2) amounts:
 * compute in integer cents, store as "12.34" strings. Avoids float drift
 * without requiring the bcmath extension.
 */
final class Money
{
    public static function toCents(string|int|float|null $amount): int
    {
        return (int) round(((float) ($amount ?? 0)) * 100);
    }

    public static function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
