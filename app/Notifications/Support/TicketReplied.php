<?php

namespace App\Notifications\Support;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the requester when staff replies (never for internal notes).
 */
class TicketReplied extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupportTicket $ticket, public SupportTicketMessage $message, public string $link) {}

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
            ->subject("Nueva respuesta en {$this->ticket->code()}")
            ->greeting("Hola {$this->ticket->requester_name},")
            ->line("{$this->message->author_name} respondió a tu solicitud «{$this->ticket->subject}»:")
            ->line(str($this->message->body)->limit(500)->toString())
            ->action('Ver la conversación', $this->link);
    }
}
