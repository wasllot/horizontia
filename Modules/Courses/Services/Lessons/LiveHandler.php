<?php

namespace Modules\Courses\Services\Lessons;

use Carbon\Carbon;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Services\LiveSessionService;

/**
 * A live lesson owns one scheduled session (CourseLiveStream linked by curriculum_id). Creating it announces the session
 * to the enrolled students; moving it to another day announces it again; the reminder command does the rest.
 */
class LiveHandler implements LessonHandler
{
    public function __construct(private ?LiveSessionService $sessions = null)
    {
        $this->sessions ??= new LiveSessionService();
    }

    public function type(): string { return 'live'; }

    public function rules(array $ctx = []): array
    {
        $dateRules = ['required', 'date'];
        // A session must be scheduled in the future, but re-saving an already-held one (to add the recording) must not fail.
        if (empty($ctx['existing_date']) || $ctx['existing_date'] === null) {
            $dateRules[] = 'after:now';
        }

        return [
            'live' => ['required', 'array'],
            'live.date_time' => $dateRules,
            'live.duration_minutes' => ['required', 'integer', 'min:5', 'max:720'],
            'live.meeting_link' => ['required', 'url:https', 'max:500'],
            'live.notify_hours_before' => ['nullable', 'integer', 'min:0', 'max:168'],
            'live.description' => ['nullable', 'string', 'max:2000'],
            'live.recording_url' => ['nullable', 'url:https', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'live.date_time.required' => 'Indica la fecha y hora de la sesión.',
            'live.date_time.after' => 'La sesión debe programarse en el futuro.',
            'live.duration_minutes.required' => 'Indica la duración en minutos.',
            'live.duration_minutes.min' => 'La duración mínima es de 5 minutos.',
            'live.meeting_link.required' => 'Pega el enlace de la reunión (Meet, Zoom, Teams…).',
            'live.meeting_link.url' => 'El enlace debe empezar por https://',
        ];
    }

    public function apply(Curriculum $lesson, array $data): array
    {
        $live = $data['live'];
        $payload = [
            'title' => $lesson->title,
            'description' => $live['description'] ?? null,
            'meeting_link' => $live['meeting_link'],
            'date_time' => $live['date_time'],
            'duration_minutes' => (int) $live['duration_minutes'],
            'notify_hours_before' => (int) ($live['notify_hours_before'] ?? 24),
            'recording_url' => $live['recording_url'] ?? null,
        ];

        $stream = $lesson->liveStream;
        if ($stream) {
            $moved = Carbon::parse($payload['date_time'])->ne($stream->date_time);
            $stream = $this->sessions->update($stream, $payload + ['status' => \Modules\Courses\Models\CourseLiveStream::STATUS_SCHEDULED]);
            app(\Modules\Courses\Services\LiveRecordingService::class)->publishIfReady($stream);   // sesión terminada + grabación = lección
            if ($moved && $stream->date_time->isFuture()) {
                $this->sessions->announce($stream);
            }
        } else {
            $course = $lesson->section->course;
            $this->sessions->create($course, $payload, $lesson);
        }

        return ['media_path' => null, 'article_content' => null, 'content_length' => (int) $live['duration_minutes'] * 60];
    }

    public function isComplete(Curriculum $lesson): bool
    {
        return $lesson->liveStream !== null && !empty($lesson->liveStream->meeting_link);
    }
}
