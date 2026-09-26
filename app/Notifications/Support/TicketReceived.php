<?php

namespace App\Notifications\Support;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the requester when their ticket is registered.
 */
class TicketReceived extends Notification implements ShouldQueue
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
            ->subject("Recibimos tu solicitud {$this->ticket->code()}")
            ->greeting("Hola {$this->ticket->requester_name},")
            ->line("Registramos tu solicitud «{$this->ticket->subject}» con el código {$this->ticket->code()}.")
            ->line('Nuestro equipo la revisará y te responderá por este medio.')
            ->action('Ver mi solicitud', $this->link);
    }
}
