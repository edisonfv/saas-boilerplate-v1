<?php

namespace App\Notifications\Signatures;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the applicant their payment was confirmed, or rejected with the
 * reason and a fresh link to upload another receipt.
 */
class PaymentReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $companyName,
        public string $applicantName,
        public string $code,
        public bool $approved,
        public ?string $reason = null,
        public ?string $link = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->greeting("Hola {$this->applicantName},");

        if ($this->approved) {
            return $mail
                ->subject("{$this->companyName}: confirmamos el pago de {$this->code}")
                ->line("Confirmamos el pago de tu solicitud de firma electrónica {$this->code}.")
                ->line('Ahora la enviaremos a la entidad certificadora, que validará tu identidad y te enviará tu firma por correo.');
        }

        $mail = $mail
            ->subject("{$this->companyName}: no pudimos confirmar el pago de {$this->code}")
            ->line("No pudimos confirmar el pago de tu solicitud {$this->code}.")
            ->line("Motivo: {$this->reason}");

        return $this->link !== null
            ? $mail->action('Subir otro comprobante', $this->link)
            : $mail;
    }
}
