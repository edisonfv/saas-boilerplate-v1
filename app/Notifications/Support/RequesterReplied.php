<?php

namespace App\Notifications\Support;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the assigned staff member when the requester replies.
 */
class RequesterReplied extends Notification implements ShouldQueue
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
            ->subject("El cliente respondió en {$this->ticket->code()}")
            ->line("{$this->ticket->requester_name} respondió al ticket «{$this->ticket->subject}».")
            ->action('Abrir ticket', $this->link);
    }
}
