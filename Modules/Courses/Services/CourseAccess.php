<?php

namespace Modules\Courses\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Section;

/**
 * Single place that decides who may edit a course and everything inside it. Livewire public properties and
 * request ids are client-controlled, so every wizard action resolves the record through here instead of trusting an id:
 * admins/sub-admins manage any course, an instructor only their own.
 */
class CourseAccess
{
    public static function isAdmin(?User $user = null): bool
    {
        $user ??= Auth::user();

        return $user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin', 'sub_admin']);
    }

    public static function canManage(Course $course, ?User $user = null): bool
    {
        $user ??= Auth::user();
        if (!$user) {
            return false;
        }

        return self::isAdmin($user) || (int) $course->instructor_id === (int) $user->id;
    }

    /** @throws \Symfony\Component\HttpKernel\Exception\HttpException 404 when missing, 403 when not allowed */
    public static function course(int|string|null $id): Course
    {
        $course = Course::find((int) $id);
        abort_if(!$course, 404);
        abort_unless(self::canManage($course), 403, __('No tienes permiso para editar este curso.'));

        return $course;
    }

    public static function section(int|string|null $id): Section
    {
        $section = Section::find((int) $id);
        abort_if(!$section, 404);
        self::course($section->course_id);

        return $section;
    }

    public static function curriculum(int|string|null $id): Curriculum
    {
        $lesson = Curriculum::find((int) $id);
        abort_if(!$lesson, 404);
        self::section($lesson->section_id);

        return $lesson;
    }
}
