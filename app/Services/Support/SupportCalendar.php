<?php

namespace App\Services\Support;

use App\Models\SupportBlackout;
use App\Models\SupportBusinessHour;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Business-hours math for the support team, in config('app.timezone'):
 * which windows are open on a given day (team hours minus team-wide
 * blackouts), and how to add "business minutes" to a moment — the basis
 * for SLA deadlines and bookable slots.
 */
class SupportCalendar
{
    /** Safety net so a misconfigured calendar can't loop forever. */
    private const MAX_DAYS_SCANNED = 366;

    /** @var Collection<int, SupportBusinessHour>|null */
    private ?Collection $hours = null;

    /**
     * Open windows on the given day, sorted, with team-wide blackouts cut out.
     *
     * @return list<array{0: CarbonImmutable, 1: CarbonImmutable}>
     */
    public function windowsOn(CarbonInterface $day): array
    {
        $date = CarbonImmutable::parse($day->toDateString(), config('app.timezone'));

        $windows = $this->businessHours()
            ->where('weekday', $date->isoWeekday())
            ->map(fn (SupportBusinessHour $hour) => [
                $date->setTimeFromTimeString($hour->opens_at),
                $date->setTimeFromTimeString($hour->closes_at),
            ])
            ->filter(fn (array $window) => $window[1]->greaterThan($window[0]))
            ->sortBy(fn (array $window) => $window[0]->getTimestamp())
            ->values()
            ->all();

        $windows = array_values($windows);

        return $this->subtract($windows, $this->teamBlackoutsOn($date));
    }

    public function hasBusinessHours(): bool
    {
        return $this->businessHours()->isNotEmpty();
    }

    /**
     * Moves forward `$minutes` of open time from `$from`. Without any business
     * hours configured, falls back to wall-clock minutes.
     */
    public function addBusinessMinutes(CarbonInterface $from, int $minutes): CarbonImmutable
    {
        $cursor = CarbonImmutable::instance($from)->setTimezone(config('app.timezone'));

        if (! $this->hasBusinessHours() || $minutes <= 0) {
            return $cursor->addMinutes(max(0, $minutes));
        }

        $remaining = $minutes;

        for ($day = 0; $day < self::MAX_DAYS_SCANNED; $day++) {
            foreach ($this->windowsOn($cursor) as [$opensAt, $closesAt]) {
                if ($closesAt->lessThanOrEqualTo($cursor)) {
                    continue;
                }

                $start = $opensAt->greaterThan($cursor) ? $opensAt : $cursor;
                $available = (int) $start->diffInMinutes($closesAt);

                if ($remaining <= $available) {
                    return $start->addMinutes($remaining);
                }

                $remaining -= $available;
            }

            $cursor = $cursor->addDay()->startOfDay();
        }

        return $cursor;
    }

    /**
     * Forget memoized business hours (after staff edits them).
     */
    public function refresh(): void
    {
        $this->hours = null;
    }

    /**
     * @return Collection<int, SupportBusinessHour>
     */
    private function businessHours(): Collection
    {
        return $this->hours ??= SupportBusinessHour::query()->get();
    }

    /**
     * @return array<int, array{0: CarbonImmutable, 1: CarbonImmutable}>
     */
    private function teamBlackoutsOn(CarbonImmutable $date): array
    {
        return SupportBlackout::query()
            ->whereNull('support_technician_id')
            ->where('starts_at', '<', $date->endOfDay())
            ->where('ends_at', '>', $date->startOfDay())
            ->get()
            ->map(fn (SupportBlackout $blackout) => [
                CarbonImmutable::instance($blackout->starts_at),
                CarbonImmutable::instance($blackout->ends_at),
            ])
            ->values()
            ->all();
    }

    /**
     * Removes the `$cuts` intervals from `$windows`.
     *
     * @param  list<array{0: CarbonImmutable, 1: CarbonImmutable}>  $windows
     * @param  array<int, array{0: CarbonImmutable, 1: CarbonImmutable}>  $cuts
     * @return list<array{0: CarbonImmutable, 1: CarbonImmutable}>
     */
    private function subtract(array $windows, array $cuts): array
    {
        foreach ($cuts as [$cutStart, $cutEnd]) {
            $next = [];

            foreach ($windows as [$start, $end]) {
                if ($cutEnd->lessThanOrEqualTo($start) || $cutStart->greaterThanOrEqualTo($end)) {
                    $next[] = [$start, $end];

                    continue;
                }

                if ($cutStart->greaterThan($start)) {
                    $next[] = [$start, $cutStart];
                }

                if ($cutEnd->lessThan($end)) {
                    $next[] = [$cutEnd, $end];
                }
            }

            $windows = $next;
        }

        return $windows;
    }
}
