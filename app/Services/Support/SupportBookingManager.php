<?php

namespace App\Services\Support;

use App\Enums\AppointmentStatus;
use App\Enums\TicketEventType;
use App\Models\CentralUser;
use App\Models\SupportAppointment;
use App\Models\SupportAttendanceType;
use App\Models\SupportBlackout;
use App\Models\SupportSetting;
use App\Models\SupportTechnician;
use App\Models\SupportTicket;
use App\Notifications\Support\AppointmentBooked;
use App\Notifications\Support\AppointmentCancelled;
use App\Services\TenantEntitlements;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Facades\Notification;

/**
 * Books, reschedules, cancels and closes support sessions. Booking re-checks
 * capacity inside a central transaction with the technicians locked, so two
 * customers can't take the last seat of the same slot.
 */
class SupportBookingManager
{
    public function __construct(
        private SupportAvailability $availability,
        private SupportTicketManager $tickets,
        private SupportLinks $links,
        private TenantEntitlements $entitlements,
    ) {}

    public function book(
        SupportTicket $ticket,
        SupportAttendanceType $type,
        CarbonInterface $startsAt,
        SupportActor $actor,
    ): SupportAppointment {
        $start = CarbonImmutable::instance($startsAt)->setTimezone(config('app.timezone'));
        $end = $start->addMinutes($type->duration_minutes);

        $this->guardBookable($ticket, $type);

        $appointment = $this->tickets->transaction(function () use ($ticket, $type, $start, $end, $actor): SupportAppointment {
            SupportTechnician::query()->where('is_active', true)->lockForUpdate()->get();

            if (! $this->availability->isOffered($type, $start)) {
                throw new DomainException('Esa franja ya no está disponible. Elige otro horario.');
            }

            $this->guardTenantAppointmentLimit($ticket);

            $technician = $this->availability->candidatesFor($start, $end)->first()
                ?? throw new DomainException('Esa franja ya no está disponible. Elige otro horario.');

            $appointment = $ticket->appointments()->create([
                'support_attendance_type_id' => $type->id,
                'support_technician_id' => $technician->id,
                'tenant_id' => $ticket->tenant_id,
                'starts_at' => $start,
                'ends_at' => $end,
                'status' => AppointmentStatus::Scheduled(),
                'booked_by_name' => $ticket->requester_name,
                'booked_by_email' => $ticket->requester_email,
            ]);

            $this->tickets->record($ticket, TicketEventType::AppointmentBooked(), $actor, [
                'starts_at' => $start->toIso8601String(),
                'type' => $type->name,
                'technician' => $technician->user->name,
            ]);

            return $appointment;
        });

        $this->notifyBooked($appointment);

        return $appointment;
    }

    public function reschedule(SupportAppointment $appointment, CarbonInterface $startsAt, SupportActor $actor): SupportAppointment
    {
        $this->guardRequesterNotice($appointment, $actor);

        return $this->tickets->transaction(function () use ($appointment, $startsAt, $actor): SupportAppointment {
            $this->cancelQuietly($appointment, $actor, 'Reprogramada');

            return $this->book($appointment->ticket, $appointment->attendanceType, $startsAt, $actor);
        });
    }

    public function cancel(SupportAppointment $appointment, SupportActor $actor, ?string $reason = null): void
    {
        $this->guardRequesterNotice($appointment, $actor);
        $this->cancelQuietly($appointment, $actor, $reason);
        $this->notifyCancelled($appointment);
    }

    public function setMeetingUrl(SupportAppointment $appointment, ?string $meetingUrl, SupportActor $actor): void
    {
        $appointment->update(['meeting_url' => $meetingUrl]);

        $this->tickets->record($appointment->ticket, TicketEventType::AppointmentUpdated(), $actor, [
            'meeting_url' => $meetingUrl,
        ]);
    }

    /**
     * Closes a session as attended or no-show. Either way the booked time is
     * logged against the ticket, so it consumes the plan's support hours.
     */
    public function finish(SupportAppointment $appointment, AppointmentStatus $status, CentralUser $staff): void
    {
        if (! $status->equals(AppointmentStatus::Completed(), AppointmentStatus::NoShow())) {
            throw new DomainException('Estado de cierre no válido.');
        }

        if (! $appointment->status->equals(AppointmentStatus::Scheduled())) {
            throw new DomainException('La cita ya fue cerrada.');
        }

        $this->tickets->transaction(function () use ($appointment, $status, $staff): void {
            $appointment->update(['status' => $status]);

            $this->tickets->logTime(
                $appointment->ticket,
                $staff,
                $appointment->minutes(),
                $status->equals(AppointmentStatus::NoShow())
                    ? "Cita {$appointment->attendanceType->name}: el cliente no asistió"
                    : "Cita {$appointment->attendanceType->name}",
                true,
                $appointment->starts_at,
                $appointment->id,
            );

            $this->tickets->record($appointment->ticket, TicketEventType::AppointmentUpdated(), SupportActor::staff($staff), [
                'status' => $status->label,
            ]);
        });
    }

    /**
     * A new holiday/absence: move each affected session to another technician
     * with room in the same slot, or cancel it and tell the customer.
     *
     * @return array{reassigned: int, cancelled: int}
     */
    public function applyBlackout(SupportBlackout $blackout, SupportActor $actor): array
    {
        $result = ['reassigned' => 0, 'cancelled' => 0];

        $affected = SupportAppointment::query()
            ->active()
            ->overlapping($blackout->starts_at, $blackout->ends_at)
            ->when($blackout->support_technician_id, fn ($query, $technicianId) => $query->where('support_technician_id', $technicianId))
            ->with(['ticket', 'attendanceType', 'technician.user'])
            ->get();

        foreach ($affected as $appointment) {
            $replacement = $blackout->support_technician_id === null
                ? null
                : $this->availability
                    ->candidatesFor($appointment->starts_at, $appointment->ends_at, $appointment->support_technician_id)
                    ->first();

            if ($replacement) {
                $appointment->update(['support_technician_id' => $replacement->id]);
                $appointment->setRelation('technician', $replacement);

                $this->tickets->record($appointment->ticket, TicketEventType::AppointmentUpdated(), $actor, [
                    'technician' => $replacement->user->name,
                    'reason' => $blackout->reason,
                ]);

                $replacement->user->notify(new AppointmentBooked($appointment, $this->links->forStaff($appointment->ticket)));
                $result['reassigned']++;

                continue;
            }

            $this->cancelQuietly($appointment, $actor, "Sin atención: {$blackout->reason}");
            $this->notifyCancelled($appointment);
            $result['cancelled']++;
        }

        return $result;
    }

    private function guardBookable(SupportTicket $ticket, SupportAttendanceType $type): void
    {
        if (! $type->is_active) {
            throw new DomainException('Ese tipo de atención ya no está disponible.');
        }

        if (! $ticket->isOpen()) {
            throw new DomainException('Solo se puede agendar sobre tickets abiertos.');
        }

        if (! $this->tickets->tenantHasSupport($ticket->tenant)) {
            throw new DomainException('El agendamiento requiere el módulo de Soporte contratado.');
        }
    }

    private function guardTenantAppointmentLimit(SupportTicket $ticket): void
    {
        $limit = $this->entitlements->effectiveLimits($ticket->tenant)[SupportLimits::MaxActiveAppointments] ?? null;

        if ($limit === null) {
            return;
        }

        $active = SupportAppointment::query()
            ->active()
            ->where('tenant_id', $ticket->tenant_id)
            ->where('ends_at', '>', now())
            ->count();

        if ($active >= $limit) {
            throw new DomainException("Tu plan permite {$limit} cita(s) activa(s) a la vez.");
        }
    }

    private function guardRequesterNotice(SupportAppointment $appointment, SupportActor $actor): void
    {
        if (! $appointment->status->equals(AppointmentStatus::Scheduled())) {
            throw new DomainException('La cita ya no está activa.');
        }

        $hours = SupportSetting::current()->cancellation_notice_hours;

        if (! $actor->isStaff() && $appointment->starts_at->lessThan(now()->addHours($hours))) {
            throw new DomainException("Solo puedes cancelar o reprogramar hasta {$hours} h antes de la cita.");
        }
    }

    private function cancelQuietly(SupportAppointment $appointment, SupportActor $actor, ?string $reason): void
    {
        $appointment->update([
            'status' => AppointmentStatus::Cancelled(),
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
        ]);

        $this->tickets->record($appointment->ticket, TicketEventType::AppointmentCancelled(), $actor, [
            'starts_at' => $appointment->starts_at->toIso8601String(),
            'reason' => $reason,
        ]);
    }

    private function notifyBooked(SupportAppointment $appointment): void
    {
        $appointment->loadMissing(['ticket', 'attendanceType', 'technician.user']);

        Notification::route('mail', $appointment->booked_by_email)
            ->notify(new AppointmentBooked($appointment, $this->links->forRequester($appointment->ticket)));

        $appointment->technician->user->notify(new AppointmentBooked($appointment, $this->links->forStaff($appointment->ticket)));
    }

    private function notifyCancelled(SupportAppointment $appointment): void
    {
        $appointment->loadMissing(['ticket', 'attendanceType', 'technician.user']);

        Notification::route('mail', $appointment->booked_by_email)
            ->notify(new AppointmentCancelled($appointment, $this->links->forRequester($appointment->ticket)));

        $appointment->technician->user->notify(new AppointmentCancelled($appointment, $this->links->forStaff($appointment->ticket)));
    }
}
