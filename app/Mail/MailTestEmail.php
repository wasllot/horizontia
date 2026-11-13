<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Correo de prueba de Admin → Prueba de correo: confirma que el servidor SMTP y la cola entregan mensajes. */
class MailTestEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $mode)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Prueba de correo · ' . config('app.name') . ' (' . $this->mode . ')');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.mail-test', with: [
            'mode' => $this->mode, 'sentAt' => now()->timezone(config('academy.timezone', 'America/Mexico_City')),
            'appUrl' => config('app.url'), 'from' => config('mail.from.address'),
        ]);
    }
}
