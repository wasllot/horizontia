<?php

namespace Modules\Courses\Services;

use Modules\Courses\Models\Course;
use Modules\Courses\Services\Lessons\LessonSaver;

/**
 * What a course still needs before it can be published. `required` items block publishing; `recommended` ones only
 * make the course better (shown as tips).
 */
class CourseReadiness
{
    /** @return array<int, array{key:string, label:string, ok:bool, required:bool, hint:string, tab:string}> */
    public function checks(Course $course): array
    {
        $course->loadMissing(['thumbnail', 'promotionalVideo', 'pricing', 'faqs', 'sections.curriculums.liveStream']);
        $saver = new LessonSaver();
        $lessons = $course->sections->flatMap->curriculums;
        $incomplete = $lessons->filter(fn ($l) => !$saver->isComplete($l));
        $objectives = array_filter((array) $course->learning_objectives, fn ($o) => trim((string) $o) !== '');

        $checks = [
            ['key' => 'basics', 'label' => 'Título, descripción, categoría, nivel e idioma', 'ok' => filled($course->title) && filled(trim(strip_tags((string) $course->description))) && $course->category_id && $course->sub_category_id && $course->level && $course->language_id, 'required' => true, 'hint' => 'Completa la información básica del curso.', 'tab' => 'details'],
            ['key' => 'thumbnail', 'label' => 'Imagen de portada', 'ok' => (bool) $course->thumbnail?->path, 'required' => true, 'hint' => 'Es lo primero que ven los estudiantes en el catálogo.', 'tab' => 'media'],
        ];
        if (isPaidSystem()) {
            $checks[] = ['key' => 'pricing', 'label' => 'Precio definido (o curso gratuito)', 'ok' => $course->pricing !== null, 'required' => true, 'hint' => 'Elige si es gratis o fija el precio.', 'tab' => 'pricing'];
        }
        if ($course->isOnsite()) {
            $dates = $course->sessions()->upcoming()->get();
            $incompleteDates = $dates->filter(fn ($d) => blank($d->venue_name) || blank($d->address) || blank($d->city));
            $checks[] = ['key' => 'sessions', 'label' => $dates->isEmpty() ? 'Al menos una fecha futura con sede' : $dates->count() . ' ' . ($dates->count() === 1 ? 'fecha disponible' : 'fechas disponibles'), 'ok' => $dates->isNotEmpty() && $incompleteDates->isEmpty(), 'required' => true, 'hint' => 'Añade una fecha con su sede (dirección y ciudad) para poder vender el curso.', 'tab' => 'sessions'];
        }
        // A ticket-like course (in-person or hybrid) can be just a purchase: online lessons are optional there.
        $needsLessons = !$course->isOnsite();
        $checks[] = ['key' => 'lessons', 'label' => 'Al menos una sección con una lección', 'ok' => $lessons->isNotEmpty(), 'required' => $needsLessons, 'hint' => 'Crea tu primera sección y lección.', 'tab' => 'content'];
        $checks[] = ['key' => 'complete', 'label' => $incomplete->isEmpty() ? 'Todas las lecciones tienen contenido' : $incomplete->count() . ' ' . ($incomplete->count() === 1 ? 'lección sin contenido' : 'lecciones sin contenido') . ': ' . $incomplete->take(3)->pluck('title')->implode(', '), 'ok' => $incomplete->isEmpty() && ($lessons->isNotEmpty() || !$needsLessons), 'required' => $needsLessons || $lessons->isNotEmpty(), 'hint' => 'Completa o elimina las lecciones vacías.', 'tab' => 'content'];

        $checks[] = ['key' => 'promo', 'label' => 'Video promocional', 'ok' => (bool) $course->promotionalVideo?->path, 'required' => false, 'hint' => 'Los cursos con video de presentación convierten mejor.', 'tab' => 'media'];
        $checks[] = ['key' => 'objectives', 'label' => 'Objetivos de aprendizaje', 'ok' => count($objectives) >= 3, 'required' => false, 'hint' => 'Indica al menos 3 cosas que aprenderá el estudiante.', 'tab' => 'details'];
        $checks[] = ['key' => 'preview', 'label' => 'Una lección de vista previa gratuita', 'ok' => $lessons->contains('is_preview', true), 'required' => false, 'hint' => 'Deja que prueben el curso antes de comprar.', 'tab' => 'content'];
        $checks[] = ['key' => 'faqs', 'label' => 'Preguntas frecuentes', 'ok' => $course->faqs->isNotEmpty(), 'required' => false, 'hint' => 'Resuelve las dudas habituales antes de que pregunten.', 'tab' => 'faqs'];

        return $checks;
    }

    public function ready(Course $course): bool
    {
        foreach ($this->checks($course) as $c) {
            if ($c['required'] && !$c['ok']) {
                return false;
            }
        }

        return true;
    }
}
