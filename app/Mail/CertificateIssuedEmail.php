<?php

namespace App\Mail;

use App\Models\CourseCertificate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** «Terminaste tu curso»: el certificado ya existe, con su enlace de verificación y el botón para agregarlo a LinkedIn. */
class CertificateIssuedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CourseCertificate $certificate)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: '¡Felicidades! Tu certificado de «' . $this->certificate->course_title . '» está listo');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.certificate-issued', with: [
            'cert' => $this->certificate,
            'url' => $this->certificate->url(),
            'linkedin' => $this->certificate->linkedinProfileUrl(),
        ]);
    }
}
