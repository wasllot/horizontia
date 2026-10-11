<?php

namespace App\Services;

use App\Mail\ChatMessageEmail;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Enrollment;

/**
 * Mensajes entre personas de la academia. Reglas: el alumno escribe a los instructores de sus cursos y a soporte,
 * el instructor a los alumnos de sus cursos y a soporte, y el administrador (soporte) a cualquiera.
 */
class ChatService
{
    public const MAX_BODY = 2000;
    public const MAX_FILE_KB = 5120;
    public const PER_MINUTE = 20;
    public const MIMES = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];

    /** Disco de los adjuntos: nunca el público (se sirven con permiso). */
    public static function disk(): string
    {
        return config('filesystems.private_disk') ?: 'local';
    }

    public static function enabled(): bool
    {
        return (bool) config('academy.chat', true);
    }

    private function isStaff(User $u): bool
    {
        return in_array($u->role, ['admin', 'sub_admin'], true);
    }

    public function canMessage(User $a, User $b): bool
    {
        if ($a->id === $b->id || !self::enabled()) {
            return false;
        }
        if ($this->isStaff($a) || $this->isStaff($b)) {
            return true;
        }
        [$student, $tutor] = $a->role === 'student' ? [$a, $b] : [$b, $a];
        if ($student->role !== 'student' || $tutor->role !== 'tutor') {
            return false;
        }

        return Enrollment::where('student_id', $student->id)->where('status', Enrollment::STATUSES['active'])
            ->whereIn('course_id', Course::where('instructor_id', $tutor->id)->select('id'))->exists();
    }

    /** Personas con las que puede empezar una conversación. @return Collection<int, User> */
    public function contacts(User $me, string $search = '', int $limit = 30): Collection
    {
        $q = User::query()->where('id', '!=', $me->id)->with('profile:id,user_id,first_name,last_name,image');
        if (!$this->isStaff($me)) {
            $staff = User::whereHas('roles', fn ($r) => $r->whereIn('name', ['admin', 'sub_admin']))->pluck('id');
            if ($me->role === 'student') {
                $mine = Course::whereIn('id', Enrollment::where('student_id', $me->id)->where('status', Enrollment::STATUSES['active'])->select('course_id'))->pluck('instructor_id');
            } else {
                $mine = Enrollment::whereIn('course_id', Course::where('instructor_id', $me->id)->select('id'))->where('status', Enrollment::STATUSES['active'])->pluck('student_id');
            }
            $q->whereIn('id', $staff->merge($mine)->unique());
        }
        $term = trim($search);
        if ($term !== '') {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';
            $q->where(fn ($w) => $w->where('email', 'like', $like)->orWhereHas('profile', fn ($p) => $p->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)));
        }

        return $q->limit($limit)->get();
    }

    public static function displayName(?User $u): string
    {
        $p = $u?->profile;
        $name = trim(($p?->first_name ?? '') . ' ' . ($p?->last_name ?? ''));

        return $name !== '' ? $name : ($u?->email ? Str::before($u->email, '@') : 'Usuario');
    }

    public static function roleLabel(?User $u): string
    {
        return match ($u?->role) {
            'admin', 'sub_admin' => 'Soporte',
            'tutor' => 'Instructor',
            'student' => 'Estudiante',
            default => '',
        };
    }

    public function conversationWith(User $me, User $other): ChatConversation
    {
        abort_unless($this->canMessage($me, $other), 403);
        [$low, $high] = [min($me->id, $other->id), max($me->id, $other->id)];

        return ChatConversation::firstOrCreate(['user_low' => $low, 'user_high' => $high]);
    }

    /** Conversaciones con mensajes, la más reciente primero. */
    public function conversations(User $me): Collection
    {
        return ChatConversation::query()->where(fn ($q) => $q->where('user_low', $me->id)->orWhere('user_high', $me->id))->whereNotNull('last_message_at')->orderByDesc('last_message_at')->get();
    }

    /** @return array<int, int> conversation_id => mensajes sin leer */
    public function unreadByConversation(User $me): array
    {
        return ChatMessage::query()->whereIn('conversation_id', ChatConversation::where(fn ($q) => $q->where('user_low', $me->id)->orWhere('user_high', $me->id))->select('id'))
            ->where('sender_id', '!=', $me->id)->whereNull('read_at')->groupBy('conversation_id')->selectRaw('conversation_id, COUNT(*) n')->pluck('n', 'conversation_id')->map(fn ($n) => (int) $n)->all();
    }

    public function unreadCount(User $me): int
    {
        return array_sum($this->unreadByConversation($me));
    }

    public function markRead(ChatConversation $c, User $me): void
    {
        ChatMessage::where('conversation_id', $c->id)->where('sender_id', '!=', $me->id)->whereNull('read_at')->update(['read_at' => now()]);
    }

    /**
     * @return array{ok: bool, error?: string, message?: ChatMessage}
     */
    public function send(User $me, ChatConversation $c, ?string $body, ?UploadedFile $file = null): array
    {
        abort_unless($c->includes($me), 403);
        $other = User::find($c->otherId($me));
        abort_unless($other && $this->canMessage($me, $other), 403);

        $body = self::clean((string) $body);
        if ($body === '' && !$file) {
            return ['ok' => false, 'error' => 'Escribe un mensaje o adjunta un archivo.'];
        }
        if (RateLimiter::tooManyAttempts('chat:' . $me->id, self::PER_MINUTE)) {
            return ['ok' => false, 'error' => 'Vas muy rápido. Espera unos segundos.'];
        }
        RateLimiter::hit('chat:' . $me->id, 60);

        $data = ['conversation_id' => $c->id, 'sender_id' => $me->id, 'body' => $body !== '' ? $body : null];
        if ($file) {
            $disk = self::disk();
            $name = Str::random(32) . '.' . strtolower($file->getClientOriginalExtension() ?: $file->extension());
            $data += [
                'file_path' => Storage::disk($disk)->putFileAs('chat/' . $c->id, $file, $name), 'file_name' => mb_substr(preg_replace('/[^\w .\-()áéíóúñÁÉÍÓÚÑ]/u', '_', $file->getClientOriginalName()), 0, 160),
                'file_mime' => $file->getMimeType(), 'file_size' => $file->getSize(),
            ];
        }
        $message = DB::transaction(function () use ($data, $c, $me, $body, $file) {
            $m = ChatMessage::create($data);
            $c->update(['last_message_at' => now(), 'last_sender_id' => $me->id, 'last_preview' => Str::limit($body !== '' ? $body : '📎 ' . ($file ? $file->getClientOriginalName() : 'Archivo'), 120, '…')]);

            return $m;
        });

        // Aviso por correo, como máximo uno por hora y conversación (para no saturar si están chateando).
        if ($other->email && Cache::add('chat_mail:' . $c->id . ':' . $other->id, 1, now()->addHour())) {
            try {
                Mail::to($other->email)->queue(new ChatMessageEmail($me, $other, $message));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return ['ok' => true, 'message' => $message];
    }

    public function deleteOwn(User $me, ChatMessage $m): void
    {
        abort_unless((int) $m->sender_id === (int) $me->id, 403);
        if ($m->file_path) {
            Storage::disk(self::disk())->delete($m->file_path);
        }
        $m->delete();
        $c = $m->conversation;
        $last = ChatMessage::where('conversation_id', $c->id)->latest('id')->first();
        $c->update($last ? ['last_preview' => Str::limit($last->body ?: '📎 ' . $last->file_name, 120, '…'), 'last_sender_id' => $last->sender_id, 'last_message_at' => $last->created_at] : ['last_preview' => null, 'last_message_at' => null]);
    }

    public static function clean(string $text): string
    {
        return mb_substr(trim(preg_replace("/\n{3,}/", "\n\n", str_replace("\r", '', strip_tags($text)))), 0, self::MAX_BODY);
    }

    /** Texto seguro para mostrar: escapado, con saltos de línea y enlaces http(s) clicables. */
    public static function render(string $body): string
    {
        $html = nl2br(e($body));

        return preg_replace_callback('~(https?://[^\s<]+[^\s<.,;:!?)\]])~i', fn ($m) => '<a href="' . $m[1] . '" target="_blank" rel="noopener noreferrer nofollow">' . $m[1] . '</a>', $html) ?? $html;
    }
}
