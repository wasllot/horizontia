<?php

namespace Modules\Courses\Services;

use App\Mail\OnsiteDateEmail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\CourseSession;
use Modules\Courses\Models\Enrollment;

/** E-mails and panel notices about in-person dates. Failures are logged, never allowed to break a purchase or an admin action. */
class OnsiteNotifier
{
    /** A student's payment was confirmed: send the confirmation and, if the date went over capacity, alert the admins. */
    public static function enrolled($order, Course $course, CourseSession $session, bool $oversold = false): void
    {
        $student = User::find($order->user_id);
        if ($student) {
            self::mail($student, $course, $session, 'confirmation');
        }

        if ($oversold) {
            $msg = 'La fecha «' . $session->whenLabel() . ' · ' . $session->city . '» de «' . html_entity_decode((string) $course->title) . '» superó su cupo de ' . $session->capacity . ' lugares (pagos simultáneos). Revisa los inscritos y mueve a alguien a otra fecha o aumenta el cupo.';
            Log::warning('Sobrecupo en fecha presencial', ['session_id' => $session->id, 'course_id' => $course->id]);
            self::alertAdmins('Sobrecupo en un curso presencial', $msg, route('courses.admin.course-enrollees', ['course' => $course->id]));
        }
    }

    public static function mail(User $student, Course $course, CourseSession $session, string $kind, ?string $note = null): bool
    {
        if (!$student->email) {
            return false;
        }
        try {
            Mail::to($student->email)->queue(new OnsiteDateEmail($course, $session, $student->profile?->first_name ?: 'estudiante', $kind, $note));

            return true;
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar el correo de fecha presencial: ' . $e->getMessage(), ['session_id' => $session->id, 'user_id' => $student->id]);

            return false;
        }
    }

    /** Notice for the admin role (the bell in the panel). */
    public static function alertAdmins(string $subject, string $content, ?string $url = null): void
    {
        try {
            foreach (User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->get() as $admin) {
                $admin->notify(new \App\Notifications\DbNotification([
                    'subject' => $subject, 'content' => $content,
                    'has_link' => (bool) $url, 'link_text' => $url ? 'Ver inscritos' : '', 'link_target' => $url,
                ]));
            }
        } catch (\Throwable $e) {
            Log::error('No se pudo avisar a los administradores: ' . $e->getMessage());
        }
    }

    /** Sends one kind of notice to everyone enrolled in a date; returns how many e-mails were queued. */
    public static function mailEnrollees(CourseSession $date, string $kind, ?string $note = null): int
    {
        $course = $date->course;
        $sent = 0;
        foreach (self::enrolleesOf($date) as $enrollment) {
            $user = $enrollment->student?->user;
            if ($course && $user && self::mail($user, $course, $date, $kind, $note)) {
                $sent++;
            }
        }

        return $sent;
    }

    /** Students with an active enrollment in a date. */
    public static function enrolleesOf(CourseSession $session)
    {
        return Enrollment::where('session_id', $session->id)->where('status', Enrollment::STATUSES['active'])->with('student.user')->get();
    }
}
