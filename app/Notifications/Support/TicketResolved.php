<?php

namespace App\Notifications\Support;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the requester when the ticket is resolved, with the CSAT survey link.
 */
class TicketResolved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupportTicket $ticket, public string $link, public string $ratingLink) {}

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
            ->subject("Tu solicitud {$this->ticket->code()} fue resuelta")
            ->greeting("Hola {$this->ticket->requester_name},")
            ->line("Marcamos como resuelta tu solicitud «{$this->ticket->subject}».")
            ->line('¿Cómo fue la atención? Tu calificación nos ayuda a mejorar.')
            ->action('Calificar la atención', $this->ratingLink)
            ->line("Si el problema continúa, responde desde la solicitud: {$this->link}");
    }
}
