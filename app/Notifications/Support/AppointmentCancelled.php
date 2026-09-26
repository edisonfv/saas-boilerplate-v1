<?php

namespace App\Notifications\Support;

use App\Models\SupportAppointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the requester or technician that a session was cancelled.
 */
class AppointmentCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupportAppointment $appointment, public string $link) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $when = $this->appointment->starts_at->timezone(config('app.timezone'))->translatedFormat('l j \\d\\e F, H:i');

        return (new MailMessage)
            ->subject("Cita de soporte cancelada: {$when}")
            ->line("Se canceló la cita del {$when} para el ticket {$this->appointment->ticket->code()}.")
            ->line('Motivo: '.($this->appointment->cancellation_reason ?: 'No indicado.'))
            ->action('Agendar de nuevo', $this->link);
    }
}
