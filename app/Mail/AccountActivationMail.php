<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountActivationMail extends Mailable implements ShouldQueue // Es buena práctica implementar ShouldQueue para enviar correos en segundo plano
{
    use Queueable, SerializesModels;

    // Haz la propiedad pública para que esté disponible automáticamente en la vista
    public $activationUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(string $activationUrl)
    {
        $this->activationUrl = $activationUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Activa tu cuenta',
        // Puedes añadir remitente, destinatarios CC/BCC aquí si es necesario
        // from: new Address('info@example.com', 'Tu Aplicación'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account_activation',
            with: [
                'url' => $this->activationUrl,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
