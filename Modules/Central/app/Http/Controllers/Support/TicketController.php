<?php

namespace Modules\Central\Http\Controllers\Support;

use App\Enums\TicketBillingMode;
use App\Enums\TicketBillingStatus;
use App\Enums\TicketCategory;
use App\Enums\TicketChannel;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\CentralUser;
use App\Models\Module;
use App\Models\SupportAttendanceType;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Services\Support\OpenTicketData;
use App\Services\Support\SupportAvailability;
use App\Services\Support\SupportTicketManager;
use App\Services\Support\SupportTicketPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Central\Http\Requests\Support\StoreSupportTicketRequest;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TicketController extends Controller
{
    public function index(Request $request, SupportTicketPresenter $presenter): Response
    {
        $userId = $request->user()->id;

        // No status filter at all = the open queue; an explicit empty status = everything.
        $baseQuery = SupportTicket::query()->when(! $request->has('filter.status'), fn (Builder $query) => $query->open());

        $tickets = QueryBuilder::for($baseQuery)
            ->allowedFilters(
                AllowedFilter::callback('search', fn (Builder $query, string $value) => $query->where(fn (Builder $search) => $search
                    ->where('subject', 'like', "%{$value}%")
                    ->orWhere('requester_name', 'like', "%{$value}%")
                    ->orWhere('requester_email', 'like', "%{$value}%")
                    ->orWhere('tenant_id', 'like', "%{$value}%")
                    // "SOP-000123" or "123" find ticket number 123.
                    ->when(
                        preg_match('/^(?:SOP-)?0*(\d+)$/i', trim($value), $matches) === 1,
                        fn (Builder $byNumber) => $byNumber->orWhere('number', (int) $matches[1]),
                    ))),
                AllowedFilter::callback('status', fn (Builder $query, string $value) => $value === 'open'
                    ? $query->open()
                    : $query->where('status', $value)),
                AllowedFilter::exact('priority'),
                AllowedFilter::callback('assignee', fn (Builder $query, string $value) => match ($value) {
                    'me' => $query->where('assigned_to', $userId),
                    'none' => $query->whereNull('assigned_to'),
                    default => $query->where('assigned_to', $value),
                }),
                AllowedFilter::callback('billing', fn (Builder $query, string $value) => $query
                    ->where('billing_status', TicketBillingStatus::Pending()->value)),
                AllowedFilter::callback('overdue', fn (Builder $query) => $query->open()->where(fn (Builder $late) => $late
                    ->where(fn (Builder $first) => $first->whereNull('first_responded_at')->where('first_response_due_at', '<', now()))
                    ->orWhere('resolution_due_at', '<', now()))),
            )
            ->allowedSorts('number', 'created_at', 'last_activity_at', 'priority')
            ->defaultSort('-last_activity_at')
            ->with(['tenant', 'assignee'])
            ->paginate(20)
            ->withQueryString()
            ->through(fn (SupportTicket $ticket) => $presenter->summary($ticket));

        return Inertia::render('Central/Support/Tickets/Index', [
            'tickets' => $tickets,
            'filters' => [
                'search' => $request->input('filter.search'),
                'status' => $request->has('filter.status') ? ($request->input('filter.status') ?: 'all') : 'open',
                'priority' => $request->input('filter.priority'),
                'assignee' => $request->input('filter.assignee'),
                'billing' => $request->input('filter.billing'),
                'overdue' => $request->input('filter.overdue'),
            ],
            'statuses' => TicketStatus::toArray(),
            'priorities' => TicketPriority::toArray(),
            'staff' => $this->staffOptions(),
            'stats' => [
                'open' => SupportTicket::query()->open()->count(),
                'unassigned' => SupportTicket::query()->open()->whereNull('assigned_to')->count(),
                'mine' => SupportTicket::query()->open()->where('assigned_to', $userId)->count(),
                'pendingBilling' => SupportTicket::query()->where('billing_status', TicketBillingStatus::Pending()->value)->count(),
            ],
            'can' => [
                'create' => $request->user()->can('central.support-tickets.create'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Central/Support/Tickets/Create', [
            'tenants' => Tenant::query()->orderBy('company_name')->get(['id', 'company_name'])
                ->map(fn (Tenant $tenant) => [
                    'id' => $tenant->getTenantKey(),
                    'name' => $tenant->company_name ?? $tenant->getTenantKey(),
                    'contact_name' => $tenant->primary_contact_name,
                    'contact_email' => $tenant->primary_contact_email,
                ]),
            'modules' => Module::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'categories' => TicketCategory::toArray(),
            'priorities' => TicketPriority::toArray(),
        ]);
    }

    public function store(StoreSupportTicketRequest $request, SupportTicketManager $manager): RedirectResponse
    {
        $ticket = $manager->open(new OpenTicketData(
            channel: TicketChannel::Staff(),
            requesterName: $request->string('requester_name')->toString(),
            requesterEmail: $request->string('requester_email')->toString(),
            subject: $request->string('subject')->toString(),
            description: $request->string('description')->toString(),
            category: TicketCategory::from($request->string('category')->toString()),
            priority: TicketPriority::from($request->string('priority')->toString()),
            tenant: $request->filled('tenant_id') ? Tenant::find($request->string('tenant_id')->toString()) : null,
            requesterCompany: $request->input('requester_company'),
            moduleId: $request->input('module_id'),
            createdBy: $request->user(),
            attachments: $request->file('attachments', []),
        ));

        return redirect()->route('central.support.tickets.show', $ticket)->with('status', 'support-ticket-created');
    }

    public function show(Request $request, SupportTicket $ticket, SupportTicketPresenter $presenter, SupportAvailability $availability): Response
    {
        $user = $request->user();
        $attendanceTypes = SupportAttendanceType::query()->where('is_active', true)->orderBy('duration_minutes')->get();
        $selectedTypeId = $request->string('type')->toString() ?: $attendanceTypes->first()?->id;
        $selectedType = $attendanceTypes->firstWhere('id', $selectedTypeId);
        $selectedDate = $request->string('date')->toString() ?: now()->toDateString();

        return Inertia::render('Central/Support/Tickets/Show', [
            'ticket' => $presenter->forStaff(
                $ticket,
                fn (Attachment $attachment) => route('central.attachments.show', $attachment),
            ),
            'staff' => $this->staffOptions(),
            'statuses' => TicketStatus::toArray(),
            'priorities' => TicketPriority::toArray(),
            'billingModes' => TicketBillingMode::toArray(),
            'tenants' => $ticket->tenant_id === null
                ? Tenant::query()->orderBy('company_name')->get(['id', 'company_name'])->map(fn (Tenant $tenant) => [
                    'id' => $tenant->getTenantKey(),
                    'name' => $tenant->company_name ?? $tenant->getTenantKey(),
                ])
                : [],
            'booking' => Inertia::optional(fn () => [
                'types' => $attendanceTypes->map(fn (SupportAttendanceType $type) => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'duration_minutes' => $type->duration_minutes,
                ]),
                'type_id' => $selectedType?->id,
                'date' => $selectedDate,
                'dates' => $selectedType ? $availability->availableDates($selectedType) : [],
                'slots' => $selectedType
                    ? collect($availability->slotsOn($selectedType, CarbonImmutable::parse($selectedDate)))
                        ->map(fn (array $slot) => [
                            'starts_at' => $slot['starts_at']->toIso8601String(),
                            'available' => $slot['available'],
                        ])
                    : [],
            ]),
            'can' => [
                'update' => $user->can('central.support-tickets.update'),
                'assign' => $user->can('central.support-tickets.assign'),
                'billing' => $user->can('central.support-tickets.billing'),
            ],
        ]);
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function staffOptions(): array
    {
        return CentralUser::permission('central.support-tickets.update')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (CentralUser $user) => ['id' => $user->id, 'name' => $user->name])
            ->all();
    }
}
