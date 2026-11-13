<?php

namespace App\Support;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/** Explica por qué falló un envío de correo: detalle técnico completo (sin credenciales) y una pista en español de la causa probable. */
class MailDiagnostics
{
    /** Quita contraseñas y usuarios del texto: valores de configuración, líneas AUTH y credenciales en base64 del diálogo SMTP. */
    public static function scrub(string $text, array $secrets = []): string
    {
        foreach (array_filter($secrets, fn ($s) => is_string($s) && strlen($s) >= 3) as $secret) {
            $text = str_replace([$secret, rawurlencode($secret), base64_encode($secret)], '[oculto]', $text);
        }
        $text = preg_replace('/\b(AUTH\s+(?:PLAIN|LOGIN|XOAUTH2|CRAM-MD5))\s+\S+/i', '$1 [oculto]', $text);
        $text = preg_replace('/^(\s*>\s*)[A-Za-z0-9+\/]{6,}={0,2}\s*$/m', '$1[oculto]', $text);

        return (string) $text;
    }

    /** @return array{class: string, message: string, chain: array<int, string>, debug: string, hint: ?string} */
    public static function describe(\Throwable $e, array $secrets = []): array
    {
        $chain = [];
        for ($p = $e->getPrevious(); $p; $p = $p->getPrevious()) {
            $chain[] = get_class($p) . ': ' . self::scrub(preg_replace('/\s+/', ' ', $p->getMessage()), $secrets);
        }
        $debug = $e instanceof TransportExceptionInterface ? trim($e->getDebug()) : '';
        $message = self::scrub(trim($e->getMessage()), $secrets);

        return [
            'class' => get_class($e),
            'message' => mb_substr($message, 0, 1500),
            'chain' => $chain,
            'debug' => mb_substr(self::scrub($debug, $secrets), 0, 3000),
            'hint' => self::hint($e->getMessage() . ' ' . $debug . ' ' . implode(' ', $chain)),
        ];
    }

    public static function hint(string $text): ?string
    {
        $t = mb_strtolower($text);
        $rules = [
            ['/redis|connection.*6379|predis|phpredis/', 'La cola usa Redis y no está disponible. Revisa QUEUE_CONNECTION (en hosting sin Redis usa database con ACADEMY_QUEUE_VIA_SCHEDULER=true).'],
            ['/sqlstate|table.*jobs|jobs.*doesn.?t exist/', 'La cola usa la base de datos y falta la tabla de trabajos. Corre las migraciones o cambia QUEUE_CONNECTION.'],
            ['/\b535\b|authenticat|username and password|invalid credentials|auth.*(fail|reject)/', 'El servidor rechazó el usuario o la contraseña. Revisa MAIL_USERNAME y MAIL_PASSWORD (si la cuenta tiene verificación en dos pasos, usa una contraseña de aplicación).'],
            ['/connection (refused|could not be established)|could not connect|timed? ?out|network is unreachable|no route to host|name or service not known|getaddrinfo|resolve host/', 'No se pudo conectar al servidor. Revisa MAIL_HOST y MAIL_PORT, y que el hosting no bloquee el puerto de salida (587 o 465).'],
            ['/certificate|ssl|tls|crypto|handshake|starttls/', 'Falló el cifrado. Prueba MAIL_ENCRYPTION=tls con el puerto 587 (o ssl con el 465), y que el nombre del servidor coincida con su certificado.'],
            ['/relay|not permitted|not allowed|sender.*(reject|denied)|\b553\b|\b554\b|domain.*not|spf|dmarc/', 'El servidor rechazó el remitente o el destino. MAIL_FROM_ADDRESS debe ser una cuenta o dominio autorizado en ese servidor SMTP.'],
            ['/\b550\b|mailbox|user unknown|no such user|does not exist/', 'El servidor dice que el buzón no existe o no se puede entregar. Verifica la dirección del destinatario.'],
            ['/\b4(21|50|51|52)\b|quota|rate|too many|limit|try again|temporar/', 'Rechazo temporal: probablemente un límite de envíos o de conexiones. Espera unos minutos y vuelve a intentar.'],
        ];
        foreach ($rules as [$regex, $hint]) {
            if (preg_match($regex, $t)) {
                return $hint;
            }
        }

        return null;
    }
}
