<?php

namespace App\Mail;

use App\Models\CommunityPost;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Courses\Models\Course;

/** «Hay una pregunta nueva en tu curso» (máximo un correo por hora y curso). */
class CommunityQuestionEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CommunityPost $post, public Course $course)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Nueva pregunta en «' . html_entity_decode($this->course->title) . '»');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.community-question', with: ['url' => route('courses.course-taking', ['slug' => $this->course->slug]) . '#comunidad']);
    }
}
