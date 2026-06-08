<?php

namespace Modules\Courses\Livewire\Traits;

use Illuminate\Support\Facades\Auth;

/**
 * Provides helpers so course-creation Livewire components work for both
 * tutors and admins/sub-admins without duplicating route logic.
 */
trait AdminAwareCourseRouting
{
    /**
     * Whether the currently authenticated user is an admin or sub-admin.
     */
    protected function isAdmin(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        // Support both Spatie roles and a simple `role` column
        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole(['admin', 'sub_admin']);
        }

        return in_array($user->role ?? '', ['admin', 'sub_admin']);
    }

    /**
     * Return the named route for the course edit page,
     * using the admin route for admins and the tutor route for tutors.
     */
    protected function editCourseRoute(string $tab, int $id): string
    {
        $routeName = $this->isAdmin()
            ? 'courses.admin.edit-course'
            : 'courses.tutor.edit-course';

        return route($routeName, ['tab' => $tab, 'id' => $id]);
    }

    /**
     * Return the named route for the course listing page.
     */
    protected function courseListingRoute(): string
    {
        return $this->isAdmin()
            ? route('courses.admin.courses')
            : route('courses.tutor.courses');
    }

    /**
     * Returns the instructor ID to use when querying/creating a course.
     * Admins pass null so they can access any course; tutors use their own ID.
     */
    protected function instructorId(): ?int
    {
        return $this->isAdmin() ? null : Auth::id();
    }
}
