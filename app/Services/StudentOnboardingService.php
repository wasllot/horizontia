<?php

namespace App\Services;

use App\Models\CourseCertificate;
use App\Models\User;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Models\Watchtime;

/**
 * «Primeros pasos» del estudiante: perfil, primer curso, primera lección y primer certificado.
 * Se calcula con datos reales (nada que marcar a mano) y se oculta al terminar o cuando la persona lo cierra.
 */
class StudentOnboardingService
{
    /**
     * @return array{steps: array<int, array{key:string,title:string,hint:string,done:bool,url:string,cta:string}>, done:int, total:int, next:?string}|null
     *         null cuando no hay nada que mostrar (todo hecho, cerrado o no es estudiante)
     */
    public function forStudent(User $user): ?array
    {
        if ($user->onboarding_dismissed_at || !$user->hasRole('student')) {
            return null;
        }

        $enrolled = Enrollment::where('student_id', $user->id)->where('status', Enrollment::STATUSES['active'])->orderBy('id')->get(['course_id']);
        $firstCourse = $enrolled->isNotEmpty() ? Course::find($enrolled->first()->course_id) : null;
        $started = Watchtime::where('user_id', $user->id)->where('duration', '>', 0)->exists();
        $certified = CourseCertificate::where('user_id', $user->id)->whereNull('revoked_at')->exists();
        $hasPhoto = !empty($user->profile?->image);

        $steps = [
            ['key' => 'profile', 'title' => 'Completa tu perfil', 'hint' => 'Agrega tu foto para que tu instructor y tu comunidad te reconozcan.', 'done' => $hasPhoto,
                'url' => route('student.profile.personal-details'), 'cta' => 'Ir a mi perfil'],
            ['key' => 'course', 'title' => 'Elige tu primer curso', 'hint' => 'Explora el catálogo y prueba las lecciones de vista previa.', 'done' => $enrolled->isNotEmpty(),
                'url' => route('courses.search-courses'), 'cta' => 'Ver cursos'],
            ['key' => 'lesson', 'title' => 'Empieza tu primera lección', 'hint' => 'Con unos minutos al día avanzas más de lo que crees.', 'done' => $started,
                'url' => $firstCourse ? route('courses.course-taking', ['slug' => $firstCourse->slug]) : route('courses.search-courses'), 'cta' => $firstCourse ? 'Ir a mi curso' : 'Ver cursos'],
        ];
        if (config('academy.certificates')) {
            $steps[] = ['key' => 'certificate', 'title' => 'Termina un curso y obtén tu certificado', 'hint' => 'Tu certificado tiene un código que cualquiera puede verificar.', 'done' => $certified,
                'url' => $firstCourse ? route('courses.course-taking', ['slug' => $firstCourse->slug]) : route('courses.search-courses'), 'cta' => 'Seguir aprendiendo'];
        }

        $done = count(array_filter($steps, fn ($s) => $s['done']));
        if ($done === count($steps)) {
            return null;
        }

        return ['steps' => $steps, 'done' => $done, 'total' => count($steps), 'next' => collect($steps)->first(fn ($s) => !$s['done'])['key']];
    }

    public function dismiss(User $user): void
    {
        $user->forceFill(['onboarding_dismissed_at' => now()])->save();
    }
}
