<?php

namespace App\Support;

/**
 * Which panel screens refresh themselves in the background, and how often (seconds).
 * Only read-mostly screens (dashboards and listings) are here: refreshing a form while someone types would be hostile.
 */
class PanelLive
{
    public const SCREENS = [
        // admin
        'admin.insights' => 30,
        'admin.users' => 45,
        'admin.leads' => 30,
        'admin.invoices' => 45,
        'admin.bookings' => 45,
        'admin.withdraw-requests' => 45,
        'admin.disputes' => 45,
        'admin.identity-verification' => 45,
        'admin.blog-listing' => 60,
        'courses.admin.courses' => 45,
        'courses.admin.course-enrollments' => 30,
        // instructor
        'tutor.dashboard' => 30,
        'courses.tutor.courses' => 45,
        'courses.tutor.assignments' => 45,
        'courses.tutor.manage-live-streams' => 45,
        'tutor.invoices' => 60,
        'tutor.payouts' => 60,
        // student
        'courses.course-list' => 60,
        'student.invoices' => 60,
        'student.certificate-list' => 90,
    ];

    public static function interval(?string $routeName = null): int
    {
        return self::SCREENS[$routeName ?? \Illuminate\Support\Facades\Route::currentRouteName()] ?? 0;
    }
}
