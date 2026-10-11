<?php

namespace App\Livewire\Frontend;

use App\Mail\ContactMessage;
use App\Models\Contact;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

/** Formulario de contacto: temas rápidos, validación clara, señuelo anti-bots y límite por IP. */
class ContactForm extends Component
{
    public const TOPICS = [
        'courses' => ['Cursos', 'Quiero información sobre un curso'],
        'payments' => ['Pagos', 'Tengo una duda sobre un pago o factura'],
        'access' => ['Mi cuenta', 'Necesito ayuda con mi acceso o mi cuenta'],
        'teach' => ['Quiero enseñar', 'Quiero enseñar en la academia'],
        'other' => ['Otro', ''],
    ];
    public const PER_HOUR = 5;

    public string $topic = '';
    public string $name = '';
    public string $email = '';
    public string $subject = '';
    public string $message = '';
    public string $website = '';          // señuelo anti-bots
    public $recaptchaToken;
    public bool $sent = false;

    public function mount(): void
    {
        $asunto = request()->query('asunto');
        if (is_string($asunto) && isset(self::TOPICS[$asunto])) {
            $this->pick($asunto);
        }
    }

    public function pick(string $topic): void
    {
        if (!isset(self::TOPICS[$topic])) {
            return;
        }
        $previous = self::TOPICS[$this->topic][1] ?? null;
        $this->topic = $topic;
        // Solo se reescribe el asunto si está vacío o es el que puso el tema anterior (no se pisa lo que la persona escribió).
        if ($this->subject === '' || $this->subject === $previous) {
            $this->subject = self::TOPICS[$topic][1];
        }
    }

    public function submit(): void
    {
        $rules = [
            'name' => 'required|string|min:3|max:100',
            'email' => 'required|email:rfc|max:100',
            'subject' => 'required|string|min:3|max:150',
            'message' => 'required|string|min:10|max:3000',
        ];
        if (recaptchaEnabled('contact')) {
            $rules['recaptchaToken'] = ['required', new \App\Rules\RecaptchaRule];
        }
        $this->validate($rules, [
            'name.required' => 'Escribe tu nombre.', 'name.min' => 'Escribe tu nombre completo.',
            'email.required' => 'Escribe tu correo para poder responderte.', 'email.email' => 'Revisa tu correo: no parece válido.',
            'subject.required' => 'Cuéntanos de qué se trata.', 'subject.min' => 'El asunto es muy corto.',
            'message.required' => 'Escribe tu mensaje.', 'message.min' => 'Cuéntanos un poco más (mínimo 10 caracteres).', 'message.max' => 'El mensaje es muy largo (máximo 3000 caracteres).',
            'recaptchaToken.required' => 'Por favor, completa la verificación del reCAPTCHA.',
        ]);

        if ($this->website !== '') {   // un bot llenó el señuelo: parece que salió bien, pero no se guarda nada
            $this->sent = true;

            return;
        }
        $key = 'contact-form:' . sha1((string) request()->ip());
        if (RateLimiter::tooManyAttempts($key, self::PER_HOUR)) {
            $this->addError('message', 'Has enviado varios mensajes seguidos. Inténtalo de nuevo en un rato.');

            return;
        }
        RateLimiter::hit($key, 3600);

        try {
            $data = ['name' => trim(strip_tags($this->name)), 'email' => $this->email, 'subject' => trim(strip_tags($this->subject)), 'message' => trim(strip_tags($this->message))];
            Contact::create($data);
            Mail::to(config('mail.from.address', 'hello@horizontia.test'))->send(new ContactMessage($data));
            $this->sent = true;
        } catch (\Throwable $e) {
            Log::error('Contact form error: ' . $e->getMessage());
            $this->addError('email', 'Hubo un error al enviar tu mensaje. Por favor intenta de nuevo más tarde.');
        }
    }

    public function again(): void
    {
        $this->reset('sent', 'topic', 'subject', 'message');
    }

    public function render()
    {
        $email = config('academy.contact_email') ?: (setting('_front_page_settings.footer_email') ?: config('mail.from.address'));

        return view('livewire.frontend.contact-form', [
            'topics' => self::TOPICS,
            'contactEmail' => $email,
            'address' => config('academy.address') ?: setting('_front_page_settings.footer_address'),
            'whatsapp' => function_exists('whatsappUrl') ? whatsappUrl() : null,
        ]);
    }
}
