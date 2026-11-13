<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\MailTestEmail;
use App\Support\MailDiagnostics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/** Admin → Prueba de correo: manda un mensaje de prueba (directo por SMTP o por la cola) a los correos que se escriban (por defecto, ACADEMY_MAIL_TEST_TO). */
class MailTestController extends Controller
{
    /** @return array<int, string> */
    private function recipients(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) config('academy.mail_test_to')))));
    }

    public function show(Request $request)
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        return view('admin.mail-test', [
            'info' => $this->info(), 'results' => session('mail_test_results'), 'mode' => session('mail_test_mode'),
            'emails' => session('mail_test_emails', implode(', ', $this->recipients())), 'failed' => $this->recentFailedJobs(),
        ]);
    }

    public function send(Request $request)
    {
        abort_unless($request->user()?->hasRole('admin'), 403);
        $mode = $request->input('mode') === 'cola' ? 'cola' : 'directo';
        $raw = trim((string) $request->input('emails'));
        $list = $raw === '' ? $this->recipients() : array_values(array_unique(array_map('mb_strtolower', preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [])));
        $invalid = array_values(array_filter($list, fn ($e) => !filter_var($e, FILTER_VALIDATE_EMAIL)));
        if ($invalid || !$list || count($list) > 5) {
            $why = $invalid ? 'Correo no válido: ' . implode(', ', $invalid) . '.' : (!$list ? 'Escribe al menos un correo.' : 'Máximo 5 correos por prueba.');

            return redirect()->route('admin.mail-test')->with(['mail_test_emails' => $raw, 'mail_test_error' => $why]);
        }

        $secrets = [config('mail.mailers.smtp.password'), config('mail.mailers.smtp.username')];
        $results = [];
        foreach ($list as $to) {
            try {
                if ($mode === 'cola') {
                    Mail::to($to)->queue(new MailTestEmail('cola'));
                    $results[] = ['to' => $to, 'ok' => true, 'msg' => 'Puesto en la cola. Llega cuando el worker o el cron la procese (normalmente en menos de un minuto).'];
                } else {
                    Mail::to($to)->send(new MailTestEmail('directo'));
                    $results[] = ['to' => $to, 'ok' => true, 'msg' => 'El servidor SMTP aceptó el mensaje.'];
                }
            } catch (\Throwable $e) {
                $results[] = ['to' => $to, 'ok' => false, 'msg' => get_class($e), 'error' => MailDiagnostics::describe($e, $secrets)];
            }
        }

        return redirect()->route('admin.mail-test')->with(['mail_test_results' => $results, 'mail_test_mode' => $mode, 'mail_test_emails' => implode(', ', $list)]);
    }

    /** Últimos trabajos fallidos de la cola (por si el correo se puso en cola pero el worker no pudo enviarlo). */
    private function recentFailedJobs(): array
    {
        if (!Schema::hasTable('failed_jobs')) {
            return [];
        }
        $secrets = [config('mail.mailers.smtp.password'), config('mail.mailers.smtp.username')];

        return DB::table('failed_jobs')->orderByDesc('id')->limit(3)->get()->map(fn ($f) => [
            'when' => (string) $f->failed_at, 'queue' => $f->queue,
            'error' => mb_substr(MailDiagnostics::scrub((string) strtok((string) $f->exception, "\n"), $secrets), 0, 400),
        ])->all();
    }

    /** Configuración efectiva de correo y cola, sin contraseñas. */
    private function info(): array
    {
        $mailer = (string) config('mail.default');
        $smtp = (array) config('mail.mailers.smtp');
        $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : null;
        $pending = config('queue.default') === 'database' && Schema::hasTable('jobs') ? DB::table('jobs')->count() : null;

        return [
            'Mailer' => $mailer . (in_array($mailer, ['log', 'array'], true) ? '  ⚠ no entrega correos reales' : ''),
            'Servidor SMTP' => ($smtp['host'] ?? '—') . ':' . ($smtp['port'] ?? '—') . ' · ' . ($smtp['scheme'] ?? $smtp['encryption'] ?? 'sin cifrado'),
            'Usuario / contraseña' => (filled($smtp['username'] ?? null) ? 'usuario capturado' : 'SIN usuario') . ' · ' . (filled($smtp['password'] ?? null) ? 'contraseña capturada' : 'SIN contraseña'),
            'Remitente' => config('mail.from.address') . ' (' . config('mail.from.name') . ')',
            'Cola' => config('queue.default') . (config('academy.queue_via_scheduler') ? ' · procesada por el cron (schedule:run)' : ''),
            'Trabajos pendientes' => $pending === null ? 'no disponible para este tipo de cola' : (string) $pending,
            'Trabajos fallidos (histórico)' => $failed === null ? '—' : (string) $failed,
            'Destinatarios de la prueba' => implode(', ', $this->recipients()) ?: 'ninguno (define ACADEMY_MAIL_TEST_TO)',
        ];
    }
}
