<?php

namespace App\Services\Support;

use App\Enums\TicketPriority;
use App\Models\SupportTicket;
use App\Services\TenantEntitlements;
use Carbon\CarbonImmutable;

/**
 * Computes SLA deadlines from the tenant's plan. The plan defines base
 * targets (in business hours) for a Normal-priority ticket; other
 * priorities scale them. Time is counted only while support is open
 * (see SupportCalendar). Tickets not covered by the Support module get no SLA.
 */
class SupportSla
{
    public function __construct(
        private TenantEntitlements $entitlements,
        private SupportCalendar $calendar,
    ) {}

    /**
     * Fraction of the plan's base target that applies to each priority.
     */
    public static function priorityFactor(TicketPriority $priority): float
    {
        return match (true) {
            $priority->equals(TicketPriority::Urgent()) => 0.25,
            $priority->equals(TicketPriority::High()) => 0.5,
            $priority->equals(TicketPriority::Low()) => 2.0,
            default => 1.0,
        };
    }

    /**
     * @return array{first_response_due_at: CarbonImmutable|null, resolution_due_at: CarbonImmutable|null}
     */
    public function deadlinesFor(SupportTicket $ticket): array
    {
        $none = ['first_response_due_at' => null, 'resolution_due_at' => null];

        if (! $ticket->covered_by_plan || $ticket->tenant === null) {
            return $none;
        }

        $limits = $this->entitlements->effectiveLimits($ticket->tenant);
        $factor = self::priorityFactor($ticket->priority);
        $openedAt = $ticket->created_at ?? now();

        $due = fn (?int $hours) => $hours === null || $hours <= 0
            ? null
            : $this->calendar->addBusinessMinutes($openedAt, (int) round($hours * 60 * $factor));

        return [
            'first_response_due_at' => $due($limits[SupportLimits::FirstResponseHours] ?? null),
            'resolution_due_at' => $due($limits[SupportLimits::ResolutionHours] ?? null),
        ];
    }
}
