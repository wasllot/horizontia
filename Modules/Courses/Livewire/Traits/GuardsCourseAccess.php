<?php

namespace Modules\Courses\Livewire\Traits;

use Modules\Courses\Models\Course;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Section;
use Modules\Courses\Services\CourseAccess;

/** Resolve course / section / lesson through {@see CourseAccess} inside Livewire actions (never trust a posted id). */
trait GuardsCourseAccess
{
    protected function guardedCourse(int|string|null $id): Course
    {
        return CourseAccess::course($id);
    }

    protected function guardedSection(int|string|null $id): Section
    {
        return CourseAccess::section($id);
    }

    protected function guardedLesson(int|string|null $id): Curriculum
    {
        return CourseAccess::curriculum($id);
    }
}
