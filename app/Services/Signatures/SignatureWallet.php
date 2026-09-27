<?php

namespace App\Services\Signatures;

use App\Enums\SignatureAffiliationMode;
use App\Enums\SignatureLedgerEntryType;
use App\Exceptions\SignatureQuotaExceeded;
use App\Models\CentralUser;
use App\Models\SignatureAccount;
use App\Models\SignatureBalance;
use App\Models\SignatureLedgerEntry;
use App\Models\SignaturePackage;
use App\Models\SignatureProduct;
use App\Models\Tenant;
use Closure;
use DomainException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/**
 * The only writer of a tenant's signature quota (central database).
 *
 * Every movement is an append-only SignatureLedgerEntry; the running totals
 * (`signature_accounts.credit_used`, `signature_balances.available_units`)
 * are updated in the same transaction while holding a lock on the account
 * row, so two operators selling at once can never overdraw the quota.
 *
 * - Prepaid: packages add units per product; each sale consumes one unit.
 * - Credit: each sale adds the product's unit price to `credit_used`, and
 *   is refused once it would exceed `credit_limit`; payments free it up.
 */
class SignatureWallet
{
    /**
     * Create or reconfigure a tenant's distributor account.
     */
    public function configure(
        Tenant $tenant,
        SignatureAffiliationMode $mode,
        string|int|float $creditLimit = 0,
        bool $isActive = true,
        ?string $notes = null,
    ): SignatureAccount {
        return SignatureAccount::query()->updateOrCreate(
            ['tenant_id' => $tenant->getTenantKey()],
            [
                'affiliation_mode' => $mode,
                'credit_limit' => Money::fromCents(Money::toCents($creditLimit)),
                'is_active' => $isActive,
                'notes' => $notes,
            ],
        );
    }

    /**
     * Sell a prepaid package to the tenant: credits its units.
     */
    public function purchasePackage(
        SignatureAccount $account,
        SignaturePackage $package,
        ?string $reference = null,
        ?CentralUser $staff = null,
    ): SignatureLedgerEntry {
        if ($account->isCredit()) {
            throw new DomainException('Los paquetes solo aplican a cuentas en modalidad prepago.');
        }

        return $this->locked($account, function (SignatureAccount $account) use ($package, $reference, $staff) {
            $this->balanceFor($account, $package->signature_product_id)->increment('available_units', $package->quantity);

            return $account->ledgerEntries()->create([
                'signature_product_id' => $package->signature_product_id,
                'signature_package_id' => $package->id,
                'type' => SignatureLedgerEntryType::PackagePurchase(),
                'units' => $package->quantity,
                'amount' => $package->price,
                'reference' => $reference,
                'description' => "Paquete «{$package->name}»",
                'created_by' => $staff?->getKey(),
            ]);
        });
    }

    /**
     * Register a payment from a tenant on credit: lowers its used credit.
     */
    public function recordPayment(
        SignatureAccount $account,
        string|int|float $amount,
        ?string $reference = null,
        ?CentralUser $staff = null,
    ): SignatureLedgerEntry {
        $cents = Money::toCents($amount);

        if ($cents <= 0) {
            throw new DomainException('El abono debe ser mayor a cero.');
        }

        return $this->locked($account, function (SignatureAccount $account) use ($cents, $reference, $staff) {
            $account->forceFill([
                'credit_used' => Money::fromCents(Money::toCents($account->credit_used) - $cents),
            ])->save();

            return $account->ledgerEntries()->create([
                'type' => SignatureLedgerEntryType::Payment(),
                'units' => 0,
                'amount' => Money::fromCents(-$cents),
                'reference' => $reference,
                'description' => 'Abono al crédito',
                'created_by' => $staff?->getKey(),
            ]);
        });
    }

    /**
     * Manual correction of prepaid units (e.g. courtesy units, or fixing a
     * mistaken sale). Can't leave the balance negative.
     */
    public function adjustUnits(
        SignatureAccount $account,
        SignatureProduct $product,
        int $units,
        string $reason,
        ?CentralUser $staff = null,
    ): SignatureLedgerEntry {
        if ($units === 0) {
            throw new DomainException('El ajuste debe ser distinto de cero.');
        }

        return $this->locked($account, function (SignatureAccount $account) use ($product, $units, $reason, $staff) {
            $balance = $this->balanceFor($account, $product->id);

            if ($balance->available_units + $units < 0) {
                throw new DomainException('El ajuste dejaría el saldo de firmas en negativo.');
            }

            $balance->increment('available_units', $units);

            return $account->ledgerEntries()->create([
                'signature_product_id' => $product->id,
                'type' => SignatureLedgerEntryType::Adjustment(),
                'units' => $units,
                'amount' => 0,
                'description' => $reason,
                'created_by' => $staff?->getKey(),
            ]);
        });
    }

    /**
     * Throws when the tenant couldn't sell one more signature of the
     * product right now. A cheap pre-check for the UI; consume() re-checks
     * under lock.
     */
    public function assertCanSell(?SignatureAccount $account, SignatureProduct $product): void
    {
        if ($account === null) {
            throw SignatureQuotaExceeded::noAccount();
        }

        $this->guardQuota($account, $product);
    }

    /**
     * Take one signature from the tenant's quota for a sale.
     *
     * @throws SignatureQuotaExceeded
     */
    public function consume(SignatureAccount $account, SignatureProduct $product, string $reference): SignatureLedgerEntry
    {
        return $this->locked($account, function (SignatureAccount $account) use ($product, $reference) {
            $this->guardQuota($account, $product);

            if ($account->isCredit()) {
                $account->forceFill([
                    'credit_used' => Money::fromCents(
                        Money::toCents($account->credit_used) + Money::toCents($product->credit_unit_price),
                    ),
                ])->save();
            } else {
                $this->balanceFor($account, $product->id)->decrement('available_units');
            }

            return $account->ledgerEntries()->create([
                'signature_product_id' => $product->id,
                'type' => SignatureLedgerEntryType::Consumption(),
                'units' => $account->isCredit() ? 0 : -1,
                'amount' => $account->isCredit() ? $product->credit_unit_price : 0,
                'reference' => $reference,
                'description' => "Venta de «{$product->name}»",
            ]);
        });
    }

    /**
     * Give back a consumed signature (failed submission, rejected or
     * cancelled request). Idempotent: a consumption is reversed at most
     * once; returns null when it already was.
     */
    public function refund(SignatureLedgerEntry $consumption, string $reason): ?SignatureLedgerEntry
    {
        if (! $consumption->type->equals(SignatureLedgerEntryType::Consumption())) {
            throw new DomainException('Solo se pueden reversar consumos.');
        }

        return $this->locked($consumption->account, function (SignatureAccount $account) use ($consumption, $reason) {
            if ($consumption->reversal()->exists()) {
                return null;
            }

            if ($consumption->units !== 0 && $consumption->signature_product_id !== null) {
                $this->balanceFor($account, $consumption->signature_product_id)
                    ->increment('available_units', -$consumption->units);
            }

            if (Money::toCents($consumption->amount) !== 0) {
                $account->forceFill([
                    'credit_used' => Money::fromCents(
                        Money::toCents($account->credit_used) - Money::toCents($consumption->amount),
                    ),
                ])->save();
            }

            return $account->ledgerEntries()->create([
                'signature_product_id' => $consumption->signature_product_id,
                'type' => SignatureLedgerEntryType::Refund(),
                'units' => -$consumption->units,
                'amount' => Money::fromCents(-Money::toCents($consumption->amount)),
                'reference' => $consumption->reference,
                'description' => $reason,
                'reverses_entry_id' => $consumption->id,
            ]);
        });
    }

    /**
     * How many more signatures of the product the tenant can sell now.
     */
    public function sellableUnits(SignatureAccount $account, SignatureProduct $product): int
    {
        if (! $account->is_active) {
            return 0;
        }

        if ($account->isCredit()) {
            $price = Money::toCents($product->credit_unit_price);

            return $price > 0 ? intdiv($account->availableCreditCents(), $price) : PHP_INT_MAX;
        }

        return max(0, (int) $account->balances()->where('signature_product_id', $product->id)->value('available_units'));
    }

    private function guardQuota(SignatureAccount $account, SignatureProduct $product): void
    {
        if (! $account->is_active) {
            throw SignatureQuotaExceeded::inactiveAccount();
        }

        if ($account->isCredit()) {
            if (Money::toCents($product->credit_unit_price) > $account->availableCreditCents()) {
                throw SignatureQuotaExceeded::creditExhausted(
                    Money::fromCents($account->availableCreditCents()),
                    (string) $product->credit_unit_price,
                );
            }

            return;
        }

        if ($this->balanceFor($account, $product->id)->available_units < 1) {
            throw SignatureQuotaExceeded::noUnits($product->name);
        }
    }

    private function balanceFor(SignatureAccount $account, string $productId): SignatureBalance
    {
        return $account->balances()->firstOrCreate(
            ['signature_product_id' => $productId],
            ['available_units' => 0],
        );
    }

    /**
     * Run $callback in a central transaction holding the account row lock,
     * with a fresh copy of the account.
     *
     * @template TResult
     *
     * @param  Closure(SignatureAccount): TResult  $callback
     * @return TResult
     */
    private function locked(SignatureAccount $account, Closure $callback): mixed
    {
        return $this->central()->transaction(function () use ($account, $callback) {
            /** @var SignatureAccount $fresh */
            $fresh = SignatureAccount::query()->lockForUpdate()->findOrFail($account->getKey());

            $result = $callback($fresh);

            $account->setRawAttributes($fresh->getAttributes(), true);

            return $result;
        });
    }

    private function central(): ConnectionInterface
    {
        return DB::connection(config('tenancy.database.central_connection'));
    }
}
