<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/** «Retoma tu curso»: avance, siguiente lección y un botón directo (con baja en un clic). */
class ProgressReminderEmail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<int, array{title:string, progress:int, lesson:?string, url:string, started:bool}> $courses */
    public function __construct(public User $user, public string $name, public array $courses, public string $unsubscribeUrl)
    {
    }

    public function envelope(): Envelope
    {
        $first = $this->courses[0];

        return new Envelope(subject: $first['started'] ? 'Sigue donde te quedaste: ' . html_entity_decode($first['title']) : 'Tu curso te espera: ' . html_entity_decode($first['title']));
    }

    public function headers(): Headers
    {
        return new Headers(text: ['List-Unsubscribe' => '<' . $this->unsubscribeUrl . '>']);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.progress-reminder');
    }
}
