<?php

namespace Modules\Courses\Services\Lessons;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Services\ScormService;

class ScormHandler implements LessonHandler
{
    public function type(): string { return 'scorm'; }

    public function rules(array $ctx = []): array
    {
        $required = empty($ctx['has_content']) ? 'required' : 'nullable';

        return ['files.scorm' => [$required, 'file', 'mimes:zip', 'max:' . (int) (setting('_general.max_scorm_size') ?: 512) * 1024]];
    }

    public function messages(): array
    {
        return [
            'files.scorm.required' => 'Selecciona el paquete SCORM (.zip).',
            'files.scorm.mimes' => 'El paquete debe ser un archivo .zip.',
            'files.scorm.max' => 'El paquete supera el tamaño máximo permitido.',
        ];
    }

    public function apply(Curriculum $lesson, array $data): array
    {
        /** @var UploadedFile|null $file */
        $file = $data['files']['scorm'] ?? null;
        if (!$file) {
            return [];   // re-saving a lesson that already has its package
        }

        $path = $file->storeAs('scorm_temp', \Illuminate\Support\Str::uuid() . '.zip', getPrivateDisk());
        $entry = (new ScormService())->processScormZip($path, $lesson->id);
        if (!$entry) {
            throw ValidationException::withMessages(['content.scorm' => 'Paquete SCORM no válido: no se encontró el punto de entrada (imsmanifest.xml o index.html) o contiene archivos no permitidos.']);
        }

        return ['media_path' => $entry, 'article_content' => null, 'content_length' => (int) ($data['duration'] ?? 0)];
    }

    public function isComplete(Curriculum $lesson): bool
    {
        return !empty($lesson->media_path);
    }
}
