<?php

namespace App\Notifications\Signatures;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the applicant after they apply on the tenant's website: their
 * request number and the link to pay / upload the payment receipt.
 * Carries plain data (not tenant models) so the queued mail doesn't need
 * the tenant's database.
 */
class PaymentLinkSent extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $companyName,
        public string $applicantName,
        public string $code,
        public string $productName,
        public ?string $amount,
        public string $link,
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
        return (new MailMessage)
            ->subject("{$this->companyName}: tu solicitud de firma {$this->code}")
            ->greeting("Hola {$this->applicantName},")
            ->line("Recibimos tu solicitud de «{$this->productName}» con el número {$this->code}.")
            ->line($this->amount !== null
                ? "Para continuar, realiza el pago de \${$this->amount} y sube tu comprobante con el siguiente enlace."
                : 'Para continuar, realiza el pago y sube tu comprobante con el siguiente enlace.')
            ->action('Pagar y subir comprobante', $this->link)
            ->line('El enlace es válido por 7 días. Tu firma se emite una vez confirmado el pago.');
    }
}
