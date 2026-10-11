<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Alguien pidió acceso con un correo que ya tiene cuenta: se le dice cómo entrar (sin revelarlo en la página pública). */
class AccessExistingAccountEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $name)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Ya tienes una cuenta en ' . config('app.name'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.access-existing');
    }
}
