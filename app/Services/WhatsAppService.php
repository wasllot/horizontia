<?php

namespace App\Services;

use App\Jobs\SendWhatsAppMessage;
use App\Models\Order;
use App\Models\User;
use App\Models\WhatsappMessage;
use Illuminate\Support\Str;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\CourseLiveStream;
use Modules\Courses\Models\CourseSession;
use Modules\Courses\Models\Enrollment;

/**
 * Avisos automáticos por WhatsApp con la API oficial de Meta (WhatsApp Business Cloud API) y plantillas aprobadas.
 *
 * Reglas: solo se escribe a quien lo aceptó (casilla en el pago), cada aviso se manda una sola vez, y si algo falla
 * (sin configurar, sin plantilla, error de Meta) la compra o el recordatorio normal NUNCA se ven afectados.
 */
class WhatsAppService
{
    public const EVENTS = [
        'purchase' => ['label' => 'Compra confirmada', 'setting' => 'wa_template_purchase', 'default' => 'horizontia_compra'],
        'live' => ['label' => 'Sesión en vivo próxima', 'setting' => 'wa_template_live', 'default' => 'horizontia_en_vivo'],
        'onsite' => ['label' => 'Curso presencial mañana', 'setting' => 'wa_template_onsite', 'default' => 'horizontia_presencial'],
    ];

    public static function enabled(): bool
    {
        return setting('_integrations.wa_auto_enabled') == '1' && self::token() !== '' && self::phoneNumberId() !== '';
    }

    public static function token(): string
    {
        return trim((string) setting('_integrations.wa_access_token'));
    }

    public static function phoneNumberId(): string
    {
        return preg_replace('/\D/', '', (string) setting('_integrations.wa_phone_number_id'));
    }

    public static function language(): string
    {
        $lang = (string) setting('_integrations.wa_language');

        return preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $lang) ? $lang : 'es_MX';
    }

    public static function template(string $event): string
    {
        $name = trim((string) setting('_integrations.' . self::EVENTS[$event]['setting']));

        return preg_match('/^[a-z0-9_]{1,80}$/', $name) ? $name : self::EVENTS[$event]['default'];
    }

    /** Solo dígitos con código de país (México por defecto si escriben 10 dígitos). Null si no parece un teléfono. */
    public static function normalizePhone(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw);
        $digits = preg_replace('/^00/', '', $digits);
        if (strlen($digits) === 10) {
            $digits = config('academy.whatsapp_country_code', '52') . $digits;
        }

        return preg_match('/^[1-9]\d{9,14}$/', $digits) ? $digits : null;
    }

    /** Guarda el número y el consentimiento (casilla del pago). */
    public function optIn(User $user, ?string $phone): bool
    {
        $digits = self::normalizePhone($phone);
        if (!$digits) {
            return false;
        }
        $user->forceFill(['whatsapp_phone' => $digits, 'whatsapp_optin_at' => now()])->save();

        return true;
    }

    public function optOut(User $user): void
    {
        $user->forceFill(['whatsapp_optin_at' => null])->save();
    }

    private function reachable(User $user): bool
    {
        return $user->whatsapp_optin_at !== null && self::normalizePhone($user->whatsapp_phone) !== null;
    }

    /**
     * Pone en cola un aviso. @return bool true si se puso en cola.
     *
     * @param array<int, string> $params valores {{1}}, {{2}}… de la plantilla, en orden
     */
    public function send(User $user, string $event, string $subject, array $params): bool
    {
        if (!self::enabled() || !$this->reachable($user) || !isset(self::EVENTS[$event])) {
            return false;
        }
        $row = WhatsappMessage::firstOrCreate(['user_id' => $user->id, 'event' => $event, 'subject' => $subject], ['template' => self::template($event), 'to_tail' => substr((string) $user->whatsapp_phone, -4), 'status' => 'queued']);
        if (!$row->wasRecentlyCreated) {
            return false;   // ya se envió (o se intentó): no se repite
        }
        $params = array_map(fn ($p) => Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $p))), 200, '…'), $params);
        SendWhatsAppMessage::dispatch($row->id, $user->whatsapp_phone, $row->template, self::language(), $params);

        return true;
    }

    // ------------------------------------------------------------ eventos

    public function purchaseConfirmed(Order $order): void
    {
        try {
            $user = User::with('profile')->find($order->user_id);
            if (!$user) {
                return;
            }
            $titles = $order->items()->where('orderable_type', Course::class)->pluck('title')->map(fn ($t) => html_entity_decode((string) $t))->implode(', ');
            if ($titles === '') {
                return;
            }
            $this->send($user, 'purchase', 'order:' . $order->id, [$user->profile?->first_name ?: 'estudiante', $titles, route('courses.course-list')]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** A cada inscrito con consentimiento: «tu sesión en vivo empieza pronto». */
    public function liveReminder(CourseLiveStream $stream): int
    {
        $n = 0;
        try {
            $lesson = $stream->curriculum;
            $when = $stream->date_time->copy()->timezone(config('academy.timezone'))->locale('es')->translatedFormat('l j \d\e F, H:i');
            $url = route('courses.course-taking', ['slug' => $stream->course->slug] + ($lesson ? ['leccion' => $lesson->id] : []));
            foreach (Enrollment::where('course_id', $stream->course_id)->where('status', Enrollment::STATUSES['active'])->with('student.user.profile')->get() as $enrollment) {
                $user = $enrollment->student?->user;
                if ($user && $this->send($user, 'live', 'stream:' . $stream->id, [$user->profile?->first_name ?: 'estudiante', $stream->title, $when, $url])) {
                    $n++;
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $n;
    }

    public function onsiteReminder(User $user, Course $course, CourseSession $session): bool
    {
        try {
            return $this->send($user, 'onsite', 'session:' . $session->id, [$user->profile?->first_name ?: 'estudiante', html_entity_decode((string) $course->title), $session->whenLabel(), $session->venueLabel()]);
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }
}
