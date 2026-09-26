<?php

namespace App\Services\Support;

use App\Enums\TicketBillingMode;
use App\Enums\TicketBillingStatus;
use App\Models\SupportSetting;
use App\Models\SupportTicket;
use App\Models\SupportTimeEntry;
use App\Models\Tenant;
use App\Services\TenantEntitlements;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Support hours consumed by a tenant in its current billing cycle, and what
 * is left to invoice:
 *
 * - "Included" tickets consume the plan's hours; only the excess is charged
 *   at the global hourly rate. Hours reset every cycle (no rollover).
 * - "Hourly" tickets charge all their billable time at the global rate.
 * - "Fixed" tickets charge their fixed amount.
 */
class SupportUsage
{
    public function __construct(private TenantEntitlements $entitlements) {}

    /**
     * @return array{
     *     period_start: CarbonImmutable,
     *     period_end: CarbonImmutable,
     *     included_minutes: int,
     *     used_minutes: int,
     *     excess_minutes: int,
     *     hourly_minutes: int,
     *     fixed_amount: float,
     *     hourly_rate: float,
     *     currency: string,
     *     amount_due: float,
     * }
     */
    public function forTenant(Tenant $tenant): array
    {
        [$periodStart, $periodEnd] = $this->currentPeriod($tenant);
        $settings = SupportSetting::current();
        $rate = (float) $settings->hourly_rate;

        $included = (int) (($this->entitlements->effectiveLimits($tenant)[SupportLimits::IncludedHours] ?? 0) * 60);
        $used = $this->billableMinutes($tenant, TicketBillingMode::Included(), $periodStart, $periodEnd);
        $hourly = $this->billableMinutes($tenant, TicketBillingMode::Hourly(), $periodStart, $periodEnd, pendingOnly: true);
        $excess = max(0, $used - $included);

        $fixed = (float) SupportTicket::query()
            ->where('tenant_id', $tenant->getTenantKey())
            ->where('billing_mode', TicketBillingMode::Fixed()->value)
            ->where('billing_status', TicketBillingStatus::Pending()->value)
            ->sum('fixed_amount');

        return [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'included_minutes' => $included,
            'used_minutes' => $used,
            'excess_minutes' => $excess,
            'hourly_minutes' => $hourly,
            'fixed_amount' => round($fixed, 2),
            'hourly_rate' => $rate,
            'currency' => $settings->currency,
            'amount_due' => round((($excess + $hourly) / 60) * $rate + $fixed, 2),
        ];
    }

    /**
     * The tenant's current billing cycle; calendar month when it has no subscription.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function currentPeriod(Tenant $tenant): array
    {
        $subscription = $tenant->subscription()->first();

        if ($subscription?->current_period_start && $subscription->current_period_end) {
            return [
                CarbonImmutable::instance($subscription->current_period_start),
                CarbonImmutable::instance($subscription->current_period_end),
            ];
        }

        $now = CarbonImmutable::now(config('app.timezone'));

        return [$now->startOfMonth(), $now->endOfMonth()];
    }

    private function billableMinutes(
        Tenant $tenant,
        TicketBillingMode $mode,
        CarbonImmutable $from,
        CarbonImmutable $to,
        bool $pendingOnly = false,
    ): int {
        return (int) SupportTimeEntry::query()
            ->where('is_billable', true)
            ->whereBetween('worked_on', [$from->toDateString(), $to->toDateString()])
            ->whereHas('ticket', fn (Builder $ticket) => $ticket
                ->where('tenant_id', $tenant->getTenantKey())
                ->where('billing_mode', $mode->value)
                ->when($pendingOnly, fn (Builder $query) => $query->where('billing_status', TicketBillingStatus::Pending()->value)))
            ->sum('minutes');
    }
}
