<?php

namespace Modules\Support\Http\Controllers;

use App\Enums\TicketCategory;
use App\Enums\TicketChannel;
use App\Enums\TicketPriority;
use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Attachments\AttachmentStore;
use App\Services\Support\OpenTicketData;
use App\Services\Support\SupportActor;
use App\Services\Support\SupportTicketManager;
use App\Services\Support\SupportTicketPresenter;
use App\Services\Support\SupportUsage;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Support\Http\Requests\RateTicketRequest;
use Modules\Support\Http\Requests\ReplyAsRequesterRequest;
use Modules\Support\Http\Requests\StoreTenantTicketRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "Mis solicitudes" inside a tenant workspace. Listings use the
 * SupportTicket::visibleTo() scope; single tickets and files are authorized
 * on the routes by SupportTicketPolicy / AttachmentPolicy ("can" middleware).
 */
class TicketController extends Controller
{
    public function __construct(private SupportTicketManager $manager) {}

    public function index(Request $request, SupportTicketPresenter $presenter, SupportUsage $usage): Response
    {
        /** @var User $user */
        $user = $request->user();

        $tickets = SupportTicket::query()
            ->visibleTo($user)
            ->when($request->string('status')->toString() === 'closed',
                fn (Builder $query) => $query->whereNotIn('id', SupportTicket::query()->open()->select('id')),
                fn (Builder $query) => $query->open())
            ->with(['tenant', 'assignee'])
            ->latest('last_activity_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (SupportTicket $ticket) => $presenter->summary($ticket));

        $summary = $usage->forTenant($this->tenant());

        return Inertia::render('Support/Tickets/Index', [
            'tickets' => $tickets,
            'status' => $request->string('status')->toString() ?: 'open',
            'seesAll' => $user->can('tenant.support-tickets.view-all'),
            'usage' => [
                'included_minutes' => $summary['included_minutes'],
                'used_minutes' => $summary['used_minutes'],
                'period_end' => $summary['period_end']->toDateString(),
            ],
            'can' => [
                'create' => $user->can('tenant.support-tickets.create'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Support/Tickets/Create', [
            'categories' => TicketCategory::toArray(),
            'priorities' => collect(TicketPriority::toArray())->except(TicketPriority::Urgent()->value),
        ]);
    }

    public function store(StoreTenantTicketRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $ticket = $this->manager->open(new OpenTicketData(
            channel: TicketChannel::TenantPanel(),
            requesterName: $user->name,
            requesterEmail: $user->email,
            subject: $request->string('subject')->toString(),
            description: $request->string('description')->toString(),
            category: TicketCategory::from($request->string('category')->toString()),
            priority: TicketPriority::from($request->string('priority')->toString()),
            tenant: $this->tenant(),
            requesterTenantUserId: (string) $user->getKey(),
            attachments: $request->file('attachments', []),
        ));

        return redirect()->route('tenant.support.tickets.show', $ticket)->with('status', 'support-ticket-created');
    }

    public function show(Request $request, SupportTicket $ticket, SupportTicketPresenter $presenter): Response
    {
        return Inertia::render('Support/Tickets/Show', [
            'ticket' => $presenter->forRequester(
                $ticket,
                fn (Attachment $attachment) => route('tenant.support.attachments.show', $attachment),
            ),
            'canReopen' => $this->manager->canReopen($ticket),
            'can' => [
                'book' => $request->user()->can('tenant.support-appointments.create'),
            ],
        ]);
    }

    public function reply(ReplyAsRequesterRequest $request, SupportTicket $ticket): RedirectResponse
    {
        try {
            $this->manager->reply(
                $ticket,
                SupportActor::requester($request->user()->name),
                $request->string('body')->toString(),
                $request->file('attachments', []),
            );
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['body' => $exception->getMessage()]);
        }

        return back()->with('status', 'support-reply-sent');
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

    public function attachment(Attachment $attachment, AttachmentStore $store): StreamedResponse
    {
        return $store->download($attachment);
    }

    private function tenant(): Tenant
    {
        /** @var Tenant */
        return tenant();
    }
}
