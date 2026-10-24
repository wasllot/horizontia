<?php

namespace App\Mail;

use App\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Respuesta a la persona: reembolso aprobado o rechazado (con el motivo). */
class RefundDecisionEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public RefundRequest $request)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->request->status === RefundRequest::APPROVED ? 'Aprobamos tu reembolso' : 'Sobre tu solicitud de reembolso');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.refund-decision', with: ['request' => $this->request, 'days' => (int) config('academy.refund_days')]);
    }
}
