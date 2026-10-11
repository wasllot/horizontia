<?php

namespace Modules\Courses\Services\Lessons;

/**
 * The kinds of lesson an instructor can author. A "group" is what the builder shows; several stored `type` values can
 * belong to one group (a video lesson is a file upload or a YouTube / Vimeo / Genially link).
 */
final class LessonType
{
    /** group => [label, description, icon, stored types] */
    public const GROUPS = [
        'video'   => ['label' => 'Video',     'hint' => 'Sube un archivo o enlaza YouTube, Vimeo o Genially', 'icon' => 'play',     'types' => ['video', 'yt_link', 'vm_link', 'genially_link']],
        'article' => ['label' => 'Artículo',  'hint' => 'Texto con formato, imágenes y tablas',              'icon' => 'doc',      'types' => ['article']],
        'quiz'    => ['label' => 'Test',      'hint' => 'Preguntas con calificación automática',             'icon' => 'check',    'types' => ['assignment']],
        'live'    => ['label' => 'En vivo',   'hint' => 'Sesión programada con enlace y recordatorios',      'icon' => 'live',     'types' => ['live']],
        'scorm'   => ['label' => 'SCORM',     'hint' => 'Paquete interactivo SCORM 1.2 / 2004 (.zip)',        'icon' => 'package',  'types' => ['scorm']],
    ];

    public const VIDEO_SOURCES = [
        'video'         => 'Subir archivo',
        'yt_link'       => 'YouTube',
        'vm_link'       => 'Vimeo',
        'genially_link' => 'Genially',
    ];

    public static function groupOf(?string $storedType): string
    {
        foreach (self::GROUPS as $group => $def) {
            if (in_array($storedType, $def['types'], true)) {
                return $group;
            }
        }

        return 'video';
    }

    /** First stored type of a group (what a brand-new lesson of that group is saved as). */
    public static function defaultType(string $group): string
    {
        return self::GROUPS[$group]['types'][0] ?? 'video';
    }

    public static function label(?string $storedType): string
    {
        $group = self::groupOf($storedType);

        return $group === 'video' && $storedType !== 'video' && isset(self::VIDEO_SOURCES[$storedType])
            ? 'Video · ' . self::VIDEO_SOURCES[$storedType]
            : self::GROUPS[$group]['label'];
    }

    public static function storedTypes(): array
    {
        return array_merge(...array_column(self::GROUPS, 'types'));
    }
}
