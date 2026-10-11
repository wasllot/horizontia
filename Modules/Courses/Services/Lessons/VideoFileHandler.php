<?php

namespace Modules\Courses\Services\Lessons;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Courses\Models\Curriculum;

/** A video uploaded to the private disk (R2/S3 or local) and played through short-lived signed URLs. */
class VideoFileHandler implements LessonHandler
{
    public function type(): string
    {
        return 'video';
    }

    public static function allowedExtensions(): array
    {
        $ext = setting('_general.allowed_video_extensions') ?: 'mp4';

        return array_values(array_filter(array_map(fn ($e) => strtolower(trim($e)), explode(',', $ext))));
    }

    public static function maxMegabytes(): int
    {
        return (int) (setting('_general.max_video_size') ?: 20);
    }

    public function rules(array $ctx = []): array
    {
        $required = empty($ctx['has_content']) ? 'required' : 'nullable';

        return [
            'files.video' => [$required, 'file', 'mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm,video/x-ms-wmv,video/x-flv,video/mpeg,video/ogg', 'mimes:' . implode(',', self::allowedExtensions()), 'max:' . (self::maxMegabytes() * 1024)],
            'duration' => ['nullable', 'integer', 'min:0', 'max:86400'],
        ];
    }

    public function messages(): array
    {
        return [
            'files.video.required' => 'Selecciona el archivo de video.',
            'files.video.mimes' => 'Formato no permitido. Usa: ' . implode(', ', self::allowedExtensions()) . '.',
            'files.video.mimetypes' => 'El archivo no parece un video válido.',
            'files.video.max' => 'El video supera el máximo de ' . self::maxMegabytes() . ' MB.',
        ];
    }

    public function apply(Curriculum $lesson, array $data): array
    {
        /** @var UploadedFile|null $file */
        $file = $data['files']['video'] ?? null;
        $attrs = ['article_content' => null];

        if ($file) {
            $this->discard($lesson);
            $name = Str::uuid()->toString() . '.' . strtolower($file->getClientOriginalExtension() ?: 'mp4');
            $attrs['media_path'] = $file->storeAs('curriculum_videos', $name, getPrivateDisk());
        }
        if (isset($data['duration'])) {
            $attrs['content_length'] = (int) $data['duration'];
        }

        return $attrs;
    }

    /** Remove the previous file when the lesson stops using it. */
    public function discard(Curriculum $lesson): void
    {
        if ($lesson->type === 'video' && $lesson->media_path && !str_starts_with($lesson->media_path, 'http')) {
            Storage::disk(getPrivateDisk())->delete($lesson->media_path);
        }
    }

    public function isComplete(Curriculum $lesson): bool
    {
        return !empty($lesson->media_path) && lessonMediaExists($lesson->media_path);
    }
}
