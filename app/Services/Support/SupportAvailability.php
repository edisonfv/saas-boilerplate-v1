<?php

namespace App\Services\Support;

use App\Models\SupportAppointment;
use App\Models\SupportAttendanceType;
use App\Models\SupportBlackout;
use App\Models\SupportSetting;
use App\Models\SupportTechnician;
use App\Models\SupportTechnicianShift;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Computes bookable slots. The team's opening hours are cut into slots the
 * size of the attendance type; a slot's capacity is the sum, over active
 * technicians whose shift fully covers it (and who aren't absent), of their
 * capacity minus the sessions they already have overlapping it. Booking
 * rules (minimum notice, maximum days ahead) come from SupportSetting.
 */
class SupportAvailability
{
    public function __construct(private SupportCalendar $calendar) {}

    /**
     * @return list<array{starts_at: CarbonImmutable, ends_at: CarbonImmutable, available: int}>
     */
    public function slotsOn(SupportAttendanceType $type, CarbonInterface $day, bool $includeFull = false): array
    {
        $date = CarbonImmutable::parse($day->toDateString(), config('app.timezone'));

        if (! $this->isBookableDate($date)) {
            return [];
        }

        $earliest = $this->earliestBookableStart();
        $technicians = $this->techniciansWithScheduleOn($date);
        $slots = [];

        foreach ($this->calendar->windowsOn($date) as [$opensAt, $closesAt]) {
            for ($start = $opensAt; $start->addMinutes($type->duration_minutes)->lessThanOrEqualTo($closesAt); $start = $start->addMinutes($type->duration_minutes)) {
                if ($start->lessThan($earliest)) {
                    continue;
                }

                $end = $start->addMinutes($type->duration_minutes);
                $available = $technicians->sum(fn (SupportTechnician $technician) => $this->remainingCapacity($technician, $start, $end));

                if ($available > 0 || $includeFull) {
                    $slots[] = ['starts_at' => $start, 'ends_at' => $end, 'available' => $available];
                }
            }
        }

        return $slots;
    }

    /**
     * Days (from today to the booking horizon) that still have at least one free slot.
     *
     * @return list<string> dates as Y-m-d
     */
    public function availableDates(SupportAttendanceType $type): array
    {
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        $horizon = SupportSetting::current()->booking_max_days_ahead;
        $dates = [];

        for ($offset = 0; $offset <= $horizon; $offset++) {
            $date = $today->addDays($offset);

            if ($this->slotsOn($type, $date) !== []) {
                $dates[] = $date->toDateString();
            }
        }

        return $dates;
    }

    /**
     * Whether an exact start time is currently offered for this attendance type.
     */
    public function isOffered(SupportAttendanceType $type, CarbonInterface $startsAt): bool
    {
        return collect($this->slotsOn($type, $startsAt))
            ->contains(fn (array $slot) => $slot['starts_at']->equalTo($startsAt));
    }

    /**
     * Technicians who could take a session in this exact interval, least
     * loaded first (fewest overlapping sessions, then fewest that day).
     *
     * @return Collection<int, SupportTechnician>
     */
    public function candidatesFor(CarbonInterface $startsAt, CarbonInterface $endsAt, ?string $exceptTechnicianId = null): Collection
    {
        $start = CarbonImmutable::instance($startsAt);
        $end = CarbonImmutable::instance($endsAt);

        return $this->techniciansWithScheduleOn($start)
            ->reject(fn (SupportTechnician $technician) => $technician->id === $exceptTechnicianId)
            ->filter(fn (SupportTechnician $technician) => $this->remainingCapacity($technician, $start, $end) > 0)
            ->sortBy([
                fn (SupportTechnician $a, SupportTechnician $b) => $this->overlapping($a, $start, $end) <=> $this->overlapping($b, $start, $end),
                fn (SupportTechnician $a, SupportTechnician $b) => $a->appointments->count() <=> $b->appointments->count(),
            ])
            ->values();
    }

    public function earliestBookableStart(): CarbonImmutable
    {
        return CarbonImmutable::now(config('app.timezone'))
            ->addHours(SupportSetting::current()->booking_min_notice_hours);
    }

    private function isBookableDate(CarbonImmutable $date): bool
    {
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();

        return $date->greaterThanOrEqualTo($today)
            && $date->lessThanOrEqualTo($today->addDays(SupportSetting::current()->booking_max_days_ahead));
    }

    /**
     * Active technicians with their shifts, absences and active sessions for
     * that day eager-loaded, so slot computation runs without extra queries.
     *
     * @return Collection<int, SupportTechnician>
     */
    private function techniciansWithScheduleOn(CarbonImmutable $day): Collection
    {
        $dayStart = $day->startOfDay();
        $dayEnd = $day->endOfDay();

        return SupportTechnician::query()
            ->where('is_active', true)
            ->with([
                'user',
                'shifts' => fn ($query) => $query->where('weekday', $day->isoWeekday()),
                'blackouts' => fn ($query) => $query->where('starts_at', '<', $dayEnd)->where('ends_at', '>', $dayStart),
                'appointments' => fn ($query) => $query->active()->overlapping($dayStart, $dayEnd),
            ])
            ->get();
    }

    private function remainingCapacity(SupportTechnician $technician, CarbonImmutable $start, CarbonImmutable $end): int
    {
        if (! $this->isOnShift($technician, $start, $end) || $this->isAbsent($technician, $start, $end)) {
            return 0;
        }

        return max(0, $technician->capacity - $this->overlapping($technician, $start, $end));
    }

    private function overlapping(SupportTechnician $technician, CarbonImmutable $start, CarbonImmutable $end): int
    {
        return $technician->appointments
            ->filter(fn (SupportAppointment $appointment) => $appointment->starts_at->lessThan($end) && $appointment->ends_at->greaterThan($start))
            ->count();
    }

    private function isOnShift(SupportTechnician $technician, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return $technician->shifts->contains(function (SupportTechnicianShift $shift) use ($start, $end): bool {
            $shiftStart = $start->setTimeFromTimeString($shift->starts_at);
            $shiftEnd = $start->setTimeFromTimeString($shift->ends_at);

            return $start->greaterThanOrEqualTo($shiftStart) && $end->lessThanOrEqualTo($shiftEnd);
        });
    }

    private function isAbsent(SupportTechnician $technician, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return $technician->blackouts->contains(
            fn (SupportBlackout $blackout) => $blackout->starts_at->lessThan($end) && $blackout->ends_at->greaterThan($start),
        );
    }
}
