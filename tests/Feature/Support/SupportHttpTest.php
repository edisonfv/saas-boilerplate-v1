<?php

use App\Enums\TicketChannel;
use App\Enums\TicketStatus;
use App\Models\SupportAppointment;
use App\Models\SupportAttendanceType;
use App\Models\SupportBlackout;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Support\TicketReceived;
use App\Services\Support\SupportActor;
use App\Services\Support\SupportLinks;
use App\Services\Support\SupportTicketManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    seedSupport();
    Notification::fake();
    Storage::fake('attachments');
});

afterEach(function () {
    tenancy()->end();
});

function tenantTicket(Tenant $tenant, User $requester): SupportTicket
{
    return SupportTicket::factory()->create([
        'tenant_id' => $tenant->id,
        'channel' => TicketChannel::TenantPanel(),
        'requester_tenant_user_id' => (string) $requester->id,
        'requester_name' => $requester->name,
        'requester_email' => $requester->email,
    ]);
}

// --- Central console -------------------------------------------------------

test('staff without support permissions cannot open the support desk', function () {
    $this->actingAs(supportStaff('sales'), 'central')
        ->get(route('central.support.tickets.index'))
        ->assertForbidden();
});

test('the support role works tickets but cannot change billing', function () {
    $ticket = SupportTicket::factory()->create();
    $agent = supportStaff('support');

    $this->actingAs($agent, 'central')
        ->get(route('central.support.tickets.show', $ticket))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Central/Support/Tickets/Show')
            ->where('ticket.code', $ticket->code())
            ->where('can.billing', false));

    $this->actingAs($agent, 'central')
        ->patch(route('central.support.tickets.billing', $ticket), [
            'billing_mode' => 'NonBillable',
            'billing_reason' => 'Cortesía',
        ])
        ->assertForbidden();
});

test('staff registers a ticket on behalf of a tenant with attachments', function () {
    [$tenant] = supportTenant();
    $staff = supportStaff();

    $response = $this->actingAs($staff, 'central')->post(route('central.support.tickets.store'), [
        'tenant_id' => $tenant->id,
        'requester_name' => 'Luis Cliente',
        'requester_email' => 'luis@acme.test',
        'subject' => 'Llamada: error al exportar',
        'description' => 'El cliente llamó reportando un error.',
        'category' => 'Incident',
        'priority' => 'High',
        'attachments' => [UploadedFile::fake()->create('captura.png', 100, 'image/png')],
    ]);

    $ticket = SupportTicket::sole();

    $response->assertRedirect(route('central.support.tickets.show', $ticket));
    expect($ticket->channel->equals(TicketChannel::Staff()))->toBeTrue()
        ->and($ticket->created_by)->toBe($staff->id)
        ->and($ticket->attachments()->count())->toBe(1);
    Storage::disk('attachments')->assertExists($ticket->attachments()->sole()->path);

    $tenant->delete();
});

test('the queue lists open tickets by default and filters by assignee', function () {
    $staff = supportStaff();
    SupportTicket::factory()->create(['assigned_to' => $staff->id, 'subject' => 'Mío']);
    SupportTicket::factory()->create(['subject' => 'Sin asignar']);
    SupportTicket::factory()->resolved()->create(['subject' => 'Resuelto']);

    $this->actingAs($staff, 'central')
        ->get(route('central.support.tickets.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Central/Support/Tickets/Index')
            ->has('tickets.data', 2)
            ->where('stats.mine', 1));

    $this->actingAs($staff, 'central')
        ->get(route('central.support.tickets.index', ['filter' => ['assignee' => 'me']]))
        ->assertInertia(fn (Assert $page) => $page->has('tickets.data', 1)->where('tickets.data.0.subject', 'Mío'));

    $this->actingAs($staff, 'central')
        ->get(route('central.support.tickets.index', ['filter' => ['status' => '']]))
        ->assertInertia(fn (Assert $page) => $page->has('tickets.data', 3));
});

test('staff can configure technicians and register a holiday from the agenda', function () {
    $staff = supportStaff();
    $member = supportStaff('support');

    $this->actingAs($staff, 'central')
        ->get(route('central.support.schedule.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Central/Support/Schedule/Index')->has('attendanceTypes', 3));

    $this->actingAs($staff, 'central')->post(route('central.support.technicians.store'), [
        'central_user_id' => $member->id,
        'capacity' => 3,
        'shifts' => [['weekday' => 1, 'starts_at' => '08:00', 'ends_at' => '14:00']],
    ])->assertSessionHasNoErrors();

    expect($member->supportTechnician->capacity)->toBe(3)
        ->and($member->supportTechnician->shifts)->toHaveCount(1);

    $this->actingAs($staff, 'central')->post(route('central.support.blackouts.store'), [
        'starts_at' => '2026-10-09 00:00',
        'ends_at' => '2026-10-10 00:00',
        'reason' => 'Independencia de Guayaquil',
    ])->assertSessionHasNoErrors();

    expect(SupportBlackout::count())->toBe(1);
});

test('the reports page summarizes billing and satisfaction', function () {
    $this->actingAs(supportStaff(), 'central')
        ->get(route('central.support.reports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Central/Support/Reports/Index')->has('overall'));
});

// --- Tenant workspace ------------------------------------------------------

test('a tenant without the support module gets a 403 in the workspace', function () {
    [$tenant, $domain] = supportTenant('starter');
    $owner = supportTenantUser($tenant);

    $this->actingAs($owner, 'web')->get("http://{$domain}/mi-soporte")->assertForbidden();

    $tenant->delete();
});

test('users see their own tickets and the tenant admin sees the whole company', function () {
    [$tenant, $domain] = supportTenant();
    $owner = supportTenantUser($tenant);
    $member = supportTenantUser($tenant, 'member', ['tenant.support-tickets.view', 'tenant.support-tickets.create']);

    $ownTicket = tenantTicket($tenant, $member);
    $ownerTicket = tenantTicket($tenant, $owner);

    $this->actingAs($member, 'web')->get("http://{$domain}/mi-soporte")
        ->assertInertia(fn (Assert $page) => $page
            ->component('Support/Tickets/Index')
            ->has('tickets.data', 1)
            ->where('tickets.data.0.id', $ownTicket->id)
            ->where('seesAll', false));

    $this->actingAs($member, 'web')->get("http://{$domain}/mi-soporte/tickets/{$ownerTicket->id}")->assertNotFound();

    $this->actingAs($owner, 'web')->get("http://{$domain}/mi-soporte")
        ->assertInertia(fn (Assert $page) => $page->has('tickets.data', 2)->where('seesAll', true));

    $tenant->delete();
});

test('another company cannot read a ticket even knowing its id', function () {
    [$tenant] = supportTenant();
    [$otherTenant, $otherDomain] = supportTenant();
    $ticket = tenantTicket($tenant, supportTenantUser($tenant));
    $stranger = supportTenantUser($otherTenant);

    $this->actingAs($stranger, 'web')->get("http://{$otherDomain}/mi-soporte/tickets/{$ticket->id}")->assertNotFound();

    $tenant->delete();
    $otherTenant->delete();
});

test('opening a ticket from the workspace snapshots the requester and never shows internal notes', function () {
    [$tenant, $domain] = supportTenant();
    $owner = supportTenantUser($tenant);

    $this->actingAs($owner, 'web')->post("http://{$domain}/mi-soporte/tickets", [
        'subject' => 'No llegan los correos',
        'description' => 'Desde ayer no recibimos notificaciones.',
        'category' => 'Incident',
        'priority' => 'Normal',
    ]);

    tenancy()->end();
    $ticket = SupportTicket::sole();

    expect($ticket->tenant_id)->toBe($tenant->id)
        ->and($ticket->requester_tenant_user_id)->toBe((string) $owner->id)
        ->and($ticket->requester_email)->toBe(strtolower($owner->email))
        ->and($ticket->covered_by_plan)->toBeTrue();

    app(SupportTicketManager::class)->reply($ticket, SupportActor::staff(supportStaff()), 'Posible bug en el mailer', isInternal: true);

    $this->actingAs($owner, 'web')->get("http://{$domain}/mi-soporte/tickets/{$ticket->id}")
        ->assertInertia(fn (Assert $page) => $page->component('Support/Tickets/Show')->has('ticket.messages', 0));

    $tenant->delete();
});

test('a tenant user books a session from the workspace', function () {
    [$tenant, $domain] = supportTenant();
    supportTechnician();
    $owner = supportTenantUser($tenant);
    $ticket = tenantTicket($tenant, $owner);
    $ticket->update(['covered_by_plan' => true]);
    $type = SupportAttendanceType::where('duration_minutes', 60)->sole();

    $this->actingAs($owner, 'web')
        ->get("http://{$domain}/mi-soporte/tickets/{$ticket->id}/agendar?type={$type->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->component('Support/Appointments/Create')
            ->where('typeId', $type->id)
            ->where('date', '2026-09-28')
            ->has('slots'));

    $this->actingAs($owner, 'web')->post("http://{$domain}/mi-soporte/tickets/{$ticket->id}/citas", [
        'support_attendance_type_id' => $type->id,
        'starts_at' => '2026-09-29T10:00:00+00:00',
    ])->assertRedirect(route('tenant.support.tickets.show', $ticket));

    expect(SupportAppointment::sole()->ends_at->toDateTimeString())->toBe('2026-09-29 11:00:00');

    $tenant->delete();
});

// --- Public channel ---------------------------------------------------------

test('a guest opens a ticket on the central site and follows it with the token', function () {
    $this->get(route('support.public.create'))->assertOk();

    $this->post(route('support.public.store'), [
        'requester_name' => 'Prospecto',
        'requester_email' => 'prospecto@empresa.test',
        'requester_company' => 'Empresa X',
        'subject' => 'Quiero una demo',
        'description' => '¿Pueden mostrarme el módulo de firmas?',
        'category' => 'Question',
    ])->assertRedirect();

    $ticket = SupportTicket::sole();

    expect($ticket->channel->equals(TicketChannel::PublicWeb()))->toBeTrue()
        ->and($ticket->tenant_id)->toBeNull()
        ->and($ticket->status->equals(TicketStatus::New()))->toBeTrue();
    Notification::assertSentOnDemand(TicketReceived::class);

    $this->get(route('support.public.tickets.show', $ticket))->assertNotFound();
    $this->get(route('support.public.tickets.show', [$ticket, 'token' => 'wrong']))->assertNotFound();
    $this->get(route('support.public.tickets.show', [$ticket, 'token' => $ticket->access_token]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Support/Public/Show'));

    $this->post(route('support.public.tickets.reply', [$ticket, 'token' => $ticket->access_token]), [
        'body' => 'Agrego más detalles',
    ])->assertSessionHasNoErrors();

    expect($ticket->messages()->count())->toBe(1);
});

test('the honeypot rejects bots', function () {
    $this->post(route('support.public.store'), [
        'requester_name' => 'Bot',
        'requester_email' => 'bot@spam.test',
        'subject' => 'Oferta',
        'description' => 'Compra ya',
        'category' => 'Other',
        'website' => 'http://spam.test',
    ])->assertSessionHasErrors('website');

    expect(SupportTicket::count())->toBe(0);
});

test('the help form on a tenant domain links the ticket to that tenant', function () {
    [$tenant, $domain] = supportTenant('starter');

    $this->get("http://{$domain}/ayuda")->assertOk();

    $this->post("http://{$domain}/ayuda", [
        'requester_name' => 'Usuario bloqueado',
        'requester_email' => 'user@acme.test',
        'subject' => 'No puedo entrar',
        'description' => 'Olvidé mi contraseña y no llega el correo.',
        'category' => 'Question',
    ])->assertRedirect();

    $ticket = SupportTicket::sole();

    expect($ticket->tenant_id)->toBe($tenant->id)
        ->and($ticket->channel->equals(TicketChannel::TenantPublic()))->toBeTrue()
        // Starter has no Support module: billable by hours, no SLA.
        ->and($ticket->covered_by_plan)->toBeFalse();

    $tenant->delete();
});

test('the rating page only accepts valid signed links', function () {
    $ticket = SupportTicket::factory()->resolved()->create();
    $link = app(SupportLinks::class)->rating($ticket, 7);
    $path = parse_url($link, PHP_URL_PATH).'?'.parse_url($link, PHP_URL_QUERY);

    $this->get(route('support.public.rating.show', $ticket))->assertForbidden();

    $this->get($path)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Support/Public/Rate')
        ->where('alreadyRated', false));

    $this->post($path, ['stars' => 4, 'was_resolved' => true, 'comment' => 'Rápido'])->assertSessionHasNoErrors();

    expect($ticket->rating->stars)->toBe(4);
});

test('the tenant workspace receives its contracted modules for the navigation', function () {
    [$tenant, $domain] = supportTenant();
    $owner = supportTenantUser($tenant);

    $this->actingAs($owner, 'web')->get("http://{$domain}/dashboard")
        ->assertInertia(fn (Assert $page) => $page->where('tenant.modules', fn ($modules) => collect($modules)->contains('support')));

    $tenant->delete();
});
