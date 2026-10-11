<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/** "You left something in your cart" (max 2 per cart, with a one-click opt-out). */
class CartReminderEmail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<int, array{title:string, price:float, date:?string, url:string}> $items */
    public function __construct(public User $user, public string $name, public array $items, public string $unsubscribeUrl, public int $stage = 1)
    {
    }

    public function envelope(): Envelope
    {
        $first = html_entity_decode($this->items[0]['title']);

        return new Envelope(subject: $this->stage > 1 ? 'Tu lugar sigue disponible: ' . $first : '¿Olvidaste algo? ' . $first . ' te espera');
    }

    public function headers(): Headers
    {
        return new Headers(text: ['List-Unsubscribe' => '<' . $this->unsubscribeUrl . '>']);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.cart-reminder', with: ['checkoutUrl' => route('checkout')]);
    }
}
