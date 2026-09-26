<?php

namespace App\Services\Support;

use App\Enums\TicketEventType;
use App\Models\Attachment;
use App\Models\SupportAppointment;
use App\Models\SupportTicket;
use App\Models\SupportTicketEvent;
use App\Models\SupportTicketMessage;
use App\Models\SupportTimeEntry;
use Closure;

/**
 * Shapes tickets for Inertia pages. The requester view is deliberately a
 * subset: no internal notes, no billing, no time tracking, no audit trail
 * beyond what the customer did or saw.
 */
class SupportTicketPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function summary(SupportTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'code' => $ticket->code(),
            'subject' => $ticket->subject,
            'status' => $ticket->status->value,
            'status_label' => $ticket->status->label,
            'priority' => $ticket->priority->value,
            'priority_label' => $ticket->priority->label,
            'category_label' => $ticket->category->label,
            'requester_name' => $ticket->requester_name,
            'requester_email' => $ticket->requester_email,
            'tenant_id' => $ticket->tenant_id,
            'tenant_name' => $ticket->tenant !== null
                ? ($ticket->tenant->company_name ?? $ticket->tenant_id)
                : $ticket->requester_company,
            'assignee_name' => $ticket->assignee?->name,
            'channel_label' => $ticket->channel->label,
            'billing_mode' => $ticket->billing_mode->value,
            'billing_mode_label' => $ticket->billing_mode->label,
            'billing_status' => $ticket->billing_status->value,
            'billing_status_label' => $ticket->billing_status->label,
            'is_overdue' => $ticket->isFirstResponseOverdue() || $ticket->isResolutionOverdue(),
            'created_at' => $ticket->created_at?->toIso8601String(),
            'last_activity_at' => $ticket->last_activity_at?->toIso8601String(),
        ];
    }

    /**
     * @param  Closure(Attachment): string  $attachmentUrl
     * @return array<string, mixed>
     */
    public function forStaff(SupportTicket $ticket, Closure $attachmentUrl): array
    {
        $ticket->loadMissing([
            'tenant', 'assignee', 'resolver', 'module', 'rating.staff',
            'messages.attachments', 'attachments', 'events', 'timeEntries.staff',
            'appointments.attendanceType', 'appointments.technician.user',
        ]);

        return [
            ...$this->summary($ticket),
            'description' => $ticket->description,
            'category' => $ticket->category->value,
            'requester_company' => $ticket->requester_company,
            'module_name' => $ticket->module?->name,
            'assigned_to' => $ticket->assigned_to,
            'covered_by_plan' => $ticket->covered_by_plan,
            'fixed_amount' => $ticket->fixed_amount,
            'billing_reason' => $ticket->billing_reason,
            'invoice_reference' => $ticket->invoice_reference,
            'first_response_due_at' => $ticket->first_response_due_at?->toIso8601String(),
            'resolution_due_at' => $ticket->resolution_due_at?->toIso8601String(),
            'first_responded_at' => $ticket->first_responded_at?->toIso8601String(),
            'resolved_at' => $ticket->resolved_at?->toIso8601String(),
            'resolver_name' => $ticket->resolver?->name,
            'is_first_response_overdue' => $ticket->isFirstResponseOverdue(),
            'is_resolution_overdue' => $ticket->isResolutionOverdue(),
            'is_open' => $ticket->isOpen(),
            'attachments' => $this->ticketLevelAttachments($ticket, $attachmentUrl),
            'messages' => $ticket->messages
                ->sortBy('created_at')
                ->map(fn (SupportTicketMessage $message) => $this->message($message, $attachmentUrl))
                ->values(),
            'time_entries' => $ticket->timeEntries
                ->sortByDesc('worked_on')
                ->map(fn (SupportTimeEntry $entry) => [
                    'id' => $entry->id,
                    'staff_name' => $entry->staff?->name,
                    'minutes' => $entry->minutes,
                    'description' => $entry->description,
                    'is_billable' => $entry->is_billable,
                    'worked_on' => $entry->worked_on->toDateString(),
                ])
                ->values(),
            'total_minutes' => $ticket->timeEntries->sum('minutes'),
            'billable_minutes' => $ticket->timeEntries->where('is_billable', true)->sum('minutes'),
            'appointments' => $this->appointments($ticket, withTechnician: true),
            'rating' => $ticket->rating ? [
                'stars' => $ticket->rating->stars,
                'was_resolved' => $ticket->rating->was_resolved,
                'comment' => $ticket->rating->comment,
                'staff_name' => $ticket->rating->staff?->name,
            ] : null,
            'events' => $ticket->events
                ->sortByDesc('created_at')
                ->map(fn (SupportTicketEvent $event) => [
                    'id' => $event->id,
                    'type' => $event->type->value,
                    'type_label' => $event->type->label,
                    'actor_name' => $event->actor_name,
                    'data' => $event->data,
                    'created_at' => $event->created_at->toIso8601String(),
                ])
                ->values(),
        ];
    }

    /**
     * @param  Closure(Attachment): string  $attachmentUrl
     * @return array<string, mixed>
     */
    public function forRequester(SupportTicket $ticket, Closure $attachmentUrl): array
    {
        $ticket->loadMissing([
            'assignee', 'rating', 'messages.attachments', 'attachments',
            'appointments.attendanceType', 'appointments.technician.user',
        ]);

        return [
            'id' => $ticket->id,
            'code' => $ticket->code(),
            'subject' => $ticket->subject,
            'description' => $ticket->description,
            'status' => $ticket->status->value,
            'status_label' => $ticket->status->label,
            'priority_label' => $ticket->priority->label,
            'category_label' => $ticket->category->label,
            'requester_name' => $ticket->requester_name,
            'assignee_name' => $ticket->assignee?->name,
            'is_open' => $ticket->isOpen(),
            'is_closed' => ! $ticket->isOpen() && $ticket->closed_at !== null,
            'created_at' => $ticket->created_at?->toIso8601String(),
            'resolved_at' => $ticket->resolved_at?->toIso8601String(),
            'attachments' => $this->ticketLevelAttachments($ticket, $attachmentUrl),
            'messages' => $ticket->messages
                ->where('is_internal', false)
                ->sortBy('created_at')
                ->map(fn (SupportTicketMessage $message) => $this->message($message, $attachmentUrl))
                ->values(),
            'appointments' => $this->appointments($ticket, withTechnician: false),
            'rating' => $ticket->rating ? [
                'stars' => $ticket->rating->stars,
                'was_resolved' => $ticket->rating->was_resolved,
                'comment' => $ticket->rating->comment,
            ] : null,
            'events' => $ticket->events()
                ->whereIn('type', [
                    TicketEventType::Created()->value,
                    TicketEventType::StatusChanged()->value,
                    TicketEventType::Reopened()->value,
                    TicketEventType::AppointmentBooked()->value,
                    TicketEventType::AppointmentCancelled()->value,
                ])
                ->latest('created_at')
                ->get()
                ->map(fn (SupportTicketEvent $event) => [
                    'id' => $event->id,
                    'type_label' => $event->type->label,
                    'data' => array_intersect_key($event->data ?? [], array_flip(['from', 'to', 'starts_at', 'type'])),
                    'created_at' => $event->created_at->toIso8601String(),
                ])
                ->values(),
        ];
    }

    /**
     * @param  Closure(Attachment): string  $attachmentUrl
     * @return array<string, mixed>
     */
    private function message(SupportTicketMessage $message, Closure $attachmentUrl): array
    {
        return [
            'id' => $message->id,
            'author_type' => $message->author_type->value,
            'author_name' => $message->author_name,
            'body' => $message->body,
            'is_internal' => $message->is_internal,
            'created_at' => $message->created_at?->toIso8601String(),
            'attachments' => $message->attachments->map(fn (Attachment $attachment) => $this->attachment($attachment, $attachmentUrl))->values(),
        ];
    }

    /**
     * @param  Closure(Attachment): string  $attachmentUrl
     * @return array<int, array<string, mixed>>
     */
    private function ticketLevelAttachments(SupportTicket $ticket, Closure $attachmentUrl): array
    {
        return $ticket->attachments
            ->map(fn (Attachment $attachment) => $this->attachment($attachment, $attachmentUrl))
            ->values()
            ->all();
    }

    /**
     * @param  Closure(Attachment): string  $attachmentUrl
     * @return array<string, mixed>
     */
    private function attachment(Attachment $attachment, Closure $attachmentUrl): array
    {
        return [
            'id' => $attachment->id,
            'name' => $attachment->original_name,
            'size' => $attachment->size,
            'url' => $attachmentUrl($attachment),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function appointments(SupportTicket $ticket, bool $withTechnician): array
    {
        return $ticket->appointments
            ->sortByDesc('starts_at')
            ->map(fn (SupportAppointment $appointment) => [
                'id' => $appointment->id,
                'type_name' => $appointment->attendanceType->name,
                'starts_at' => $appointment->starts_at->toIso8601String(),
                'ends_at' => $appointment->ends_at->toIso8601String(),
                'status' => $appointment->status->value,
                'status_label' => $appointment->status->label,
                'meeting_url' => $appointment->meeting_url,
                'technician_name' => $withTechnician ? $appointment->technician->user->name : null,
                'cancellation_reason' => $appointment->cancellation_reason,
            ])
            ->values()
            ->all();
    }
}
