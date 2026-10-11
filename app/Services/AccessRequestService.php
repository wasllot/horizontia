<?php

namespace App\Services;

use App\Mail\AccessApprovedEmail;
use App\Mail\AccessExistingAccountEmail;
use App\Mail\AccessRejectedEmail;
use App\Mail\AccessRequestAdminEmail;
use App\Mail\AccessRequestReceivedEmail;
use App\Models\AccessRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Cómo se entra a la academia: nadie se registra solo. La persona pide acceso (estudiante o instructor), el equipo lo aprueba,
 * se crea su cuenta y recibe un enlace (válido 7 días, de un solo uso) para elegir su contraseña.
 */
class AccessRequestService
{
    public const PER_HOUR = 5;

    public static function enabled(): bool
    {
        return (bool) config('academy.access_requests', true);
    }

    public static function pendingCount(): int
    {
        return AccessRequest::where('status', AccessRequest::PENDING)->count();
    }

    /**
     * Guarda la solicitud. La respuesta es la misma tenga o no cuenta ese correo (no se revela quién existe).
     *
     * @return array{ok: bool, error?: string}
     */
    public function submit(array $data, string $ip): array
    {
        $key = 'access-request:' . sha1($ip);
        if (RateLimiter::tooManyAttempts($key, self::PER_HOUR)) {
            return ['ok' => false, 'error' => 'Has enviado varias solicitudes seguidas. Inténtalo de nuevo en un rato.'];
        }
        RateLimiter::hit($key, 3600);

        $email = mb_strtolower(trim($data['email']));
        $existing = User::where('email', $email)->first();
        if ($existing) {
            $this->queue($existing->email, new AccessExistingAccountEmail($data['name']));

            return ['ok' => true];
        }
        // Una solicitud pendiente del mismo correo no se duplica (se actualiza con lo último que escribió).
        $request = AccessRequest::where('email', $email)->where('status', AccessRequest::PENDING)->first() ?? new AccessRequest(['email' => $email, 'status' => AccessRequest::PENDING]);
        $request->fill([
            'type' => $data['type'] === 'tutor' ? 'tutor' : 'student',
            'name' => mb_substr(trim(strip_tags($data['name'])), 0, 120),
            'phone' => $data['phone'] ? mb_substr(preg_replace('/[^\d+\s().\-]/', '', $data['phone']), 0, 30) : null,
            'message' => $data['message'] ? mb_substr(trim(strip_tags($data['message'])), 0, 1500) : null,
            'link' => $data['link'] ? mb_substr(trim(strip_tags($data['link'])), 0, 250) : null,
            'ip_hash' => hash('sha256', $ip . config('app.key')),
        ]);
        $isNew = !$request->exists;
        $request->save();

        if ($isNew) {
            $this->queue($email, new AccessRequestReceivedEmail($request));
            foreach (User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->pluck('email') as $admin) {
                $this->queue($admin, new AccessRequestAdminEmail($request));
            }
        }

        return ['ok' => true];
    }

    private function queue(?string $to, $mailable): void
    {
        if (!$to) {
            return;
        }
        try {
            Mail::to($to)->queue($mailable);
        } catch (\Throwable $e) {
            report($e);   // un correo que falla nunca debe perder la solicitud
        }
    }

    public function approve(AccessRequest $request, User $admin): User
    {
        abort_unless($request->status === AccessRequest::PENDING, 409);
        $role = $request->type === 'tutor' ? 'tutor' : 'student';

        $user = DB::transaction(function () use ($request, $admin, $role) {
            $user = User::where('email', $request->email)->first() ?? User::create(['email' => $request->email, 'password' => Hash::make(Str::random(40))]);
            if (!$user->profile) {
                $parts = preg_split('/\s+/', trim($request->name), 2);
                $user->profile()->create(['first_name' => $parts[0] ?? $request->name, 'last_name' => $parts[1] ?? '', 'phone_number' => $request->phone]);
            }
            $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();   // el correo ya es suyo: lo confirmó al recibir el enlace
            if (!$user->hasRole($role)) {
                $user->assignRole($role);
            }
            $request->update(['status' => AccessRequest::APPROVED, 'user_id' => $user->id, 'reviewed_by' => $admin->id, 'reviewed_at' => now(), 'review_note' => null]);

            return $user;
        });

        $this->queue($user->email, new AccessApprovedEmail($request->fresh(), URL::temporarySignedRoute('account.activate', now()->addDays(7), ['request' => $request->id])));

        return $user;
    }

    public function reject(AccessRequest $request, User $admin, ?string $note): void
    {
        abort_unless($request->status === AccessRequest::PENDING, 409);
        $request->update(['status' => AccessRequest::REJECTED, 'reviewed_by' => $admin->id, 'reviewed_at' => now(), 'review_note' => $note ? mb_substr(trim(strip_tags($note)), 0, 300) : null]);
        $this->queue($request->email, new AccessRejectedEmail($request->fresh()));
    }
}
