<?php

namespace App\Notifications\Support;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a staff member when a ticket is assigned to them.
 */
class TicketAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupportTicket $ticket, public string $link) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Te asignaron el ticket {$this->ticket->code()}")
            ->line("Ticket: «{$this->ticket->subject}» ({$this->ticket->priority->label}).")
            ->line("Solicitante: {$this->ticket->requester_name} <{$this->ticket->requester_email}>.")
            ->action('Abrir ticket', $this->link);
    }
}
