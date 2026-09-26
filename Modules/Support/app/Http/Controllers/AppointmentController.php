<?php

namespace Modules\Support\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SupportAppointment;
use App\Models\SupportAttendanceType;
use App\Models\SupportSetting;
use App\Models\SupportTicket;
use App\Services\Support\SupportActor;
use App\Services\Support\SupportAvailability;
use App\Services\Support\SupportBookingManager;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Support\Http\Requests\BookTenantAppointmentRequest;

/**
 * Self-service booking of support sessions from a tenant workspace. Every
 * session belongs to a ticket the user can see — enforced on the routes by
 * SupportTicketPolicy::participate ("can" middleware).
 */
class AppointmentController extends Controller
{
    public function __construct(private SupportBookingManager $bookings) {}

    public function create(Request $request, SupportTicket $ticket, SupportAvailability $availability): Response
    {
        $types = SupportAttendanceType::query()->where('is_active', true)->orderBy('duration_minutes')->get();
        $type = $types->firstWhere('id', $request->string('type')->toString()) ?? $types->first();
        $dates = $type ? $availability->availableDates($type) : [];
        $date = in_array($request->string('date')->toString(), $dates, true)
            ? $request->string('date')->toString()
            : ($dates[0] ?? null);
        $settings = SupportSetting::current();

        return Inertia::render('Support/Appointments/Create', [
            'ticket' => ['id' => $ticket->id, 'code' => $ticket->code(), 'subject' => $ticket->subject],
            'types' => $types->map(fn (SupportAttendanceType $item) => $item->only(['id', 'name', 'description', 'duration_minutes'])),
            'typeId' => $type?->id,
            'dates' => $dates,
            'date' => $date,
            'slots' => $type && $date
                ? collect($availability->slotsOn($type, CarbonImmutable::parse($date)))->map(fn (array $slot) => [
                    'starts_at' => $slot['starts_at']->toIso8601String(),
                    'available' => $slot['available'],
                ])
                : [],
            'rules' => [
                'min_notice_hours' => $settings->booking_min_notice_hours,
                'max_days_ahead' => $settings->booking_max_days_ahead,
                'cancellation_notice_hours' => $settings->cancellation_notice_hours,
            ],
        ]);
    }

    public function store(BookTenantAppointmentRequest $request, SupportTicket $ticket): RedirectResponse
    {
        try {
            $this->bookings->book(
                $ticket,
                SupportAttendanceType::findOrFail($request->string('support_attendance_type_id')->toString()),
                CarbonImmutable::parse($request->string('starts_at')->toString()),
                SupportActor::requester($request->user()->name),
            );
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['starts_at' => $exception->getMessage()]);
        }

        return redirect()->route('tenant.support.tickets.show', $ticket)->with('status', 'support-appointment-booked');
    }

    public function destroy(Request $request, SupportTicket $ticket, SupportAppointment $appointment): RedirectResponse
    {
        // {appointment} is resolved scoped to {ticket} (scopeBindings on the route).
        try {
            $this->bookings->cancel($appointment, SupportActor::requester($request->user()->name), 'Cancelada por el cliente');
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['appointment' => $exception->getMessage()]);
        }

        return back()->with('status', 'support-appointment-cancelled');
    }
}
