<?php

namespace Modules\Support\Http\Controllers;

use App\Enums\TicketCategory;
use App\Enums\TicketChannel;
use App\Enums\TicketPriority;
use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\Tenant;
use App\Services\Attachments\AttachmentStore;
use App\Services\Support\OpenTicketData;
use App\Services\Support\SupportActor;
use App\Services\Support\SupportLinks;
use App\Services\Support\SupportTicketManager;
use App\Services\Support\SupportTicketPresenter;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Support\Http\Requests\RateTicketRequest;
use Modules\Support\Http\Requests\ReplyAsRequesterRequest;
use Modules\Support\Http\Requests\StorePublicTicketRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Guest channel: anyone can leave a ticket without an account — on the
 * central site, or on a tenant's own domain (e.g. from its login page when
 * they can't sign in), in which case the ticket is linked to that tenant.
 * Guests follow the ticket with a secret token and rate it via a signed link.
 */
class PublicTicketController extends Controller
{
    public function __construct(private SupportTicketManager $manager) {}

    public function create(): Response
    {
        return Inertia::render('Support/Public/Create', [
            'categories' => TicketCategory::toArray(),
            'storeUrl' => tenant() ? route('tenant.support.public.store') : route('support.public.store'),
            'tenantName' => $this->currentTenant()?->company_name,
        ]);
    }

    public function store(StorePublicTicketRequest $request, SupportLinks $links): SymfonyResponse
    {
        $tenant = $this->currentTenant();

        $ticket = $this->manager->open(new OpenTicketData(
            channel: $tenant ? TicketChannel::TenantPublic() : TicketChannel::PublicWeb(),
            requesterName: $request->string('requester_name')->toString(),
            requesterEmail: $request->string('requester_email')->toString(),
            subject: $request->string('subject')->toString(),
            description: $request->string('description')->toString(),
            category: TicketCategory::from($request->string('category')->toString()),
            priority: TicketPriority::Normal(),
            tenant: $tenant,
            requesterCompany: $request->input('requester_company') ?? $tenant?->company_name,
            attachments: $request->file('attachments', []),
        ));

        // The tracking page lives on the central domain, which may be another
        // host than this form (tenant domain): a hard location visit.
        return Inertia::location($links->forRequester($ticket));
    }

    public function show(Request $request, SupportTicket $ticket, SupportTicketPresenter $presenter): Response
    {
        $token = $this->authorizeToken($request, $ticket);

        return Inertia::render('Support/Public/Show', [
            'ticket' => $presenter->forRequester(
                $ticket,
                fn (Attachment $attachment) => route('support.public.attachments.show', [$ticket, $attachment, 'token' => $token]),
            ),
            'token' => $token,
            'canReopen' => $this->manager->canReopen($ticket),
        ]);
    }

    public function reply(ReplyAsRequesterRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorizeToken($request, $ticket);

        try {
            $this->manager->reply(
                $ticket,
                SupportActor::requester($ticket->requester_name),
                $request->string('body')->toString(),
                $request->file('attachments', []),
            );
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['body' => $exception->getMessage()]);
        }

        return back()->with('status', 'support-reply-sent');
    }

    public function attachment(Request $request, SupportTicket $ticket, Attachment $attachment, AttachmentStore $store): StreamedResponse
    {
        $this->authorizeToken($request, $ticket);

        // Guests have no user for AttachmentPolicy: only files on this ticket
        // or its public (non-internal) messages are reachable with the token.
        $owner = $attachment->attachable;
        $ownerTicket = match (true) {
            $owner instanceof SupportTicket => $owner,
            $owner instanceof SupportTicketMessage && ! $owner->is_internal => $owner->ticket,
            default => null,
        };

        abort_unless($ownerTicket?->is($ticket), 404);

        return $store->download($attachment);
    }

    public function rating(SupportTicket $ticket): Response
    {
        return Inertia::render('Support/Public/Rate', [
            'ticket' => ['code' => $ticket->code(), 'subject' => $ticket->subject, 'resolver_name' => $ticket->resolver?->name],
            'alreadyRated' => $ticket->rating()->exists(),
            'isResolved' => ! $ticket->isOpen(),
            'submitUrl' => request()->fullUrl(),
        ]);
    }

    public function rate(RateTicketRequest $request, SupportTicket $ticket): RedirectResponse
    {
        try {
            $this->manager->rate($ticket, $request->integer('stars'), $request->boolean('was_resolved'), $request->input('comment'));
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['stars' => $exception->getMessage()]);
        }

        return back()->with('status', 'support-rated');
    }

    /**
     * 404 on a missing or wrong token, so ticket ids can't be probed.
     */
    private function authorizeToken(Request $request, SupportTicket $ticket): string
    {
        $token = $request->string('token')->toString();

        abort_unless($ticket->hasValidAccessToken($token), 404);

        return $token;
    }

    private function currentTenant(): ?Tenant
    {
        $tenant = tenant();

        return $tenant instanceof Tenant ? $tenant : null;
    }
}
