<?php

namespace App\Livewire\Frontend;

use Livewire\Component;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactMessage;
use Illuminate\Support\Facades\Log;

class ContactForm extends Component
{
    public $name;
    public $email;
    public $subject;
    public $message;
    public $successMessage = '';
    public $recaptchaToken;

    public function submit()
    {
        $rules = [
            'name' => 'required|min:3|max:100',
            'email' => 'required|email|max:100',
            'subject' => 'required|min:3|max:150',
            'message' => 'required|min:10',
        ];

        if (setting('_general.enable_recaptcha') == '1') {
            $rules['recaptchaToken'] = ['required', new \App\Rules\RecaptchaRule];
        }

        $this->validate($rules, [
            'recaptchaToken.required' => 'Por favor, completa la verificación del reCAPTCHA.'
        ]);

        try {
            $data = [
                'name' => $this->name,
                'email' => $this->email,
                'subject' => $this->subject,
                'message' => $this->message,
            ];

            // Envía al correo configurado en .env (MAIL_FROM_ADDRESS) o a uno de admin
            $toEmail = env('MAIL_FROM_ADDRESS', 'hello@horizontia.test');
            Mail::to($toEmail)->send(new ContactMessage($data));

            // Guardar en base de datos
            \App\Models\Contact::create($data);

            $this->successMessage = '¡Gracias por contactarnos! Tu mensaje ha sido enviado con éxito.';
            $this->reset(['name', 'email', 'subject', 'message']);
            
        } catch (\Exception $e) {
            Log::error('Contact form error: ' . $e->getMessage());
            $this->addError('email', 'Hubo un error al enviar tu mensaje. Por favor intenta de nuevo más tarde.');
        }
    }

    public function render()
    {
        return view('livewire.frontend.contact-form');
    }
}
