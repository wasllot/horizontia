<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Receipt + access for a paid (or free) course purchase. Courses with an in-person date also get their own date e-mail. */
class PurchaseConfirmedEmail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<int, array{title:string, date:?string}> $courses */
    public function __construct(public Order $order, public string $studentName, public array $courses)
    {
    }

    public function envelope(): Envelope
    {
        $first = html_entity_decode($this->courses[0]['title'] ?? '');

        return new Envelope(subject: count($this->courses) > 1 ? 'Tu compra está confirmada: ' . count($this->courses) . ' cursos' : 'Tu compra está confirmada: ' . $first);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.purchase.confirmed', with: ['myCoursesUrl' => route('courses.course-list')]);
    }
}
