<?php

namespace App\Notifications\Support;

use App\Models\SupportAppointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirms a booked session to the requester or to the assigned technician.
 */
class AppointmentBooked extends Notification implements ShouldQueue
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
            ->subject("Cita de soporte agendada: {$when}")
            ->line("Cita «{$this->appointment->attendanceType->name}» para el ticket {$this->appointment->ticket->code()}.")
            ->line("Fecha: {$when} ({$this->appointment->minutes()} min, hora ".config('app.timezone').').')
            ->line('El enlace de la videollamada aparecerá en el ticket antes de la sesión.')
            ->action('Ver detalle', $this->link);
    }
}
