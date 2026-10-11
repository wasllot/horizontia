<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Enrollment;

class EnrolleesExportController extends Controller
{
    /** CSV of a course's enrollees (optionally one date). Only admins and the course's instructor. */
    public function csv(Request $request, Course $course)
    {
        $user = $request->user();
        abort_unless($user->hasRole('admin') || $user->hasRole('sub_admin') || $course->instructor_id === $user->id, 403);

        $filter = (string) $request->query('fecha', '');
        $rows = Enrollment::where('course_id', $course->id)->where('status', Enrollment::STATUSES['active'])
            ->when($filter === 'sin', fn ($q) => $q->whereNull('session_id'))
            ->when(ctype_digit($filter), fn ($q) => $q->where('session_id', (int) $filter))
            ->with(['student.user', 'session'])->orderBy('session_id')->orderBy('created_at')->get();

        // Spreadsheet formulas typed by a student in their name must not run when the file is opened.
        $safe = fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : (string) $v;

        return response()->streamDownload(function () use ($rows, $safe) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");   // BOM so Excel reads the accents
            fputcsv($out, ['Nombre', 'Correo', 'Teléfono', 'Fecha del curso', 'Sede', 'Ciudad', 'Fecha de compra']);
            foreach ($rows as $e) {
                $p = $e->student;
                $s = $e->session;
                fputcsv($out, array_map($safe, [
                    trim(($p?->first_name ?? '') . ' ' . ($p?->last_name ?? '')), $p?->user?->email, $p?->phone_number,
                    $s ? $s->startsLocal()->format('Y-m-d H:i') : 'Sin fecha', $s?->venue_name, $s?->city, optional($e->created_at)->format('Y-m-d H:i'),
                ]));
            }
            fclose($out);
        }, 'inscritos-' . $course->slug . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
