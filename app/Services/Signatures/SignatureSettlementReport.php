<?php

namespace App\Services\Signatures;

use App\Enums\SignatureLedgerEntryType;
use App\Enums\SignaturePaymentReview;
use App\Enums\SignaturePaymentStatus;
use App\Enums\SignatureRequestStatus;
use App\Models\SignatureAccount;
use App\Models\SignatureBalance;
use App\Models\SignatureLedgerEntry;
use App\Models\SignatureProduct;
use App\Models\SignatureProviderRequest;
use App\Models\SignatureRequest;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * A tenant's settlement ("liquidación") of the signatures it sold in a
 * period: what its customers paid (tenant DB, confirmed payments) against
 * what each signature cost it (central ledger), so the tenant can see how
 * profitable reselling signatures is.
 *
 * A signature counts as sold when it was sent to the certification
 * authority in the period (that is when the quota is consumed) and its
 * consumption was not reversed. Cost per signature:
 *   - credit accounts: the unit price charged by the consumption entry;
 *   - prepaid accounts: the account's average unit cost of the packages it
 *     bought for that product (falls back to the product's credit price
 *     when no package was ever bought, e.g. courtesy units).
 * Revenue is the sum of the request's confirmed payments; requests marked
 * Paid without payment rows (created before payments existed) use their
 * sale price.
 */
class SignatureSettlementReport
{
    /**
     * @return array{
     *     period: array{from: string, to: string},
     *     summary: array{sold: int, reversed: int, revenue: string, cost: string, margin: string, margin_percent: float|null, average_ticket: string|null},
     *     products: list<array{product_name: string, sold: int, revenue: string, cost: string, margin: string, margin_percent: float|null, average_price: string}>,
     *     rows: Collection<int, array{id: string, code: string, customer: string, document_number: string, product_id: string, product_name: string, source_label: string, status: int|string, status_label: string, submitted_at: string|null, is_reversed: bool, revenue_cents: int, cost_cents: int, revenue: string, cost: string, margin: string}>,
     *     account: array<string, mixed>|null,
     *     pending: array{to_send: int, to_send_amount: string, payments_under_review: int, payments_under_review_amount: string}
     * }
     */
    public function build(Tenant $tenant, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $start = $from->startOfDay();
        $end = $to->endOfDay();

        $requests = SignatureRequest::query()
            ->whereNotNull('central_reference')
            ->whereBetween('submitted_at', [$start, $end])
            ->withSum(['payments as collected' => fn ($query) => $query->where('review', SignaturePaymentReview::Approved()->value)], 'amount')
            ->orderBy('submitted_at')
            ->get();

        $account = $tenant->signatureAccount()->first();
        $sales = SignatureProviderRequest::query()
            ->whereIn('id', $requests->pluck('central_reference')->all())
            ->with(['consumption' => fn ($query) => $query->withExists('reversal')])
            ->get()
            ->keyBy('id');
        $unitCosts = $account ? $this->prepaidUnitCosts($account) : [];
        $creditPrices = SignatureProduct::query()->pluck('credit_unit_price', 'id');

        $rows = $requests->map(function (SignatureRequest $request) use ($sales, $account, $unitCosts, $creditPrices): array {
            $consumption = $sales->get($request->central_reference)?->consumption;
            $isReversed = (bool) ($consumption->reversal_exists ?? false);
            $revenueCents = $this->revenueCents($request);
            $costCents = $isReversed ? 0 : $this->costCents($consumption, $request, $account, $unitCosts, $creditPrices);

            return [
                'id' => $request->id,
                'code' => $request->code(),
                'customer' => $request->applicantName(),
                'document_number' => $request->document_number,
                'product_id' => $request->signature_product_id,
                'product_name' => $request->product_name,
                'source_label' => $request->source->label,
                'status' => $request->status->value,
                'status_label' => $request->status->label,
                'submitted_at' => $request->submitted_at?->toIso8601String(),
                'is_reversed' => $isReversed,
                'revenue_cents' => $revenueCents,
                'cost_cents' => $costCents,
                'revenue' => Money::fromCents($revenueCents),
                'cost' => Money::fromCents($costCents),
                'margin' => Money::fromCents($revenueCents - $costCents),
            ];
        })->values();

        $settled = $rows->where('is_reversed', false);

        return [
            'period' => ['from' => $start->toDateString(), 'to' => $end->toDateString()],
            'summary' => [
                'sold' => $settled->count(),
                'reversed' => $rows->where('is_reversed', true)->count(),
                ...$this->totals((int) $settled->sum('revenue_cents'), (int) $settled->sum('cost_cents')),
                'average_ticket' => $settled->isEmpty() ? null : Money::fromCents(intdiv($settled->sum('revenue_cents'), $settled->count())),
            ],
            'products' => array_values($settled->groupBy('product_id')
                ->map(fn (Collection $productRows) => [
                    'product_name' => $productRows->first()['product_name'],
                    'sold' => $productRows->count(),
                    ...$this->totals((int) $productRows->sum('revenue_cents'), (int) $productRows->sum('cost_cents')),
                    'average_price' => Money::fromCents(intdiv($productRows->sum('revenue_cents'), $productRows->count())),
                ])
                ->sortByDesc('sold')
                ->all()),
            'rows' => $rows,
            'account' => $account ? $this->accountStanding($account, $unitCosts, $creditPrices) : null,
            'pending' => $this->pending(),
        ];
    }

    /**
     * @return array{revenue: string, cost: string, margin: string, margin_percent: float|null}
     */
    private function totals(int $revenue, int $cost): array
    {
        return [
            'revenue' => Money::fromCents($revenue),
            'cost' => Money::fromCents($cost),
            'margin' => Money::fromCents($revenue - $cost),
            'margin_percent' => $revenue === 0 ? null : round(($revenue - $cost) / $revenue * 100, 1),
        ];
    }

    private function revenueCents(SignatureRequest $request): int
    {
        $collected = Money::toCents($request->getAttribute('collected'));

        if ($collected === 0 && $request->payment_status->equals(SignaturePaymentStatus::Paid())) {
            return Money::toCents($request->sale_price);
        }

        return $collected;
    }

    /**
     * @param  array<string, int>  $unitCosts
     * @param  Collection<string, string>  $creditPrices
     */
    private function costCents(
        ?SignatureLedgerEntry $consumption,
        SignatureRequest $request,
        ?SignatureAccount $account,
        array $unitCosts,
        Collection $creditPrices,
    ): int {
        if ($consumption !== null && Money::toCents($consumption->amount) !== 0) {
            return Money::toCents($consumption->amount);
        }

        $productId = $consumption->signature_product_id ?? $request->signature_product_id;

        if ($account !== null && ! $account->isCredit() && isset($unitCosts[$productId])) {
            return $unitCosts[$productId];
        }

        return Money::toCents($creditPrices->get($productId));
    }

    /**
     * Average unit cost (in cents) of the packages the account bought, per product.
     *
     * @return array<string, int>
     */
    private function prepaidUnitCosts(SignatureAccount $account): array
    {
        return $account->ledgerEntries()
            ->where('type', SignatureLedgerEntryType::PackagePurchase()->value)
            ->whereNotNull('signature_product_id')
            ->get(['signature_product_id', 'units', 'amount'])
            ->groupBy('signature_product_id')
            ->map(function (Collection $purchases): ?int {
                $units = (int) $purchases->sum('units');

                return $units > 0
                    ? intdiv((int) $purchases->sum(fn (SignatureLedgerEntry $entry) => Money::toCents($entry->amount)), $units)
                    : null;
            })
            ->filter(fn (?int $cost) => $cost !== null)
            ->all();
    }

    /**
     * What the tenant owes the platform (credit) or still holds (prepaid units).
     *
     * @param  array<string, int>  $unitCosts
     * @param  Collection<string, string>  $creditPrices
     * @return array<string, mixed>
     */
    private function accountStanding(SignatureAccount $account, array $unitCosts, Collection $creditPrices): array
    {
        $balances = $account->balances()->with('product')->where('available_units', '>', 0)->get()
            ->map(function (SignatureBalance $balance) use ($unitCosts, $creditPrices): array {
                $unitCost = $unitCosts[$balance->signature_product_id] ?? Money::toCents($creditPrices->get($balance->signature_product_id));

                return [
                    'product_name' => $balance->product->name,
                    'available_units' => $balance->available_units,
                    'unit_cost' => Money::fromCents($unitCost),
                    'inventory_value' => Money::fromCents($unitCost * $balance->available_units),
                ];
            })
            ->values();

        return [
            'affiliation_mode' => $account->affiliation_mode->value,
            'affiliation_mode_label' => $account->affiliation_mode->label,
            'is_credit' => $account->isCredit(),
            'credit_limit' => Money::fromCents(Money::toCents($account->credit_limit)),
            'credit_used' => Money::fromCents(Money::toCents($account->credit_used)),
            'credit_available' => Money::fromCents($account->availableCreditCents()),
            'balances' => $balances->all(),
            'inventory_value' => Money::fromCents($balances->sum(fn (array $balance) => Money::toCents($balance['inventory_value']))),
        ];
    }

    /**
     * Money already collected but not yet turned into a sale, and receipts
     * waiting for review.
     *
     * @return array{to_send: int, to_send_amount: string, payments_under_review: int, payments_under_review_amount: string}
     */
    private function pending(): array
    {
        $toSend = SignatureRequest::query()
            ->where('status', SignatureRequestStatus::Draft()->value)
            ->where('payment_status', SignaturePaymentStatus::Paid()->value);
        $underReview = SignatureRequest::query()
            ->where('payment_status', SignaturePaymentStatus::UnderReview()->value);

        return [
            'to_send' => (clone $toSend)->count(),
            'to_send_amount' => Money::fromCents(Money::toCents((clone $toSend)->sum('sale_price'))),
            'payments_under_review' => (clone $underReview)->count(),
            'payments_under_review_amount' => Money::fromCents(Money::toCents((clone $underReview)->sum('sale_price'))),
        ];
    }
}
