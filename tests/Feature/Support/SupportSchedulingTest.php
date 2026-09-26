<?php

use App\Enums\AppointmentStatus;
use App\Enums\TicketBillingMode;
use App\Enums\TicketBillingStatus;
use App\Enums\TicketChannel;
use App\Models\SupportAppointment;
use App\Models\SupportAttendanceType;
use App\Models\SupportBlackout;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Notifications\Support\AppointmentBooked;
use App\Notifications\Support\AppointmentCancelled;
use App\Services\Support\SupportActor;
use App\Services\Support\SupportAvailability;
use App\Services\Support\SupportBookingManager;
use App\Services\Support\SupportCalendar;
use App\Services\Support\SupportUsage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    seedSupport();
    Notification::fake();
});

afterEach(function () {
    tenancy()->end();
});

function thirtyMinuteType(): SupportAttendanceType
{
    return SupportAttendanceType::where('duration_minutes', 30)->sole();
}

function openTicketFor(Tenant $tenant): SupportTicket
{
    return SupportTicket::factory()->create([
        'tenant_id' => $tenant->id,
        'channel' => TicketChannel::TenantPanel(),
        'covered_by_plan' => true,
        'billing_mode' => TicketBillingMode::Included(),
        'billing_status' => TicketBillingStatus::NotApplicable(),
    ]);
}

test('business minutes skip closed hours and roll over to the next open day', function () {
    $calendar = app(SupportCalendar::class);

    // Monday 19:30 + 60 business minutes = Tuesday 08:30 (open 08:00–20:00).
    expect($calendar->addBusinessMinutes(CarbonImmutable::parse('2026-09-28 19:30'), 60)->toDateTimeString())
        ->toBe('2026-09-29 08:30:00');

    // Friday 19:00 + 120 = Monday 09:00 (weekend is closed).
    expect($calendar->addBusinessMinutes(CarbonImmutable::parse('2026-10-02 19:00'), 120)->toDateTimeString())
        ->toBe('2026-10-05 09:00:00');
});

test('a team-wide holiday removes the whole day from business hours and slots', function () {
    supportTechnician();
    SupportBlackout::create(['starts_at' => '2026-09-29 00:00', 'ends_at' => '2026-09-30 00:00', 'reason' => 'Feriado']);

    expect(app(SupportCalendar::class)->windowsOn(CarbonImmutable::parse('2026-09-29')))->toBe([])
        ->and(app(SupportAvailability::class)->slotsOn(thirtyMinuteType(), CarbonImmutable::parse('2026-09-29')))->toBe([]);
});

test('slots respect minimum notice and the technician shift', function () {
    supportTechnician();

    $slots = collect(app(SupportAvailability::class)->slotsOn(thirtyMinuteType(), CarbonImmutable::parse('2026-09-28')));

    // Now is 09:00 with 2h minimum notice: first slot 11:00, last one 19:30.
    expect($slots->first()['starts_at']->format('H:i'))->toBe('11:00')
        ->and($slots->last()['starts_at']->format('H:i'))->toBe('19:30')
        ->and($slots->first()['available'])->toBe(1);
});

test('a slot stays bookable until every technician on shift reaches capacity', function () {
    [$tenant] = supportTenant();
    supportTechnician(capacity: 2);
    supportTechnician(capacity: 1);

    $bookings = app(SupportBookingManager::class);
    $slot = CarbonImmutable::parse('2026-09-29 10:00');
    $actor = SupportActor::requester('Cliente');

    // Three seats in the slot (2 + 1): the Pro plan allows 2 active per tenant,
    // so spread them over two tenants.
    [$otherTenant] = supportTenant();

    $bookings->book(openTicketFor($tenant), thirtyMinuteType(), $slot, $actor);
    $bookings->book(openTicketFor($tenant), thirtyMinuteType(), $slot, $actor);
    $bookings->book(openTicketFor($otherTenant), thirtyMinuteType(), $slot, $actor);

    expect(SupportAppointment::count())->toBe(3)
        ->and(app(SupportAvailability::class)->isOffered(thirtyMinuteType(), $slot))->toBeFalse()
        ->and(fn () => $bookings->book(openTicketFor($otherTenant), thirtyMinuteType(), $slot, $actor))
        ->toThrow(DomainException::class, 'ya no está disponible');

    $tenant->delete();
    $otherTenant->delete();
});

test('booking assigns the least loaded technician and notifies both sides', function () {
    [$tenant] = supportTenant();
    $busy = supportTechnician(capacity: 2);
    $free = supportTechnician(capacity: 2);

    $bookings = app(SupportBookingManager::class);
    $actor = SupportActor::requester('Cliente');

    $first = $bookings->book(openTicketFor($tenant), thirtyMinuteType(), CarbonImmutable::parse('2026-09-29 10:00'), $actor);
    $second = $bookings->book(openTicketFor($tenant), thirtyMinuteType(), CarbonImmutable::parse('2026-09-29 10:00'), $actor);

    expect([$first->support_technician_id, $second->support_technician_id])
        ->toEqualCanonicalizing([$busy->id, $free->id]);

    Notification::assertSentOnDemand(AppointmentBooked::class);
    Notification::assertSentTo($busy->user, AppointmentBooked::class);

    $tenant->delete();
});

test('the plan caps simultaneous appointments per tenant', function () {
    [$tenant] = supportTenant();
    supportTechnician(capacity: 5);

    $bookings = app(SupportBookingManager::class);
    $actor = SupportActor::requester('Cliente');

    $bookings->book(openTicketFor($tenant), thirtyMinuteType(), CarbonImmutable::parse('2026-09-29 10:00'), $actor);
    $bookings->book(openTicketFor($tenant), thirtyMinuteType(), CarbonImmutable::parse('2026-09-29 11:00'), $actor);

    expect(fn () => $bookings->book(openTicketFor($tenant), thirtyMinuteType(), CarbonImmutable::parse('2026-09-29 12:00'), $actor))
        ->toThrow(DomainException::class, 'cita(s) activa(s)');

    $tenant->delete();
});

test('tenants without the support module cannot book', function () {
    [$tenant] = supportTenant('starter');
    supportTechnician();

    expect(fn () => app(SupportBookingManager::class)->book(
        openTicketFor($tenant),
        thirtyMinuteType(),
        CarbonImmutable::parse('2026-09-29 10:00'),
        SupportActor::requester('Cliente'),
    ))->toThrow(DomainException::class, 'módulo de Soporte');

    $tenant->delete();
});

test('customers cannot cancel inside the notice window but staff can', function () {
    [$tenant] = supportTenant();
    supportTechnician();

    $bookings = app(SupportBookingManager::class);
    $appointment = $bookings->book(openTicketFor($tenant), thirtyMinuteType(), CarbonImmutable::parse('2026-09-28 11:00'), SupportActor::requester('Cliente'));

    $this->travelTo(CarbonImmutable::parse('2026-09-28 10:00'));

    expect(fn () => $bookings->cancel($appointment, SupportActor::requester('Cliente')))
        ->toThrow(DomainException::class, 'hasta 2 h antes');

    $bookings->cancel($appointment, SupportActor::staff(supportStaff()), 'Técnico enfermo');

    expect($appointment->fresh()->status->equals(AppointmentStatus::Cancelled()))->toBeTrue();
    Notification::assertSentOnDemand(AppointmentCancelled::class);

    $tenant->delete();
});

test('an absence moves sessions to a free technician, a team holiday cancels them', function () {
    [$tenant] = supportTenant();
    $absent = supportTechnician();

    $bookings = app(SupportBookingManager::class);
    $actor = SupportActor::staff(supportStaff());
    $appointment = $bookings->book(openTicketFor($tenant), thirtyMinuteType(), CarbonImmutable::parse('2026-09-29 10:00'), $actor);
    $backup = supportTechnician();

    $result = $bookings->applyBlackout(SupportBlackout::create([
        'support_technician_id' => $absent->id,
        'starts_at' => '2026-09-29 00:00',
        'ends_at' => '2026-09-30 00:00',
        'reason' => 'Vacaciones',
    ]), $actor);

    expect($result)->toBe(['reassigned' => 1, 'cancelled' => 0])
        ->and($appointment->fresh()->support_technician_id)->toBe($backup->id);

    $result = $bookings->applyBlackout(SupportBlackout::create([
        'starts_at' => '2026-09-29 00:00',
        'ends_at' => '2026-09-30 00:00',
        'reason' => 'Feriado',
    ]), $actor);

    expect($result)->toBe(['reassigned' => 0, 'cancelled' => 1])
        ->and($appointment->fresh()->status->equals(AppointmentStatus::Cancelled()))->toBeTrue();

    $tenant->delete();
});

test('finished sessions (attended or no-show) consume the plan hours', function () {
    [$tenant] = supportTenant();
    supportTechnician();
    $staff = supportStaff();

    $bookings = app(SupportBookingManager::class);
    $appointment = $bookings->book(openTicketFor($tenant), thirtyMinuteType(), CarbonImmutable::parse('2026-09-28 11:00'), SupportActor::requester('Cliente'));

    $bookings->finish($appointment, AppointmentStatus::NoShow(), $staff);

    $usage = app(SupportUsage::class)->forTenant($tenant);

    expect($usage['used_minutes'])->toBe(30)
        ->and($usage['included_minutes'])->toBe(240)
        ->and($usage['excess_minutes'])->toBe(0);

    $tenant->delete();
});
