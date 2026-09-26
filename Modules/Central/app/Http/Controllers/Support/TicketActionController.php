<?php

namespace Modules\Central\Http\Controllers\Support;

use App\Enums\TicketBillingMode;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\CentralUser;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Services\Support\SupportActor;
use App\Services\Support\SupportTicketManager;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Central\Http\Requests\Support\ReplySupportTicketRequest;
use Modules\Central\Http\Requests\Support\StoreTimeEntryRequest;
use Modules\Central\Http\Requests\Support\UpdateTicketBillingRequest;

/**
 * Staff actions on a ticket from the console. All state changes go through
 * SupportTicketManager so they're audited and notified the same way.
 */
class TicketActionController extends Controller
{
    public function __construct(private SupportTicketManager $manager) {}

    public function reply(ReplySupportTicketRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $isInternal = $request->boolean('is_internal');

        $this->attempt(fn () => $this->manager->reply(
            $ticket,
            SupportActor::staff($request->user()),
            $request->string('body')->toString(),
            $request->file('attachments', []),
            $isInternal,
        ), 'body');

        return back()->with('status', $isInternal ? 'support-note-added' : 'support-reply-sent');
    }

    public function status(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(TicketStatus::toValues())],
        ]);

        $this->manager->changeStatus($ticket, TicketStatus::from($validated['status']), SupportActor::staff($request->user()));

        return back()->with('status', 'support-ticket-updated');
    }

    public function priority(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'priority' => ['required', 'string', Rule::in(TicketPriority::toValues())],
        ]);

        $this->manager->changePriority($ticket, TicketPriority::from($validated['priority']), SupportActor::staff($request->user()));

        return back()->with('status', 'support-ticket-updated');
    }

    public function assign(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_to' => ['nullable', 'uuid', Rule::exists(CentralUser::class, 'id')],
        ]);

        $assignee = isset($validated['assigned_to']) ? CentralUser::find($validated['assigned_to']) : null;

        if ($assignee && ! $assignee->can('central.support-tickets.update')) {
            throw ValidationException::withMessages(['assigned_to' => 'Ese usuario no atiende tickets de soporte.']);
        }

        $this->manager->assign($ticket, $assignee, SupportActor::staff($request->user()));

        return back()->with('status', 'support-ticket-assigned');
    }

    public function linkTenant(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string', Rule::exists(Tenant::class, 'id')],
        ]);

        $this->manager->linkTenant($ticket, Tenant::findOrFail($validated['tenant_id']), SupportActor::staff($request->user()));

        return back()->with('status', 'support-ticket-updated');
    }

    public function billing(UpdateTicketBillingRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->attempt(fn () => $this->manager->updateBilling(
            $ticket,
            SupportActor::staff($request->user()),
            TicketBillingMode::from($request->string('billing_mode')->toString()),
            $request->filled('fixed_amount') ? (string) $request->input('fixed_amount') : null,
            $request->input('billing_reason'),
            $request->input('invoice_reference'),
        ), 'fixed_amount');

        return back()->with('status', 'support-billing-updated');
    }

    public function logTime(StoreTimeEntryRequest $request, SupportTicket $ticket): RedirectResponse
    {
        $this->manager->logTime(
            $ticket,
            $request->user(),
            $request->integer('minutes'),
            $request->string('description')->toString(),
            $request->boolean('is_billable', true),
            CarbonImmutable::parse($request->string('worked_on')->toString()),
        );

        return back()->with('status', 'support-time-logged');
    }

    /**
     * Turns business-rule violations into a validation error on `$field`.
     */
    private function attempt(callable $action, string $field): void
    {
        try {
            $action();
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([$field => $exception->getMessage()]);
        }
    }
}
