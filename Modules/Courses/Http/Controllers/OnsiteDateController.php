<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\CourseSession;
use Modules\Courses\Models\Enrollment;

class OnsiteDateController extends Controller
{
    /** Calendar file of an in-person date: only for who bought that date, the course's instructor and admins. */
    public function ics(Request $request, CourseSession $session)
    {
        $session->loadMissing('course');
        $user = $request->user();
        $course = $session->course;
        abort_unless($course, 404);

        $mine = Enrollment::where('student_id', $user->id)->where('course_id', $course->id)->where('session_id', $session->id)
            ->where('status', Enrollment::STATUSES['active'])->exists();
        abort_unless($mine || $user->hasRole('admin') || $course->instructor_id === $user->id, 403);
        abort_if($session->isCancelled(), 404);

        return response($session->ics($course), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="curso-presencial-' . $session->id . '.ics"',
        ]);
    }
}
