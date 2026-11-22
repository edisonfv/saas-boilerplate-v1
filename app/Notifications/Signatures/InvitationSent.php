<?php

namespace App\Notifications\Signatures;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The prepaid, single-use link to fill in a signature application.
 */
class InvitationSent extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $companyName,
        public string $customerName,
        public string $productName,
        public string $expiresOn,
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
            ->subject("{$this->companyName}: completa tu solicitud de firma electrónica")
            ->greeting("Hola {$this->customerName},")
            ->line("Tu «{$this->productName}» ya está pagada. Completa tus datos y sube tus documentos para emitirla.")
            ->action('Completar mi solicitud', $this->link)
            ->line("El enlace es personal, de un solo uso y vence el {$this->expiresOn}.");
    }
}
