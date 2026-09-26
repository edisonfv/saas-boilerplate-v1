<?php

use App\Enums\TicketChannel;
use App\Models\Attachment;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Attachments\AttachmentStore;
use App\Services\Support\SupportActor;
use App\Services\Support\SupportTicketManager;
use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    seedSupport();
    Notification::fake();
    Storage::fake('attachments');
});

afterEach(function () {
    tenancy()->end();
});

function ticketWithFiles(Tenant $tenant, User $requester): array
{
    $ticket = SupportTicket::factory()->create([
        'tenant_id' => $tenant->id,
        'channel' => TicketChannel::TenantPanel(),
        'requester_tenant_user_id' => (string) $requester->id,
    ]);

    $store = app(AttachmentStore::class);
    $store->store($ticket, [UploadedFile::fake()->create('contrato.pdf', 50, 'application/pdf')]);

    $manager = app(SupportTicketManager::class);
    $staff = supportStaff();
    $public = $manager->reply($ticket, SupportActor::staff($staff), 'Te adjunto la guía', [UploadedFile::fake()->create('guia.pdf', 10)]);
    $internal = $manager->reply($ticket->fresh(), SupportActor::staff($staff), 'Log interno', [UploadedFile::fake()->create('log.txt', 5)], isInternal: true);

    return [
        $ticket,
        $ticket->attachments()->sole(),
        $public->attachments()->sole(),
        $internal->attachments()->sole(),
    ];
}

test('attachments are polymorphic and store morph aliases, not class names', function () {
    [$tenant] = supportTenant();
    [$ticket, $ticketFile, $messageFile] = ticketWithFiles($tenant, supportTenantUser($tenant));

    expect($ticketFile->attachable_type)->toBe('support_ticket')
        ->and($ticketFile->attachable->is($ticket))->toBeTrue()
        ->and($messageFile->attachable_type)->toBe('support_ticket_message')
        ->and($messageFile->attachable)->toBeInstanceOf(SupportTicketMessage::class)
        ->and(Attachment::count())->toBe(3);

    Storage::disk('attachments')->assertExists($ticketFile->path);

    $tenant->delete();
});

test('the morph map is enforced for polymorphic models, including Spatie role holders', function () {
    $staff = supportStaff();

    expect($staff->roles()->getMorphClass())->toBe('central_user')
        ->and(DB::table('model_has_roles')->where('model_uuid', $staff->id)->value('model_type'))->toBe('central_user');

    $unmapped = new class extends Model {};

    expect(fn () => $unmapped->getMorphClass())->toThrow(ClassMorphViolationException::class);
});

test('the requester downloads ticket and reply files but never internal-note files', function () {
    [$tenant, $domain] = supportTenant();
    $owner = supportTenantUser($tenant);
    [, $ticketFile, $messageFile, $internalFile] = ticketWithFiles($tenant, $owner);

    $this->actingAs($owner, 'web')->get("http://{$domain}/mi-soporte/adjuntos/{$ticketFile->id}")->assertOk();
    $this->actingAs($owner, 'web')->get("http://{$domain}/mi-soporte/adjuntos/{$messageFile->id}")->assertOk();
    $this->actingAs($owner, 'web')->get("http://{$domain}/mi-soporte/adjuntos/{$internalFile->id}")->assertNotFound();

    $tenant->delete();
});

test('another company cannot download a file even knowing its id', function () {
    [$tenant] = supportTenant();
    [$otherTenant, $otherDomain] = supportTenant();
    [, $ticketFile] = ticketWithFiles($tenant, supportTenantUser($tenant));
    $stranger = supportTenantUser($otherTenant);

    $this->actingAs($stranger, 'web')->get("http://{$otherDomain}/mi-soporte/adjuntos/{$ticketFile->id}")->assertNotFound();

    $tenant->delete();
    $otherTenant->delete();
});

test('staff downloads through the generic central route only with the support permission', function () {
    [$tenant] = supportTenant();
    [, , , $internalFile] = ticketWithFiles($tenant, supportTenantUser($tenant));
    tenancy()->end();

    $this->actingAs(supportStaff('support'), 'central')
        ->get(route('central.attachments.show', $internalFile))
        ->assertOk();

    $this->actingAs(supportStaff('sales'), 'central')
        ->get(route('central.attachments.show', $internalFile))
        ->assertNotFound();

    $tenant->delete();
});

test('guests with the token reach public files but not internal ones', function () {
    [$tenant] = supportTenant();
    [$ticket, $ticketFile, , $internalFile] = ticketWithFiles($tenant, supportTenantUser($tenant));
    tenancy()->end();

    $this->get(route('support.public.attachments.show', [$ticket, $ticketFile, 'token' => $ticket->access_token]))->assertOk();
    $this->get(route('support.public.attachments.show', [$ticket, $internalFile, 'token' => $ticket->access_token]))->assertNotFound();
    $this->get(route('support.public.attachments.show', [$ticket, $ticketFile]))->assertNotFound();

    $tenant->delete();
});
