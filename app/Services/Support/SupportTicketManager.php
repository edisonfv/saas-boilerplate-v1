<?php

namespace App\Services\Support;

use App\Enums\TicketBillingMode;
use App\Enums\TicketBillingStatus;
use App\Enums\TicketEventType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\CentralUser;
use App\Models\SupportRating;
use App\Models\SupportSetting;
use App\Models\SupportTicket;
use App\Models\SupportTicketEvent;
use App\Models\SupportTicketMessage;
use App\Models\SupportTimeEntry;
use App\Models\Tenant;
use App\Notifications\Support\RequesterReplied;
use App\Notifications\Support\TicketAssigned;
use App\Notifications\Support\TicketReceived;
use App\Notifications\Support\TicketReplied;
use App\Notifications\Support\TicketResolved;
use App\Services\Attachments\AttachmentStore;
use App\Services\TenantEntitlements;
use Carbon\CarbonInterface;
use Closure;
use DomainException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Every state change of a support ticket goes through here, so each one is
 * validated, written to the audit trail and notified consistently —
 * whichever channel (tenant panel, public form, console) triggered it.
 *
 * Tickets live in the central database: writes are wrapped in a central
 * transaction explicitly, because inside a tenant request the default
 * connection points at the tenant's own database.
 */
class SupportTicketManager
{
    public function __construct(
        private TenantEntitlements $entitlements,
        private SupportSla $sla,
        private SupportLinks $links,
        private AttachmentStore $attachments,
    ) {}

    public function open(OpenTicketData $data): SupportTicket
    {
        $isCovered = $this->tenantHasSupport($data->tenant);
        $billingMode = $isCovered ? TicketBillingMode::Included() : TicketBillingMode::Hourly();
        $actor = $data->createdBy
            ? SupportActor::staff($data->createdBy)
            : SupportActor::requester($data->requesterName);

        $ticket = $this->transaction(function () use ($data, $isCovered, $billingMode, $actor): SupportTicket {
            $ticket = SupportTicket::create([
                'number' => $this->nextNumber(),
                'tenant_id' => $data->tenant?->getTenantKey(),
                'channel' => $data->channel,
                'requester_tenant_user_id' => $data->requesterTenantUserId,
                'requester_name' => $data->requesterName,
                'requester_email' => Str::lower($data->requesterEmail),
                'requester_company' => $data->requesterCompany,
                'access_token' => Str::random(48),
                'subject' => $data->subject,
                'description' => $data->description,
                'category' => $data->category,
                'priority' => $data->priority,
                // A ticket opened with a known tenant is already triaged.
                'status' => $data->tenant ? TicketStatus::Open() : TicketStatus::New(),
                'module_id' => $data->moduleId,
                'created_by' => $data->createdBy?->id,
                'covered_by_plan' => $isCovered,
                'billing_mode' => $billingMode,
                'billing_status' => self::billingStatusFor($billingMode),
                'last_activity_at' => now(),
            ]);

            $ticket->forceFill($this->sla->deadlinesFor($ticket))->save();

            $this->attachments->store($ticket, $data->attachments, $data->createdBy?->id);
            $this->record($ticket, TicketEventType::Created(), $actor, ['channel' => $data->channel->value]);

            return $ticket;
        });

        Notification::route('mail', $ticket->requester_email)
            ->notify(new TicketReceived($ticket, $this->links->forRequester($ticket)));

        return $ticket;
    }

    /**
     * Adds a message to the conversation. Staff replies move the ticket to
     * "waiting on customer"; a requester reply puts it back in the queue and
     * reopens a recently resolved ticket.
     *
     * @param  list<UploadedFile>  $files
     */
    public function reply(SupportTicket $ticket, SupportActor $actor, string $body, array $files = [], bool $isInternal = false): SupportTicketMessage
    {
        if ($ticket->status->equals(TicketStatus::Closed())) {
            throw new DomainException('El ticket está cerrado. Abre una nueva solicitud.');
        }

        if ($isInternal && ! $actor->isStaff()) {
            throw new DomainException('Solo el staff puede dejar notas internas.');
        }

        if (! $actor->isStaff() && $ticket->status->equals(TicketStatus::Resolved()) && ! $this->canReopen($ticket)) {
            throw new DomainException('El plazo para reabrir este ticket venció. Abre una nueva solicitud.');
        }

        $message = $this->transaction(function () use ($ticket, $actor, $body, $files, $isInternal): SupportTicketMessage {
            $message = $ticket->messages()->create([
                'author_type' => $actor->type,
                'central_user_id' => $actor->centralUserId,
                'author_name' => $actor->name,
                'body' => $body,
                'is_internal' => $isInternal,
            ]);

            $this->attachments->store($message, $files, $actor->centralUserId);

            if ($isInternal) {
                $this->record($ticket, TicketEventType::InternalNote(), $actor);
                $ticket->forceFill(['last_activity_at' => now()])->save();

                return $message;
            }

            $this->record($ticket, TicketEventType::Replied(), $actor);

            if ($actor->isStaff()) {
                $ticket->first_responded_at ??= now();

                if ($ticket->isOpen()) {
                    $this->moveTo($ticket, TicketStatus::WaitingOnCustomer(), $actor);
                }
            } elseif ($ticket->status->equals(TicketStatus::Resolved())) {
                $this->reopen($ticket, $actor);
            } elseif ($ticket->status->equals(TicketStatus::WaitingOnCustomer())) {
                $this->moveTo($ticket, $ticket->assigned_to ? TicketStatus::InProgress() : TicketStatus::Open(), $actor);
            }

            $ticket->last_activity_at = now();
            $ticket->save();

            return $message;
        });

        if ($isInternal) {
            return $message;
        }

        if ($actor->isStaff()) {
            Notification::route('mail', $ticket->requester_email)
                ->notify(new TicketReplied($ticket, $message, $this->links->forRequester($ticket)));
        } elseif ($ticket->assignee) {
            $ticket->assignee->notify(new RequesterReplied($ticket, $this->links->forStaff($ticket)));
        }

        return $message;
    }

    public function changeStatus(SupportTicket $ticket, TicketStatus $status, SupportActor $actor): void
    {
        if ($ticket->status->equals($status)) {
            return;
        }

        $this->transaction(function () use ($ticket, $status, $actor): void {
            if (! $ticket->isOpen() && ! $status->equals(TicketStatus::Resolved(), TicketStatus::Closed())) {
                $this->reopen($ticket, $actor, $status);
            } else {
                $this->moveTo($ticket, $status, $actor);
            }

            $ticket->last_activity_at = now();
            $ticket->save();
        });

        if ($status->equals(TicketStatus::Resolved())) {
            $days = SupportSetting::current()->rating_link_days;

            Notification::route('mail', $ticket->requester_email)->notify(new TicketResolved(
                $ticket,
                $this->links->forRequester($ticket),
                $this->links->rating($ticket, $days),
            ));
        }
    }

    public function changePriority(SupportTicket $ticket, TicketPriority $priority, SupportActor $actor): void
    {
        if ($ticket->priority->equals($priority)) {
            return;
        }

        $this->transaction(function () use ($ticket, $priority, $actor): void {
            $from = $ticket->priority;
            $ticket->priority = $priority;
            $ticket->forceFill($this->sla->deadlinesFor($ticket))->save();

            $this->record($ticket, TicketEventType::PriorityChanged(), $actor, [
                'from' => $from->label,
                'to' => $priority->label,
            ]);
        });
    }

    public function assign(SupportTicket $ticket, ?CentralUser $assignee, SupportActor $actor): void
    {
        if ($ticket->assigned_to === $assignee?->id) {
            return;
        }

        $this->transaction(function () use ($ticket, $assignee, $actor): void {
            $ticket->assigned_to = $assignee?->id;

            if ($assignee && $ticket->status->equals(TicketStatus::New(), TicketStatus::Open())) {
                $this->moveTo($ticket, TicketStatus::InProgress(), $actor);
            }

            $ticket->save();

            $this->record($ticket, TicketEventType::Assigned(), $actor, [
                'to' => $assignee->name ?? 'Sin asignar',
            ]);
        });

        $ticket->setRelation('assignee', $assignee);

        if ($assignee && $assignee->id !== $actor->centralUserId) {
            $assignee->notify(new TicketAssigned($ticket, $this->links->forStaff($ticket)));
        }
    }

    /**
     * Associates a public-form ticket with the tenant it belongs to. Coverage,
     * billing defaults and SLA are recomputed from that tenant's plan.
     */
    public function linkTenant(SupportTicket $ticket, Tenant $tenant, SupportActor $actor): void
    {
        $this->transaction(function () use ($ticket, $tenant, $actor): void {
            $isCovered = $this->tenantHasSupport($tenant);
            $billingMode = $isCovered ? TicketBillingMode::Included() : TicketBillingMode::Hourly();

            $ticket->tenant_id = $tenant->getTenantKey();
            $ticket->setRelation('tenant', $tenant);
            $ticket->covered_by_plan = $isCovered;

            // Only reset billing that nobody has touched yet.
            if ($ticket->billing_status->equals(TicketBillingStatus::NotApplicable(), TicketBillingStatus::Pending())
                && $ticket->fixed_amount === null) {
                $ticket->billing_mode = $billingMode;
                $ticket->billing_status = self::billingStatusFor($billingMode);
            }

            $ticket->forceFill($this->sla->deadlinesFor($ticket));

            if ($ticket->status->equals(TicketStatus::New())) {
                $this->moveTo($ticket, TicketStatus::Open(), $actor);
            }

            $ticket->save();

            $this->record($ticket, TicketEventType::TenantLinked(), $actor, [
                'tenant' => $tenant->company_name ?? $tenant->getTenantKey(),
                'covered_by_plan' => $isCovered,
            ]);
        });
    }

    public function updateBilling(
        SupportTicket $ticket,
        SupportActor $actor,
        TicketBillingMode $mode,
        ?string $fixedAmount,
        ?string $reason,
        ?string $invoiceReference,
    ): void {
        if ($mode->equals(TicketBillingMode::Fixed()) && ($fixedAmount === null || (float) $fixedAmount <= 0)) {
            throw new DomainException('Indica el monto fijo a facturar.');
        }

        $this->transaction(function () use ($ticket, $actor, $mode, $fixedAmount, $reason, $invoiceReference): void {
            $ticket->billing_mode = $mode;
            $ticket->fixed_amount = $mode->equals(TicketBillingMode::Fixed()) ? $fixedAmount : null;
            $ticket->billing_reason = $reason;
            $ticket->invoice_reference = $invoiceReference;
            $ticket->billing_status = $invoiceReference && self::isChargeable($mode)
                ? TicketBillingStatus::Invoiced()
                : self::billingStatusFor($mode);
            $ticket->save();

            $this->record($ticket, TicketEventType::BillingChanged(), $actor, [
                'mode' => $mode->label,
                'status' => $ticket->billing_status->label,
                'fixed_amount' => $ticket->fixed_amount,
                'reason' => $reason,
                'invoice_reference' => $invoiceReference,
            ]);
        });
    }

    public function logTime(
        SupportTicket $ticket,
        CentralUser $staff,
        int $minutes,
        string $description,
        bool $isBillable,
        CarbonInterface $workedOn,
        ?string $appointmentId = null,
    ): SupportTimeEntry {
        return $this->transaction(function () use ($ticket, $staff, $minutes, $description, $isBillable, $workedOn, $appointmentId): SupportTimeEntry {
            $entry = $ticket->timeEntries()->create([
                'central_user_id' => $staff->id,
                'support_appointment_id' => $appointmentId,
                'minutes' => $minutes,
                'description' => $description,
                'is_billable' => $isBillable,
                'worked_on' => $workedOn->toDateString(),
            ]);

            $this->record($ticket, TicketEventType::TimeLogged(), SupportActor::staff($staff), [
                'minutes' => $minutes,
                'billable' => $isBillable,
            ]);

            return $entry;
        });
    }

    /**
     * Customer satisfaction for a resolved ticket, credited to whoever
     * resolved it. "Not resolved" reopens the ticket.
     */
    public function rate(SupportTicket $ticket, int $stars, bool $wasResolved, ?string $comment): SupportRating
    {
        if ($ticket->isOpen()) {
            throw new DomainException('Solo se pueden calificar tickets resueltos.');
        }

        if ($ticket->rating()->exists()) {
            throw new DomainException('Este ticket ya fue calificado.');
        }

        return $this->transaction(function () use ($ticket, $stars, $wasResolved, $comment): SupportRating {
            $actor = SupportActor::requester($ticket->requester_name);

            $rating = $ticket->rating()->create([
                'central_user_id' => $ticket->resolved_by,
                'stars' => $stars,
                'was_resolved' => $wasResolved,
                'comment' => $comment,
            ]);

            $this->record($ticket, TicketEventType::Rated(), $actor, [
                'stars' => $stars,
                'was_resolved' => $wasResolved,
            ]);

            if (! $wasResolved) {
                $this->reopen($ticket, $actor);
                $ticket->save();
            }

            return $rating;
        });
    }

    /**
     * Closes resolved tickets nobody followed up on. Returns how many closed.
     */
    public function closeStaleResolved(): int
    {
        $cutoff = now()->subDays(SupportSetting::current()->auto_close_days);
        $closed = 0;

        SupportTicket::query()
            ->where('status', TicketStatus::Resolved()->value)
            ->where('resolved_at', '<=', $cutoff)
            ->each(function (SupportTicket $ticket) use (&$closed): void {
                $this->changeStatus($ticket, TicketStatus::Closed(), SupportActor::system());
                $closed++;
            });

        return $closed;
    }

    public function canReopen(SupportTicket $ticket): bool
    {
        return $ticket->status->equals(TicketStatus::Resolved())
            && $ticket->resolved_at !== null
            && $ticket->resolved_at->greaterThan(now()->subDays(SupportSetting::current()->reopen_window_days));
    }

    public function tenantHasSupport(?Tenant $tenant): bool
    {
        return $tenant !== null
            && $this->entitlements->activeModules($tenant)->contains(SupportLimits::ModuleSlug);
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    public function record(SupportTicket $ticket, TicketEventType $type, SupportActor $actor, ?array $data = null): SupportTicketEvent
    {
        return $ticket->events()->create([
            'type' => $type,
            'actor_type' => $actor->type,
            'central_user_id' => $actor->centralUserId,
            'actor_name' => $actor->name,
            'data' => $data,
            'created_at' => now(),
        ]);
    }

    public static function billingStatusFor(TicketBillingMode $mode): TicketBillingStatus
    {
        return self::isChargeable($mode) ? TicketBillingStatus::Pending() : TicketBillingStatus::NotApplicable();
    }

    /**
     * Modes charged per ticket. "Included" is charged per tenant and period
     * (only the hours above the plan), so it has no per-ticket status.
     */
    public static function isChargeable(TicketBillingMode $mode): bool
    {
        return $mode->equals(TicketBillingMode::Hourly(), TicketBillingMode::Fixed());
    }

    /**
     * Runs on the central connection, even inside a tenant request.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function transaction(Closure $callback): mixed
    {
        return $this->central()->transaction($callback, attempts: 3);
    }

    private function central(): ConnectionInterface
    {
        return DB::connection(config('tenancy.database.central_connection'));
    }

    private function moveTo(SupportTicket $ticket, TicketStatus $status, SupportActor $actor): void
    {
        $from = $ticket->status;

        if ($from->equals($status)) {
            return;
        }

        $ticket->status = $status;

        if ($status->equals(TicketStatus::Resolved())) {
            $ticket->resolved_at = now();
            $ticket->resolved_by = $actor->isStaff() ? $actor->centralUserId : $ticket->assigned_to;
        }

        if ($status->equals(TicketStatus::Closed())) {
            $ticket->closed_at = now();
            $ticket->resolved_at ??= now();
        }

        $this->record($ticket, TicketEventType::StatusChanged(), $actor, [
            'from' => $from->label,
            'to' => $status->label,
        ]);
    }

    private function reopen(SupportTicket $ticket, SupportActor $actor, ?TicketStatus $status = null): void
    {
        $ticket->status = $status ?? ($ticket->assigned_to ? TicketStatus::InProgress() : TicketStatus::Open());
        $ticket->resolved_at = null;
        $ticket->resolved_by = null;
        $ticket->closed_at = null;

        $this->record($ticket, TicketEventType::Reopened(), $actor, ['to' => $ticket->status->label]);
    }

    private function nextNumber(): int
    {
        return (int) SupportTicket::query()->lockForUpdate()->max('number') + 1;
    }
}
