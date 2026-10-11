<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Http;

class RecaptchaRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // If reCAPTCHA is disabled (or has no keys) the forms do not ask for it, so there is nothing to verify.
        if (!recaptchaEnabled()) {
            return true;
        }

        if (empty($value)) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(8)->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => recaptchaSecret(),
                'response' => $value,
                'remoteip' => request()->ip(),
            ]);
        } catch (\Throwable) {
            return false;   // Google unreachable: fail closed, the visitor can retry
        }

        return (bool) $response->json('success');
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Por favor, completa la verificación del reCAPTCHA.';
    }
}
