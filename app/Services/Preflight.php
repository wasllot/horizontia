<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

/**
 * "Is this installation ready to take real money?" One list of checks used by `php artisan academy:preflight`
 * and by Admin → Estado del sistema. A check that only matters in production is a warning while developing
 * (so the page is useful everywhere) and an error once APP_ENV=production.
 */
class Preflight
{
    public const OK = 'ok';
    public const WARN = 'warn';
    public const FAIL = 'fail';

    /** Files that must never be downloadable: archives, dumps and environment copies. */
    private const FORBIDDEN_PUBLIC = ['*.zip', '*.sql', '*.sql.gz', '*.tar', '*.tar.gz', '*.rar', '*.7z', '*.bak', '.env', '.env.*', '*.log'];
    /** Maintenance scripts: token-protected, but they should not stay on a live server. */
    private const MAINTENANCE_SCRIPTS = ['test.php', 'readlog.php', 'scorm_diag.php', 'check_thumbnails.php', 'fix_course_view.php', 'clear_views.php', 'clear_cache.php', 'run_deploy.php', 'tareas.php'];

    private bool $production;

    public function __construct(private bool $checkDns = true)
    {
        $this->production = app()->environment('production');
    }

    /** @return array<int, array{key:string, group:string, label:string, status:string, detail:string, fix:string}> */
    public function run(): array
    {
        $checks = array_merge($this->security(), $this->operations(), $this->email(), $this->business());

        return array_values(array_filter($checks));
    }

    public static function summary(array $checks): array
    {
        return [
            'ok' => count(array_filter($checks, fn ($c) => $c['status'] === self::OK)),
            'warn' => count(array_filter($checks, fn ($c) => $c['status'] === self::WARN)),
            'fail' => count(array_filter($checks, fn ($c) => $c['status'] === self::FAIL)),
        ];
    }

    // ------------------------------------------------------------ helpers

    private function check(string $key, string $group, string $label, bool $ok, string $okDetail, string $badDetail, string $fix, string $severity = self::FAIL, bool $productionOnly = false): array
    {
        if ($ok) {
            return compact('key', 'group', 'label') + ['status' => self::OK, 'detail' => $okDetail, 'fix' => ''];
        }
        // Outside production a "production only" problem is just a reminder.
        $status = $productionOnly && !$this->production ? self::WARN : $severity;

        return compact('key', 'group', 'label', 'fix') + ['status' => $status, 'detail' => $badDetail . ($productionOnly && !$this->production ? ' (pendiente para producción)' : '')];
    }

    private function heartbeatAge(string $name): ?int
    {
        $at = Cache::get('heartbeat:' . $name);

        return $at ? (int) abs(\Carbon\Carbon::parse($at)->diffInSeconds(now())) : null;
    }

    // ------------------------------------------------------------ groups

    private function security(): array
    {
        $out = [];
        $out[] = $this->check('env', 'Seguridad', 'Entorno de producción', app()->environment('production'), 'APP_ENV=production', 'APP_ENV=' . app()->environment(), 'Pon APP_ENV=production en el .env del servidor.', self::FAIL, true);
        $out[] = $this->check('debug', 'Seguridad', 'Modo depuración apagado', !config('app.debug'), 'APP_DEBUG=false', 'APP_DEBUG=true muestra rutas, claves y consultas a cualquiera que provoque un error.', 'Pon APP_DEBUG=false.', self::FAIL, true);
        $out[] = $this->check('key', 'Seguridad', 'Clave de la aplicación', !empty(config('app.key')), 'APP_KEY definida', 'Falta APP_KEY.', 'Ejecuta php artisan key:generate.');
        $out[] = $this->check('https', 'Seguridad', 'Sitio servido por HTTPS', str_starts_with((string) config('app.url'), 'https://'), config('app.url'), 'APP_URL=' . config('app.url'), 'Usa un dominio con certificado y APP_URL=https://… (Mercado Pago y las cookies seguras lo exigen).', self::FAIL, true);
        $out[] = $this->check('cookies', 'Seguridad', 'Cookies de sesión seguras', (bool) config('session.secure'), 'SESSION_SECURE_COOKIE=true', 'Las cookies de sesión viajan también por HTTP.', 'Pon SESSION_SECURE_COOKIE=true.', self::WARN, true);

        $found = [];
        foreach (self::FORBIDDEN_PUBLIC as $pattern) {
            foreach (glob(public_path($pattern)) ?: [] as $f) {
                $found[] = basename($f);
            }
        }
        $out[] = $this->check('public_files', 'Seguridad', 'Sin archivos descargables en public/', $found === [], 'Nada que descargar', 'Se pueden descargar: ' . implode(', ', array_slice($found, 0, 5)), 'Muévelos fuera de public/ (cualquiera puede bajarlos).');

        $scripts = array_values(array_filter(self::MAINTENANCE_SCRIPTS, fn ($f) => File::exists(public_path($f))));
        $out[] = $this->check('maintenance_scripts', 'Seguridad', 'Sin scripts de mantenimiento en public/', $scripts === [], 'Ninguno', count($scripts) . ' script(s) accesibles con token: ' . implode(', ', array_slice($scripts, 0, 4)), 'Bórralos del servidor: sus tareas existen como comandos artisan.', self::WARN, true);

        $admin = User::where('email', 'admin@amentotech.com')->first();
        $weak = $admin && (Hash::check('google', $admin->password) || Hash::check('password', $admin->password));
        $out[] = $this->check('admin_password', 'Seguridad', 'Contraseña del administrador inicial', !$weak, 'No es la de fábrica', 'admin@amentotech.com sigue con la contraseña de fábrica.', 'Cámbiala desde tu perfil o crea otro administrador y elimina esta cuenta.');

        $demo = User::where('email', 'like', '%@horizontia.test')->count();
        $out[] = $this->check('demo_accounts', 'Seguridad', 'Sin cuentas de demostración', $demo === 0, 'Ninguna', "{$demo} cuentas @horizontia.test con contraseña conocida", 'Elimínalas antes de abrir al público.', self::FAIL, true);

        $legacy = collect(['personal_photo', 'attachments', 'transcript'])->contains(fn ($col) => \App\Models\UserIdentityVerification::whereNotNull($col)->where($col, 'not like', 'identity_photo/%')->exists());
        $out[] = $this->check('identity_legacy', 'Seguridad', 'Documentos de identidad privados', !$legacy, 'Todos en almacenamiento privado', 'Hay documentos de identidad guardados con el sistema anterior.', 'Revísalos y vuelve a pedirlos: los nuevos se guardan en el disco privado.', self::WARN);

        return $out;
    }

    private function operations(): array
    {
        $out = [];
        $out[] = $this->check('queue_driver', 'Operación', 'Cola de trabajos real', config('queue.default') !== 'sync', 'QUEUE_CONNECTION=' . config('queue.default'), 'QUEUE_CONNECTION=sync: los correos se envían dentro de la petición del usuario y se pierden si fallan.', 'Usa redis o database y un worker (php artisan queue:work).', self::FAIL, true);

        $q = $this->heartbeatAge('queue');
        $out[] = $this->check('queue_worker', 'Operación', 'Worker de la cola activo', $q !== null && $q < 900, $q === null ? '' : "último latido hace {$q} s", $q === null ? 'Nunca se ha visto un worker procesando trabajos.' : "El último latido fue hace " . intdiv($q, 60) . ' min.', 'Levanta el worker (php artisan queue:work) bajo supervisor/systemd o el servicio `queue` de docker-compose.', self::FAIL, true);

        $s = $this->heartbeatAge('scheduler');
        $out[] = $this->check('scheduler', 'Operación', 'Planificador activo (recordatorios, respaldos)', $s !== null && $s < 300, $s === null ? '' : "último latido hace {$s} s", $s === null ? 'El planificador nunca se ha ejecutado.' : 'El último latido fue hace ' . intdiv($s, 60) . ' min.', 'Programa `php artisan schedule:run` cada minuto (cron) o usa `schedule:work` / el servicio `scheduler` de docker-compose.', self::FAIL, true);

        $last = $this->lastBackup();
        $out[] = $this->check('backup', 'Operación', 'Respaldo de la base de datos reciente', $last !== null && $last->gt(now()->subHours(30)), $last ? 'último: ' . $last->diffForHumans() : '', $last ? 'El último respaldo fue ' . $last->diffForHumans() . '.' : 'No hay respaldos.', 'Se hacen solos cada madrugada si corre el planificador; prueba con php artisan app:backup-database. Guarda además una copia fuera del servidor.', self::FAIL, true);

        $private = config('filesystems.private_disk');
        $out[] = $this->check('private_disk', 'Operación', 'Almacenamiento privado para videos y documentos', !empty($private) && $private !== 'public', (string) $private, 'PRIVATE_FILESYSTEM_DISK no apunta a un disco privado.', 'Configura el disco privado (r2_private en docs/r2-storage.md).', self::WARN, true);

        $monitored = !empty(config('sentry.dsn')) || !empty(config('sentry.dsn')) || ErrorAlert::enabled();
        $out[] = $this->check('monitoring', 'Operación', 'Aviso de errores del sitio', $monitored, 'Los errores inesperados te llegan por correo', 'Nadie se entera cuando una página falla.', 'Pon ACADEMY_ALERT_EMAIL=tu@correo en el .env (aviso por correo, una vez por hora por error) o instala Sentry (docs/produccion.md).', self::WARN, true);

        return $out;
    }

    private function email(): array
    {
        $out = [];
        $mailer = config('mail.default');
        $host = (string) config('mail.mailers.smtp.host');
        $real = !in_array($mailer, ['log', 'array'], true) && !in_array($host, ['mailpit', 'localhost', '127.0.0.1', 'mailhog'], true);
        $out[] = $this->check('mailer', 'Correo', 'Proveedor de correo real', $real, "{$mailer}" . ($host ? " ({$host})" : ''), "MAIL_MAILER={$mailer}" . ($host ? " · {$host}" : '') . ': los correos no llegan a nadie.', 'Configura un proveedor transaccional (Resend, Postmark, SES, Brevo…) en el .env.', self::FAIL, true);

        $from = (string) config('mail.from.address');
        $domain = substr(strrchr($from, '@') ?: '', 1);
        $fromOk = $domain !== '' && !in_array($domain, ['example.com', 'localhost', 'horizontia.test'], true) && !str_ends_with($domain, '.test');
        $out[] = $this->check('mail_from', 'Correo', 'Remitente con tu dominio', $fromOk, $from, "MAIL_FROM_ADDRESS={$from}", 'Usa una dirección de tu dominio (p. ej. no-responder@tudominio.com).', self::FAIL, true);

        if ($this->checkDns && $fromOk) {
            $out[] = $this->dnsCheck($domain);
        }

        return $out;
    }

    private function dnsCheck(string $domain): array
    {
        $txt = function (string $host): array {
            try {
                return array_map(fn ($r) => $r['txt'] ?? '', @dns_get_record($host, DNS_TXT) ?: []);
            } catch (\Throwable) {
                return [];
            }
        };
        $spf = collect($txt($domain))->contains(fn ($t) => str_starts_with($t, 'v=spf1'));
        $dmarc = collect($txt('_dmarc.' . $domain))->contains(fn ($t) => str_starts_with($t, 'v=DMARC1'));
        $missing = array_filter(['SPF' => !$spf, 'DMARC' => !$dmarc]);

        return $this->check('mail_dns', 'Correo', 'SPF y DMARC del dominio', $missing === [], 'SPF y DMARC publicados en ' . $domain, 'Faltan registros DNS en ' . $domain . ': ' . implode(', ', array_keys($missing)), 'Publica SPF, DKIM (lo da tu proveedor) y DMARC; sin ellos los correos de compra caen en spam.', self::WARN, true);
    }

    private function business(): array
    {
        $out = [];
        $mp = (array) (setting('admin_settings.payment_method.mercadopago') ?? []);
        $mpOn = ($mp['status'] ?? 'off') === 'on';
        $out[] = $this->check('mp_enabled', 'Cobros', 'Mercado Pago activo', $mpOn && !empty($mp['mp_access_token']), 'Activo', 'Mercado Pago no está activo o falta el token.', 'Admin → Finanzas → Métodos de pago.', self::WARN, true);
        if ($mpOn) {
            $out[] = $this->check('mp_webhook', 'Cobros', 'Firma del webhook de Mercado Pago', !empty($mp['mp_webhook_secret']), 'Configurada', 'Sin clave secreta del webhook: los pagos aprobados no se confirman.', 'Copia la clave secreta del webhook desde tu panel de Mercado Pago.', self::FAIL);
            $test = str_starts_with((string) ($mp['mp_access_token'] ?? ''), 'TEST-') || !empty($mp['enable_test_mode']);
            $out[] = $this->check('mp_live', 'Cobros', 'Mercado Pago en modo real', !$test, 'Credenciales de producción', 'Estás usando credenciales o modo de prueba: no se cobra dinero real.', 'Cambia al token de producción (APP-USR-…) y apaga el modo de pruebas.', self::FAIL, true);
        }

        $contact = !empty(config('academy.contact_email')) && !empty(config('academy.address'));
        $out[] = $this->check('legal_data', 'Cobros', 'Datos legales de la academia', $contact && !empty(config('academy.legal_name')), 'Completos', 'Faltan correo de contacto, dirección o razón social en el .env (ACADEMY_*).', 'Complétalos: salen en los avisos legales. Haz que un asesor legal revise los textos.', self::WARN, true);

        return $out;
    }

    // ------------------------------------------------------------ backups

    public function lastBackup(): ?\Carbon\Carbon
    {
        $ts = Cache::get('heartbeat:backup');
        if ($ts) {
            return \Carbon\Carbon::parse($ts);
        }
        $files = glob(storage_path('app/backups/*.sql.gz')) ?: [];

        return $files ? \Carbon\Carbon::createFromTimestamp(max(array_map('filemtime', $files))) : null;
    }
}
