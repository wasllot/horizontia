<?php

namespace Modules\Courses\Services;

use App\Models\User;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Enrollment;

/**
 * Single place that answers "may this user see the paid content of this course?".
 */
class CourseAccessService
{
    public function canAccessCourse(?User $user, Course $course): bool
    {
        if (!$user) {
            return false;
        }

        if (in_array($user->role, ['admin', 'sub_admin'], true) || (int) $course->instructor_id === (int) $user->id) {
            return true;
        }

        // Raw column value 1 = active (see Enrollment::STATUSES); refunded/revoked rows must not grant access.
        return Enrollment::where('course_id', $course->id)
            ->where('student_id', $user->id)
            ->where('status', Enrollment::STATUSES['active'])
            ->exists();
    }

    public function canViewCurriculum(?User $user, Curriculum $curriculum): bool
    {
        if ($curriculum->is_preview) {
            return true;
        }

        $course = $curriculum->section?->course;

        return $course && $this->canAccessCourse($user, $course);
    }
}
