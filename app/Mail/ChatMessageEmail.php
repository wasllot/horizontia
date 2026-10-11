<?php

namespace App\Mail;

use App\Models\ChatMessage;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** «Tienes un mensaje nuevo» (máximo uno por hora y conversación). */
class ChatMessageEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $sender, public User $recipient, public ChatMessage $message)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Nuevo mensaje de ' . ChatService::displayName($this->sender));
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.chat-message', with: ['url' => route('laraguppy.messenger', ['con' => $this->sender->id]), 'name' => ChatService::displayName($this->sender)]);
    }
}
