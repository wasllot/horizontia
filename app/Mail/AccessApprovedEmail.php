<?php

namespace App\Mail;

use App\Models\AccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccessApprovedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AccessRequest $request, public string $activationUrl)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu acceso a ' . config('app.name') . ' está listo');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.access-approved');
    }
}
