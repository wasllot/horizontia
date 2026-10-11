<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\CourseSession;

/** Everything a student gets about the in-person date they bought: confirmation, 24 h reminder, change and cancellation notices. */
class OnsiteDateEmail extends Mailable
{
    use Queueable, SerializesModels;

    public const KINDS = ['confirmation', 'reminder', 'changed', 'cancelled'];

    public function __construct(
        public Course $course,
        public CourseSession $session,
        public string $studentName,
        public string $kind = 'confirmation',
        public ?string $note = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $title = html_entity_decode((string) $this->course->title);

        return new Envelope(subject: match ($this->kind) {
            'reminder' => 'Mañana: ' . $title,
            'changed' => 'Cambio en tu fecha: ' . $title,
            'cancelled' => 'Se canceló tu fecha: ' . $title,
            default => 'Tu lugar está confirmado: ' . $title,
        });
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.onsite.date', with: [
            'myCoursesUrl' => route('courses.course-list'),
        ]);
    }

    public function attachments(): array
    {
        if ($this->kind === 'cancelled') {
            return [];
        }

        return [Attachment::fromData(fn () => $this->session->ics($this->course), 'fecha-curso.ics')->withMime('text/calendar')];
    }
}
