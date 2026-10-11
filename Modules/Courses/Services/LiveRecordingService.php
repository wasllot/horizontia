<?php

namespace Modules\Courses\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\CourseLiveStream;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Watchtime;
use Modules\Courses\Services\Lessons\VimeoHandler;
use Modules\Courses\Services\Lessons\YoutubeHandler;

/**
 * La grabación de una sesión en vivo queda como lección del curso (para quien no pudo asistir).
 *
 * - Se publica sola cuando la sesión ya terminó y tiene enlace de grabación (lo pega el instructor o llega del webhook de Zoom).
 * - Es una lección sin duración propia: el tiempo del curso ya cuenta la sesión en vivo. Terminar la grabación da por
 *   cumplida la lección en vivo (quien asistió ya la tenía), así nadie se queda sin poder completar el curso.
 */
class LiveRecordingService
{
    public const TITLE_PREFIX = 'Grabación: ';

    /** @return array{type: string, media_path: ?string, article_content: ?string} cómo se muestra el enlace de la grabación */
    public function lessonFor(string $url, ?string $passcode = null): array
    {
        $url = trim($url);
        if ($yt = (new YoutubeHandler())->normalize($url)) {
            return ['type' => 'yt_link', 'media_path' => $yt, 'article_content' => null];
        }
        if ($vm = (new VimeoHandler())->normalize($url)) {
            return ['type' => 'vm_link', 'media_path' => $vm, 'article_content' => null];
        }

        $safe = e($url);
        $html = '<p>La grabación de esta sesión en vivo está disponible aquí:</p>'
            . '<p><a href="' . $safe . '" target="_blank" rel="noopener noreferrer"><strong>Ver la grabación</strong></a></p>'
            . ($passcode ? '<p>Código de acceso: <strong>' . e($passcode) . '</strong></p>' : '');

        return ['type' => 'article', 'media_path' => null, 'article_content' => $html];
    }

    /** ¿Ya se puede publicar? Sesión terminada (por hora o estado), con grabación y sin lección todavía. */
    public function isReady(CourseLiveStream $stream): bool
    {
        return !empty($stream->recording_url)
            && empty($stream->recording_curriculum_id)
            && $stream->status != CourseLiveStream::STATUS_CANCELLED
            && LiveSessionService::phase($stream) === 'ended';
    }

    public function publishIfReady(CourseLiveStream $stream, ?string $passcode = null): ?Curriculum
    {
        return $this->isReady($stream) ? $this->publish($stream, $passcode) : null;
    }

    public function publish(CourseLiveStream $stream, ?string $passcode = null): ?Curriculum
    {
        return DB::transaction(function () use ($stream, $passcode) {
            $stream = CourseLiveStream::lockForUpdate()->find($stream->id);
            if (!$stream || !empty($stream->recording_curriculum_id)) {
                return null;
            }
            $section = $stream->curriculum?->section ?? Course::find($stream->course_id)?->sections()->orderByDesc('sort_order')->first();
            if (!$section) {
                return null;
            }

            $lesson = Curriculum::create(['section_id' => $section->id, 'title' => mb_substr(self::TITLE_PREFIX . $stream->title, 0, 250), 'description' => 'Grabación de la sesión en vivo «' . $stream->title . '».',
                'content_length' => 0, 'sort_order' => (int) Curriculum::where('section_id', $section->id)->max('sort_order') + 1, 'is_preview' => false] + $this->lessonFor($stream->recording_url, $passcode));
            $stream->update(['recording_curriculum_id' => $lesson->id]);

            return $lesson;
        });
    }

    /** Publica las grabaciones pendientes (las sesiones que terminaron después de guardar el enlace). @return int cuántas */
    public function publishPending(): int
    {
        $n = 0;
        CourseLiveStream::whereNotNull('recording_url')->where('recording_url', '!=', '')->whereNull('recording_curriculum_id')
            ->where('status', '!=', CourseLiveStream::STATUS_CANCELLED)->get()
            ->each(function ($stream) use (&$n) {
                if ($this->publishIfReady($stream)) {
                    $n++;
                }
            });

        return $n;
    }

    /** Al terminar la grabación, la lección en vivo de esa sesión cuenta como cumplida. */
    public function creditLiveLesson(User $student, int $recordingCurriculumId): void
    {
        $stream = CourseLiveStream::where('recording_curriculum_id', $recordingCurriculumId)->with('curriculum')->first();
        $live = $stream?->curriculum;
        if (!$live || (int) $live->content_length <= 0) {
            return;
        }
        $row = Watchtime::where('user_id', $student->id)->where('curriculum_id', $live->id)->first();
        if ($row) {
            if ((int) $row->duration < (int) $live->content_length) {
                $row->update(['duration' => $live->content_length]);
            }

            return;
        }
        Watchtime::create(['user_id' => $student->id, 'course_id' => $stream->course_id, 'section_id' => $live->section_id, 'curriculum_id' => $live->id, 'duration' => $live->content_length]);
    }
}
