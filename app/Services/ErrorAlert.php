<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Minimal "tell me when it breaks": unexpected server errors are e-mailed to ACADEMY_ALERT_EMAIL (and shown in the
 * admins' bell), at most once per hour per distinct error so a failing page cannot flood the inbox.
 * Sentry (or similar) is still the better tool; this is the dependency-free baseline.
 */
class ErrorAlert
{
    public static function recipient(): ?string
    {
        return config('academy.alert_email') ?: null;
    }

    public static function enabled(): bool
    {
        return (bool) self::recipient() && (app()->environment('production') || config('academy.error_alerts'));
    }

    public static function report(Throwable $e): void
    {
        try {
            if (!self::enabled() || self::ignored($e)) {
                return;
            }
            $signature = md5(get_class($e) . '|' . $e->getFile() . '|' . $e->getLine());
            if (!Cache::add('error-alert:' . $signature, 1, now()->addHour())) {
                return;   // already reported within the hour
            }

            $request = app()->bound('request') ? request() : null;
            $body = implode("\n", array_filter([
                get_class($e) . ': ' . $e->getMessage(),
                'Archivo: ' . str_replace(base_path() . '/', '', $e->getFile()) . ':' . $e->getLine(),
                $request && !app()->runningInConsole() ? 'URL: ' . $request->method() . ' ' . $request->fullUrl() : 'Consola: ' . implode(' ', array_slice($_SERVER['argv'] ?? [], 1, 3)),
                auth()->check() ? 'Usuario: #' . auth()->id() : null,
                'Hora: ' . now()->toDateTimeString(),
                '',
                'Más detalle en storage/logs/laravel.log. Si el mismo error se repite, no recibirás otro aviso durante una hora.',
            ]));

            Mail::to(self::recipient())->send(new \App\Mail\ErrorAlertEmail('[' . config('app.name') . '] Error en el sitio: ' . class_basename($e), $body));
        } catch (Throwable $inner) {
            Log::error('No se pudo enviar el aviso de error: ' . $inner->getMessage());
        }
    }

    /** Expected conditions are not alerts: 4xx, validation, authentication/authorization, token mismatches. */
    private static function ignored(Throwable $e): bool
    {
        return $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface && $e->getStatusCode() < 500
            || $e instanceof \Illuminate\Validation\ValidationException
            || $e instanceof \Illuminate\Auth\AuthenticationException
            || $e instanceof \Illuminate\Auth\Access\AuthorizationException
            || $e instanceof \Illuminate\Session\TokenMismatchException
            || $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
            || $e instanceof \Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
    }
}
