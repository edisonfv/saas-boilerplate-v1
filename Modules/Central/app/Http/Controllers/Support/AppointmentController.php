<?php

namespace Modules\Central\Http\Controllers\Support;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\SupportAppointment;
use App\Models\SupportAttendanceType;
use App\Models\SupportTicket;
use App\Services\Support\SupportActor;
use App\Services\Support\SupportBookingManager;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Central\Http\Requests\Support\BookAppointmentRequest;

/**
 * Staff-side session management: book on a customer's behalf, add the
 * meeting link, close as attended/no-show, or cancel.
 */
class AppointmentController extends Controller
{
    public function __construct(private SupportBookingManager $bookings) {}

    public function store(BookAppointmentRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->attempt(fn () => $this->bookings->book(
            $ticket,
            SupportAttendanceType::findOrFail($request->string('support_attendance_type_id')->toString()),
            CarbonImmutable::parse($request->string('starts_at')->toString()),
            SupportActor::staff($request->user()),
        ), 'starts_at');

        return back()->with('status', 'support-appointment-booked');
    }

    public function update(Request $request, SupportAppointment $appointment): RedirectResponse
    {
        $validated = $request->validate([
            'meeting_url' => ['nullable', 'url:https', 'max:500'],
        ]);

        $this->bookings->setMeetingUrl($appointment, $validated['meeting_url'] ?? null, SupportActor::staff($request->user()));

        return back()->with('status', 'support-appointment-updated');
    }

    public function finish(Request $request, SupportAppointment $appointment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in([AppointmentStatus::Completed()->value, AppointmentStatus::NoShow()->value])],
        ]);

        $this->attempt(fn () => $this->bookings->finish(
            $appointment,
            AppointmentStatus::from($validated['status']),
            $request->user(),
        ), 'status');

        return back()->with('status', 'support-appointment-updated');
    }

    public function destroy(Request $request, SupportAppointment $appointment): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->attempt(fn () => $this->bookings->cancel(
            $appointment,
            SupportActor::staff($request->user()),
            $validated['reason'] ?? null,
        ), 'reason');

        return back()->with('status', 'support-appointment-cancelled');
    }

    private function attempt(callable $action, string $field): void
    {
        try {
            $action();
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([$field => $exception->getMessage()]);
        }
    }
}
