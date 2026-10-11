<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Instructions for a payment that is not approved yet (OXXO/cash, SPEI transfer, card under review). */
class PaymentPendingEmail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array{kind:string, label:string, voucher_url:?string, expires_at:?string} $info */
    public function __construct(public Order $order, public string $studentName, public array $info)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->info['kind'] === 'review' ? 'Estamos revisando tu pago' : 'Completa tu pago para activar tu acceso');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.purchase.pending', with: ['statusUrl' => route('payment.pending', ['order' => $this->order->id])]);
    }
}
