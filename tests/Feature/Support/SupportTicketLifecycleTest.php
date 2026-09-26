<?php

use App\Enums\TicketBillingMode;
use App\Enums\TicketBillingStatus;
use App\Enums\TicketCategory;
use App\Enums\TicketChannel;
use App\Enums\TicketEventType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\SupportSetting;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Notifications\Support\RequesterReplied;
use App\Notifications\Support\TicketAssigned;
use App\Notifications\Support\TicketReceived;
use App\Notifications\Support\TicketReplied;
use App\Notifications\Support\TicketResolved;
use App\Services\Support\OpenTicketData;
use App\Services\Support\SupportActor;
use App\Services\Support\SupportTicketManager;
use App\Services\Support\SupportUsage;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    seedSupport();
    Notification::fake();
});

afterEach(function () {
    tenancy()->end();
});

function openSupportTicket(?Tenant $tenant, ?TicketPriority $priority = null, ?TicketChannel $channel = null): SupportTicket
{
    return app(SupportTicketManager::class)->open(new OpenTicketData(
        channel: $channel ?? TicketChannel::TenantPanel(),
        requesterName: 'Ana Cliente',
        requesterEmail: 'Ana@Cliente.test',
        subject: 'No puedo firmar documentos',
        description: 'El botón de firmar no responde.',
        category: TicketCategory::Incident(),
        priority: $priority ?? TicketPriority::Normal(),
        tenant: $tenant,
    ));
}

test('a ticket from a tenant with the support module is covered, has SLA and consumes plan hours', function () {
    [$tenant] = supportTenant('pro');

    $ticket = openSupportTicket($tenant);

    expect($ticket->code())->toBe('SOP-000001')
        ->and($ticket->status->equals(TicketStatus::Open()))->toBeTrue()
        ->and($ticket->covered_by_plan)->toBeTrue()
        ->and($ticket->billing_mode->equals(TicketBillingMode::Included()))->toBeTrue()
        ->and($ticket->billing_status->equals(TicketBillingStatus::NotApplicable()))->toBeTrue()
        ->and($ticket->requester_email)->toBe('ana@cliente.test')
        // Pro: 4 business hours to first response, 24 to resolution (Mon 09:00, open 08–20).
        ->and($ticket->first_response_due_at->toDateTimeString())->toBe('2026-09-28 13:00:00')
        ->and($ticket->resolution_due_at->toDateTimeString())->toBe('2026-09-30 09:00:00')
        ->and($ticket->events()->where('type', TicketEventType::Created()->value)->exists())->toBeTrue();

    Notification::assertSentOnDemand(TicketReceived::class, fn ($notification, $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'ana@cliente.test');

    $tenant->delete();
});

test('priority scales the plan SLA', function () {
    [$tenant] = supportTenant('pro');

    $ticket = openSupportTicket($tenant, TicketPriority::Urgent());

    // Urgent = 25% of 4h = 1 business hour.
    expect($ticket->first_response_due_at->toDateTimeString())->toBe('2026-09-28 10:00:00');

    app(SupportTicketManager::class)->changePriority($ticket, TicketPriority::Low(), SupportActor::system());

    // Low = 200% of 4h = 8 business hours.
    expect($ticket->fresh()->first_response_due_at->toDateTimeString())->toBe('2026-09-28 17:00:00');

    $tenant->delete();
});

test('without the support module a ticket is billable by hours and has no SLA', function () {
    [$tenant] = supportTenant('starter');

    $ticket = openSupportTicket($tenant);

    expect($ticket->covered_by_plan)->toBeFalse()
        ->and($ticket->billing_mode->equals(TicketBillingMode::Hourly()))->toBeTrue()
        ->and($ticket->billing_status->equals(TicketBillingStatus::Pending()))->toBeTrue()
        ->and($ticket->first_response_due_at)->toBeNull();

    $tenant->delete();
});

test('a public ticket starts untriaged and linking a tenant recomputes coverage', function () {
    [$tenant] = supportTenant('pro');

    $ticket = openSupportTicket(null, channel: TicketChannel::PublicWeb());

    expect($ticket->status->equals(TicketStatus::New()))->toBeTrue()
        ->and($ticket->tenant_id)->toBeNull();

    app(SupportTicketManager::class)->linkTenant($ticket, $tenant, SupportActor::staff(supportStaff()));
    $ticket->refresh();

    expect($ticket->tenant_id)->toBe($tenant->id)
        ->and($ticket->status->equals(TicketStatus::Open()))->toBeTrue()
        ->and($ticket->covered_by_plan)->toBeTrue()
        ->and($ticket->billing_mode->equals(TicketBillingMode::Included()))->toBeTrue()
        ->and($ticket->first_response_due_at)->not->toBeNull();

    $tenant->delete();
});

test('staff replies notify the requester; internal notes never do', function () {
    [$tenant] = supportTenant();
    $staff = supportStaff();
    $manager = app(SupportTicketManager::class);
    $ticket = openSupportTicket($tenant);

    $manager->reply($ticket, SupportActor::staff($staff), 'Revisando logs', isInternal: true);
    Notification::assertNotSentTo(new AnonymousNotifiable, TicketReplied::class);
    expect($ticket->fresh()->first_responded_at)->toBeNull();

    $manager->reply($ticket, SupportActor::staff($staff), '¿Qué navegador usas?');
    $ticket->refresh();

    expect($ticket->first_responded_at)->not->toBeNull()
        ->and($ticket->status->equals(TicketStatus::WaitingOnCustomer()))->toBeTrue();
    Notification::assertSentOnDemand(TicketReplied::class);

    expect(fn () => $manager->reply($ticket, SupportActor::requester('Ana'), 'nota', isInternal: true))
        ->toThrow(DomainException::class);

    $tenant->delete();
});

test('assignment notifies the assignee and a requester reply notifies them back', function () {
    [$tenant] = supportTenant();
    $lead = supportStaff();
    $agent = supportStaff('support');
    $manager = app(SupportTicketManager::class);
    $ticket = openSupportTicket($tenant);

    $manager->assign($ticket, $agent, SupportActor::staff($lead));
    Notification::assertSentTo($agent, TicketAssigned::class);
    expect($ticket->fresh()->status->equals(TicketStatus::InProgress()))->toBeTrue();

    $manager->reply($ticket->fresh(), SupportActor::staff($agent), 'Prueba con Chrome');
    $manager->reply($ticket->fresh(), SupportActor::requester('Ana'), 'Sigue igual');

    Notification::assertSentTo($agent, RequesterReplied::class);
    expect($ticket->fresh()->status->equals(TicketStatus::InProgress()))->toBeTrue();

    $tenant->delete();
});

test('resolving sends the rating link and the rating is credited to the resolver', function () {
    [$tenant] = supportTenant();
    $agent = supportStaff('support');
    $manager = app(SupportTicketManager::class);
    $ticket = openSupportTicket($tenant);

    $manager->changeStatus($ticket, TicketStatus::Resolved(), SupportActor::staff($agent));
    Notification::assertSentOnDemand(TicketResolved::class);

    $rating = $manager->rate($ticket->fresh(), 5, true, 'Excelente');

    expect($rating->central_user_id)->toBe($agent->id)
        ->and($ticket->fresh()->status->equals(TicketStatus::Resolved()))->toBeTrue()
        ->and(fn () => $manager->rate($ticket->fresh(), 4, true, null))->toThrow(DomainException::class, 'ya fue calificado');

    $tenant->delete();
});

test('rating a ticket as not resolved reopens it', function () {
    [$tenant] = supportTenant();
    $manager = app(SupportTicketManager::class);
    $ticket = openSupportTicket($tenant);

    $manager->changeStatus($ticket, TicketStatus::Resolved(), SupportActor::staff(supportStaff()));
    $manager->rate($ticket->fresh(), 2, false, 'No funcionó');

    $ticket->refresh();

    expect($ticket->isOpen())->toBeTrue()
        ->and($ticket->resolved_at)->toBeNull()
        ->and($ticket->events()->where('type', TicketEventType::Reopened()->value)->exists())->toBeTrue();

    $tenant->delete();
});

test('a requester reply reopens a resolved ticket only within the window', function () {
    [$tenant] = supportTenant();
    $manager = app(SupportTicketManager::class);
    $ticket = openSupportTicket($tenant);

    $manager->changeStatus($ticket, TicketStatus::Resolved(), SupportActor::staff(supportStaff()));
    $manager->reply($ticket->fresh(), SupportActor::requester('Ana'), 'Volvió a fallar');
    expect($ticket->fresh()->isOpen())->toBeTrue();

    $manager->changeStatus($ticket->fresh(), TicketStatus::Resolved(), SupportActor::staff(supportStaff()));
    $this->travel(8)->days();

    expect(fn () => $manager->reply($ticket->fresh(), SupportActor::requester('Ana'), 'Hola'))
        ->toThrow(DomainException::class, 'plazo para reabrir');

    $tenant->delete();
});

test('stale resolved tickets are closed by the scheduled command', function () {
    [$tenant] = supportTenant();
    $ticket = openSupportTicket($tenant);
    app(SupportTicketManager::class)->changeStatus($ticket, TicketStatus::Resolved(), SupportActor::staff(supportStaff()));

    $this->travel(6)->days();
    $this->artisan('support:close-resolved')->assertSuccessful();

    expect($ticket->fresh()->status->equals(TicketStatus::Closed()))->toBeTrue()
        ->and(fn () => app(SupportTicketManager::class)->reply($ticket->fresh(), SupportActor::requester('Ana'), 'Hola'))
        ->toThrow(DomainException::class, 'cerrado');

    $tenant->delete();
});

test('billing: fixed amount is required, excess plan hours are charged at the global rate', function () {
    [$tenant] = supportTenant();
    $staff = supportStaff();
    $manager = app(SupportTicketManager::class);
    SupportSetting::current()->update(['hourly_rate' => 30]);

    $included = openSupportTicket($tenant);
    $manager->logTime($included, $staff, 300, 'Implementación', true, now());
    $manager->logTime($included, $staff, 60, 'Investigación interna', false, now());

    $fixed = openSupportTicket($tenant);

    expect(fn () => $manager->updateBilling($fixed, SupportActor::staff($staff), TicketBillingMode::Fixed(), null, 'Migración', null))
        ->toThrow(DomainException::class, 'monto fijo');

    $manager->updateBilling($fixed, SupportActor::staff($staff), TicketBillingMode::Fixed(), '150.00', 'Migración de datos', null);

    $usage = app(SupportUsage::class)->forTenant($tenant);

    // 300 billable min used vs 240 included → 60 min excess × $30/h + $150 fixed.
    expect($usage['used_minutes'])->toBe(300)
        ->and($usage['excess_minutes'])->toBe(60)
        ->and($usage['fixed_amount'])->toBe(150.0)
        ->and($usage['amount_due'])->toBe(180.0);

    $manager->updateBilling($fixed->fresh(), SupportActor::staff($staff), TicketBillingMode::Fixed(), '150.00', 'Migración de datos', 'FAC-001-001-000123');

    expect($fixed->fresh()->billing_status->equals(TicketBillingStatus::Invoiced()))->toBeTrue()
        ->and(app(SupportUsage::class)->forTenant($tenant)['fixed_amount'])->toBe(0.0);

    $tenant->delete();
});
