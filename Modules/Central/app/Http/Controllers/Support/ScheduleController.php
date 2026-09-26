<?php

namespace Modules\Central\Http\Controllers\Support;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\CentralUser;
use App\Models\SupportAppointment;
use App\Models\SupportAttendanceType;
use App\Models\SupportBlackout;
use App\Models\SupportBusinessHour;
use App\Models\SupportSetting;
use App\Models\SupportTechnician;
use App\Models\SupportTechnicianShift;
use App\Services\Support\SupportActor;
use App\Services\Support\SupportBookingManager;
use App\Services\Support\SupportTicketManager;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Central\Http\Requests\Support\SaveAttendanceTypeRequest;
use Modules\Central\Http\Requests\Support\SaveTechnicianRequest;
use Modules\Central\Http\Requests\Support\StoreBlackoutRequest;
use Modules\Central\Http\Requests\Support\UpdateBusinessHoursRequest;
use Modules\Central\Http\Requests\Support\UpdateSupportSettingsRequest;

/**
 * The support agenda (upcoming sessions) and everything that shapes it:
 * global rules and rate, team hours, attendance types, technicians with
 * their capacity and shifts, and holidays/absences.
 */
class ScheduleController extends Controller
{
    public function __construct(private SupportTicketManager $tickets) {}

    public function index(Request $request): Response
    {
        $from = CarbonImmutable::parse($request->string('from')->toString() ?: 'today', config('app.timezone'))->startOfDay();
        $to = $from->addDays(6)->endOfDay();

        return Inertia::render('Central/Support/Schedule/Index', [
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'appointments' => SupportAppointment::query()
                ->whereBetween('starts_at', [$from, $to])
                ->where('status', '!=', AppointmentStatus::Cancelled()->value)
                ->with(['ticket.tenant', 'attendanceType', 'technician.user'])
                ->orderBy('starts_at')
                ->get()
                ->map(fn (SupportAppointment $appointment) => [
                    'id' => $appointment->id,
                    'ticket_id' => $appointment->support_ticket_id,
                    'ticket_code' => $appointment->ticket->code(),
                    'subject' => $appointment->ticket->subject,
                    'tenant_name' => $appointment->ticket->tenant?->company_name ?? $appointment->tenant_id,
                    'type_name' => $appointment->attendanceType->name,
                    'technician_name' => $appointment->technician->user->name,
                    'starts_at' => $appointment->starts_at->toIso8601String(),
                    'ends_at' => $appointment->ends_at->toIso8601String(),
                    'status' => $appointment->status->value,
                    'status_label' => $appointment->status->label,
                    'meeting_url' => $appointment->meeting_url,
                ]),
            'settings' => SupportSetting::current()->only([
                'hourly_rate', 'currency', 'booking_min_notice_hours', 'booking_max_days_ahead',
                'cancellation_notice_hours', 'auto_close_days', 'reopen_window_days', 'rating_link_days',
            ]),
            'businessHours' => SupportBusinessHour::query()->orderBy('weekday')->orderBy('opens_at')->get()
                ->map(fn (SupportBusinessHour $hour) => [
                    'weekday' => $hour->weekday,
                    'opens_at' => substr($hour->opens_at, 0, 5),
                    'closes_at' => substr($hour->closes_at, 0, 5),
                ]),
            'attendanceTypes' => SupportAttendanceType::query()->orderBy('duration_minutes')->get()
                ->map(fn (SupportAttendanceType $type) => $type->only(['id', 'name', 'description', 'duration_minutes', 'is_active'])),
            'technicians' => SupportTechnician::query()->with(['user', 'shifts'])->get()
                ->sortBy('user.name')
                ->values()
                ->map(fn (SupportTechnician $technician) => [
                    'id' => $technician->id,
                    'central_user_id' => $technician->central_user_id,
                    'name' => $technician->user->name,
                    'capacity' => $technician->capacity,
                    'is_active' => $technician->is_active,
                    'shifts' => $technician->shifts->sortBy(['weekday', 'starts_at'])->values()->map(fn (SupportTechnicianShift $shift) => [
                        'weekday' => $shift->weekday,
                        'starts_at' => substr($shift->starts_at, 0, 5),
                        'ends_at' => substr($shift->ends_at, 0, 5),
                    ]),
                ]),
            'blackouts' => SupportBlackout::query()->where('ends_at', '>=', now()->startOfDay())->with('technician.user')->orderBy('starts_at')->get()
                ->map(fn (SupportBlackout $blackout) => [
                    'id' => $blackout->id,
                    'technician_name' => $blackout->technician?->user->name,
                    'starts_at' => $blackout->starts_at->toIso8601String(),
                    'ends_at' => $blackout->ends_at->toIso8601String(),
                    'reason' => $blackout->reason,
                ]),
            'staff' => CentralUser::query()->whereDoesntHave('supportTechnician')->orderBy('name')->get(['id', 'name']),
            'timezone' => config('app.timezone'),
            'can' => [
                'update' => $request->user()->can('central.support-schedule.update'),
            ],
        ]);
    }

    public function updateSettings(UpdateSupportSettingsRequest $request): RedirectResponse
    {
        SupportSetting::current()->update($request->validated());

        return back()->with('status', 'support-settings-updated');
    }

    public function updateBusinessHours(UpdateBusinessHoursRequest $request): RedirectResponse
    {
        $this->tickets->transaction(function () use ($request): void {
            SupportBusinessHour::query()->delete();

            foreach ($request->validated('hours') as $hour) {
                SupportBusinessHour::create($hour);
            }
        });

        return back()->with('status', 'support-settings-updated');
    }

    public function storeAttendanceType(SaveAttendanceTypeRequest $request): RedirectResponse
    {
        SupportAttendanceType::create($request->validated());

        return back()->with('status', 'support-settings-updated');
    }

    public function updateAttendanceType(SaveAttendanceTypeRequest $request, SupportAttendanceType $attendanceType): RedirectResponse
    {
        $attendanceType->update($request->validated());

        return back()->with('status', 'support-settings-updated');
    }

    public function storeTechnician(SaveTechnicianRequest $request): RedirectResponse
    {
        $this->saveTechnician(new SupportTechnician(['central_user_id' => $request->validated('central_user_id')]), $request);

        return back()->with('status', 'support-settings-updated');
    }

    public function updateTechnician(SaveTechnicianRequest $request, SupportTechnician $technician): RedirectResponse
    {
        $this->saveTechnician($technician, $request);

        return back()->with('status', 'support-settings-updated');
    }

    public function storeBlackout(StoreBlackoutRequest $request, SupportBookingManager $bookings): RedirectResponse
    {
        $blackout = SupportBlackout::create($request->validated());
        $result = $bookings->applyBlackout($blackout, SupportActor::staff($request->user()));

        return back()->with('status', $result['cancelled'] > 0 || $result['reassigned'] > 0
            ? 'support-blackout-applied'
            : 'support-settings-updated');
    }

    public function destroyBlackout(SupportBlackout $blackout): RedirectResponse
    {
        $blackout->delete();

        return back()->with('status', 'support-settings-updated');
    }

    private function saveTechnician(SupportTechnician $technician, SaveTechnicianRequest $request): void
    {
        $this->tickets->transaction(function () use ($technician, $request): void {
            $technician->fill([
                'capacity' => $request->integer('capacity'),
                'is_active' => $request->boolean('is_active', true),
            ])->save();

            $technician->shifts()->delete();
            $technician->shifts()->createMany($request->validated('shifts'));
        });
    }
}
