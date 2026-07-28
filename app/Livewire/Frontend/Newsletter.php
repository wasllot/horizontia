<?php

namespace App\Livewire\Frontend;

use App\Models\Subscriber;
use Livewire\Component;

class Newsletter extends Component
{
    public $email;
    public $recaptchaToken;
    public $successMessage = '';

    public function subscribe()
    {
        $rules = [
            'email' => 'required|email|unique:subscribers,email',
        ];

        if (setting('_general.enable_recaptcha') == '1') {
            $rules['recaptchaToken'] = ['required', new \App\Rules\RecaptchaRule];
        }

        $this->validate($rules, [
            'email.required' => 'El correo electrónico es requerido.',
            'email.email' => 'El correo electrónico no es válido.',
            'email.unique' => 'Este correo ya está suscrito.',
            'recaptchaToken.required' => 'Por favor, completa la verificación del reCAPTCHA.'
        ]);

        Subscriber::create(['email' => $this->email]);

        $this->email = '';
        $this->successMessage = '¡Gracias por suscribirte a nuestro boletín!';
        
        $this->dispatch('showAlertMessage', type: 'success', title: 'Suscrito', message: $this->successMessage);
    }

    public function render()
    {
        return view('livewire.frontend.newsletter');
    }
}
