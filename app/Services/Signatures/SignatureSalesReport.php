<?php

namespace App\Services\Signatures;

use App\Enums\SignatureLedgerEntryType;
use App\Enums\SignatureRequestStatus;
use App\Models\SignatureAccount;
use App\Models\SignatureBalance;
use App\Models\SignatureLedgerEntry;
use App\Models\SignatureProduct;
use App\Models\SignatureProviderRequest;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Central's view of the resale business (Uanataca → central → distributor
 * → end customer), built from the sales index (`signature_provider_requests`,
 * whose economics are snapshotted at sale time) and the quota ledger.
 *
 * Per sale: central's revenue is `unit_price`, its profit `unit_price -
 * unit_cost`, and the distributor's profit `sale_price - unit_price`.
 * Refunded sales (rejected/cancelled or reversed) don't count. Sums are
 * done in integer cents in PHP so the report behaves the same on every
 * database engine.
 */
class SignatureSalesReport
{
    /** Share of the credit line above which an account needs attention. */
    public const CreditWarningRatio = 0.8;

    /** Prepaid balance (all products) at or below which an account needs attention. */
    public const LowStockUnits = 5;

    /** Days without sales after which an active distributor needs attention. */
    public const IdleDays = 30;

    /**
     * @return array<string, int|float|string|null>
     */
    public function summary(CarbonImmutable $from, CarbonImmutable $to, ?string $tenantId = null, ?string $productId = null): array
    {
        $rows = $this->sales($from, $to, $tenantId, $productId);

        return [
            ...$this->totals($rows),
            'issued' => $rows->filter(fn (object $sale) => $sale->status === SignatureRequestStatus::Issued()->value)->count(),
            'in_progress' => $rows->filter(fn (object $sale) => $sale->status !== SignatureRequestStatus::Issued()->value)->count(),
            'refunded' => $this->scoped(SignatureProviderRequest::query(), $from, $to, $tenantId, $productId)
                ->whereHas('consumption', fn (Builder $consumption) => $consumption->has('reversal'))
                ->count(),
        ];
    }

    /**
     * Sales, revenue and margins of each distributor in the period, best first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function byTenant(CarbonImmutable $from, CarbonImmutable $to, ?string $productId = null): Collection
    {
        $rows = $this->sales($from, $to, null, $productId);
        $tenants = Tenant::query()->whereIn('id', $rows->pluck('tenant_id')->unique())->get()->keyBy('id');
        $totalUnits = max(1, $rows->count());

        return $rows->groupBy('tenant_id')
            ->map(fn (Collection $sales, string $tenantId) => [
                'tenant_id' => $tenantId,
                'tenant_name' => $tenants->get($tenantId)?->company_name ?? $tenantId,
                ...$this->totals($sales),
                'share' => round($sales->count() / $totalUnits * 100, 1),
            ])
            ->sortByDesc('units')
            ->values();
    }

    /**
     * Sales and prices per product in the period, next to the catalog prices.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function byProduct(CarbonImmutable $from, CarbonImmutable $to, ?string $tenantId = null): Collection
    {
        $rows = $this->sales($from, $to, $tenantId);
        $products = SignatureProduct::query()->whereIn('id', $rows->pluck('signature_product_id')->filter()->unique())->get()->keyBy('id');

        return $rows->groupBy(fn (object $sale) => $sale->signature_product_id ?? '')
            ->map(function (Collection $sales, string $productId) use ($products) {
                $product = $products->get($productId);
                $retail = $sales->whereNotNull('sale_price');

                return [
                    'product_id' => $productId,
                    'product_name' => $product?->name ?? 'Producto eliminado',
                    ...$this->totals($sales),
                    'min_retail_price' => $product?->min_retail_price,
                    'max_sale_price' => $retail->isEmpty() ? null : Money::fromCents($retail->max(fn (object $sale) => Money::toCents($sale->sale_price))),
                    'min_sale_price' => $retail->isEmpty() ? null : Money::fromCents($retail->min(fn (object $sale) => Money::toCents($sale->sale_price))),
                    'suggested_retail_price' => $product?->suggested_retail_price,
                ];
            })
            ->sortByDesc('units')
            ->values();
    }

    /**
     * Units, revenue and profit of each of the last `$months` months up to `$until`.
     *
     * @return list<array<string, mixed>>
     */
    public function monthlyTrend(CarbonImmutable $until, int $months = 12, ?string $tenantId = null, ?string $productId = null): array
    {
        $start = $until->startOfMonth()->subMonthsNoOverflow($months - 1);
        $byMonth = $this->sales($start, $until->endOfMonth(), $tenantId, $productId)
            ->groupBy(fn (object $sale) => CarbonImmutable::parse($sale->created_at)->format('Y-m'));

        return collect(range(0, $months - 1))
            ->map(function (int $offset) use ($start, $byMonth) {
                $month = $start->addMonthsNoOverflow($offset);
                $totals = $this->totals($byMonth[$month->format('Y-m')] ?? collect());

                return [
                    'month' => $month->format('Y-m'),
                    'label' => ucfirst($month->locale('es')->isoFormat('MMM YY')),
                    'units' => $totals['units'],
                    'revenue' => $totals['revenue'],
                    'central_profit' => $totals['central_profit'],
                ];
            })
            ->all();
    }

    /**
     * Money that moved (packages sold, credit payments) in the period, and
     * the standing position: credit owed by distributors and prepaid units
     * they still hold.
     *
     * @return array<string, int|string>
     */
    public function cash(CarbonImmutable $from, CarbonImmutable $to, ?string $tenantId = null): array
    {
        $ledger = fn (SignatureLedgerEntryType $type) => SignatureLedgerEntry::query()
            ->where('type', $type->value)
            ->whereBetween('created_at', [$from, $to])
            ->when($tenantId, fn (Builder $query) => $query->whereHas('account', fn (Builder $account) => $account->where('tenant_id', $tenantId)))
            ->pluck('amount')
            ->sum(fn (string $amount) => Money::toCents($amount));

        $accounts = SignatureAccount::query()->when($tenantId, fn (Builder $query) => $query->where('tenant_id', $tenantId));

        return [
            'packages_sold' => Money::fromCents($ledger(SignatureLedgerEntryType::PackagePurchase())),
            'payments_received' => Money::fromCents(-$ledger(SignatureLedgerEntryType::Payment())),
            'credit_outstanding' => Money::fromCents((clone $accounts)->pluck('credit_used')->sum(fn (string $used) => Money::toCents($used))),
            'prepaid_units_held' => (int) SignatureBalance::query()
                ->whereIn('signature_account_id', (clone $accounts)->select('id'))
                ->sum('available_units'),
        ];
    }

    /**
     * Distributor accounts that need central's attention: credit line
     * almost used, prepaid balance running out, selling blocked, or no
     * sales lately.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function alerts(): Collection
    {
        $lastSale = SignatureProviderRequest::query()
            ->counted()
            ->toBase()
            ->selectRaw('tenant_id, max(created_at) as last_sale_at')
            ->groupBy('tenant_id')
            ->pluck('last_sale_at', 'tenant_id');

        return SignatureAccount::query()
            ->with(['tenant', 'balances'])
            ->get()
            ->flatMap(function (SignatureAccount $account) use ($lastSale) {
                $alerts = [];
                $tenantName = $account->tenant?->company_name ?? $account->tenant_id;
                $alert = fn (string $kind, string $tone, string $message) => [
                    'tenant_id' => $account->tenant_id,
                    'tenant_name' => $tenantName,
                    'kind' => $kind,
                    'tone' => $tone,
                    'message' => $message,
                ];

                if (! $account->is_active) {
                    return [$alert('inactive', 'red', 'Venta de firmas bloqueada')];
                }

                if ($account->isCredit()) {
                    $limit = Money::toCents($account->credit_limit);
                    $used = Money::toCents($account->credit_used);

                    if ($limit > 0 && $used >= $limit * self::CreditWarningRatio) {
                        $alerts[] = $alert('credit', $used >= $limit ? 'red' : 'amber', sprintf(
                            'Crédito usado al %d%% (%s de %s USD)',
                            (int) round($used / $limit * 100),
                            $account->credit_used,
                            $account->credit_limit,
                        ));
                    }
                } else {
                    $units = (int) $account->balances->sum('available_units');

                    if ($units <= self::LowStockUnits) {
                        $alerts[] = $alert('stock', $units === 0 ? 'red' : 'amber', $units === 0
                            ? 'Sin firmas prepagadas disponibles'
                            : "Solo le quedan {$units} firmas prepagadas");
                    }
                }

                $last = $lastSale[$account->tenant_id] ?? null;

                if ($last === null || CarbonImmutable::parse($last)->lt(now()->subDays(self::IdleDays))) {
                    $alerts[] = $alert('idle', 'gray', $last === null
                        ? 'Aún no ha vendido firmas'
                        : 'Sin ventas desde '.CarbonImmutable::parse($last)->format('d/m/Y'));
                }

                return $alerts;
            })
            ->sortBy(fn (array $alert) => ['red' => 0, 'amber' => 1, 'gray' => 2][$alert['tone']])
            ->values();
    }

    /**
     * Sales that count in the period, as light rows.
     *
     * @return Collection<int, object{tenant_id: string, signature_product_id: string|null, status: string, unit_cost: string|null, unit_price: string|null, sale_price: string|null, created_at: string}>
     */
    public function sales(CarbonImmutable $from, CarbonImmutable $to, ?string $tenantId = null, ?string $productId = null): Collection
    {
        return $this->scoped(SignatureProviderRequest::query()->counted(), $from, $to, $tenantId, $productId)
            ->toBase()
            ->get(['tenant_id', 'signature_product_id', 'status', 'unit_cost', 'unit_price', 'sale_price', 'created_at']);
    }

    /**
     * @param  Builder<SignatureProviderRequest>  $query
     * @return Builder<SignatureProviderRequest>
     */
    private function scoped(Builder $query, CarbonImmutable $from, CarbonImmutable $to, ?string $tenantId, ?string $productId): Builder
    {
        return $query
            ->whereBetween('created_at', [$from, $to])
            ->when($tenantId, fn (Builder $query) => $query->where('tenant_id', $tenantId))
            ->when($productId, fn (Builder $query) => $query->where('signature_product_id', $productId));
    }

    /**
     * Revenue and margins of a set of sales. Profit figures only include
     * sales whose cost (or retail price) is known; the counts of the ones
     * missing it are returned so the UI can flag incomplete data.
     *
     * @param  Collection<int, object>  $sales
     * @return array<string, int|float|string|null>
     */
    private function totals(Collection $sales): array
    {
        $cents = fn (?string $amount) => Money::toCents($amount);
        $withCost = $sales->whereNotNull('unit_cost');
        $withRetail = $sales->whereNotNull('sale_price');

        $revenue = $sales->sum(fn (object $sale) => $cents($sale->unit_price));
        $costedRevenue = $withCost->sum(fn (object $sale) => $cents($sale->unit_price));
        $cost = $withCost->sum(fn (object $sale) => $cents($sale->unit_cost));
        $retail = $withRetail->sum(fn (object $sale) => $cents($sale->sale_price));
        $retailWholesale = $withRetail->sum(fn (object $sale) => $cents($sale->unit_price));

        return [
            'units' => $sales->count(),
            'revenue' => Money::fromCents($revenue),
            'provider_cost' => Money::fromCents($cost),
            'central_profit' => Money::fromCents($costedRevenue - $cost),
            'central_margin' => $costedRevenue > 0 ? round(($costedRevenue - $cost) / $costedRevenue * 100, 1) : null,
            'retail' => Money::fromCents($retail),
            'distributor_profit' => Money::fromCents($retail - $retailWholesale),
            'distributor_margin' => $retail > 0 ? round(($retail - $retailWholesale) / $retail * 100, 1) : null,
            'average_sale_price' => $withRetail->isEmpty() ? null : Money::fromCents(intdiv($retail, $withRetail->count())),
            'average_unit_price' => $sales->isEmpty() ? null : Money::fromCents(intdiv($revenue, $sales->count())),
            'without_cost' => $sales->count() - $withCost->count(),
            'without_retail' => $sales->count() - $withRetail->count(),
        ];
    }
}
