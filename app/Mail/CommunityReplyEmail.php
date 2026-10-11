<?php

namespace App\Mail;

use App\Models\CommunityPost;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Courses\Models\Course;

/** «Respondieron tu pregunta». */
class CommunityReplyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CommunityPost $root, public CommunityPost $reply, public Course $course, public bool $byInstructor)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->byInstructor ? 'Tu instructor respondió: ' : 'Respondieron tu pregunta: ') . $this->root->title);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.community-reply', with: ['url' => route('courses.course-taking', ['slug' => $this->course->slug]) . '#comunidad']);
    }
}
