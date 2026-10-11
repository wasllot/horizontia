<?php

namespace App\Livewire\Frontend;

use App\Services\AccessRequestService;
use Livewire\Component;

/** Formulario público «Solicitar acceso» (como estudiante o como instructor). */
class RequestAccess extends Component
{
    public string $type = 'student';
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $message = '';
    public string $link = '';
    public bool $consent = false;
    public string $website = '';          // señuelo anti-bots: una persona nunca lo ve ni lo llena
    public $recaptchaToken;
    public bool $sent = false;

    public function mount(): void
    {
        abort_unless(AccessRequestService::enabled(), 404);
        $this->type = request()->query('como') === 'instructor' ? 'tutor' : 'student';
    }

    public function choose(string $type): void
    {
        $this->type = $type === 'tutor' ? 'tutor' : 'student';
        $this->resetValidation();
    }

    public function submit(AccessRequestService $service): void
    {
        $rules = [
            'type' => 'required|in:student,tutor',
            'name' => 'required|string|min:3|max:120',
            'email' => 'required|email:rfc|max:150',
            'phone' => 'nullable|string|max:30',
            'message' => $this->type === 'tutor' ? 'required|string|min:30|max:1500' : 'nullable|string|max:1500',
            'link' => 'nullable|url:http,https|max:250',
            'consent' => 'accepted',
        ];
        if (recaptchaEnabled('contact')) {
            $rules['recaptchaToken'] = ['required', new \App\Rules\RecaptchaRule];
        }
        $this->validate($rules, [
            'name.required' => 'Escribe tu nombre completo.', 'name.min' => 'Escribe tu nombre completo.',
            'email.required' => 'Escribe tu correo electrónico.', 'email.email' => 'Revisa tu correo: no parece válido.',
            'message.required' => 'Cuéntanos sobre tu experiencia (al menos 30 caracteres).', 'message.min' => 'Cuéntanos un poco más (al menos 30 caracteres).',
            'link.url' => 'El enlace debe empezar por https://', 'consent.accepted' => 'Necesitamos que aceptes el aviso de privacidad para continuar.',
            'recaptchaToken.required' => 'Por favor, completa la verificación del reCAPTCHA.',
        ]);

        if ($this->website !== '') {   // un bot llenó el señuelo: se responde como si todo hubiera salido bien
            $this->sent = true;

            return;
        }
        $result = $service->submit(['type' => $this->type, 'name' => $this->name, 'email' => $this->email, 'phone' => $this->phone, 'message' => $this->message, 'link' => $this->link], request()->ip() ?? '0.0.0.0');
        if (!$result['ok']) {
            $this->addError('email', $result['error']);

            return;
        }
        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.frontend.request-access');
    }
}
