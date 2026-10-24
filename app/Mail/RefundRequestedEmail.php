<?php

namespace App\Mail;

use App\Models\RefundRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Aviso al equipo: hay una solicitud de reembolso por revisar. */
class RefundRequestedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public RefundRequest $request)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Nueva solicitud de reembolso · pedido #' . $this->request->order_id);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.refund-requested', with: ['request' => $this->request, 'url' => route('admin.refunds')]);
    }
}
