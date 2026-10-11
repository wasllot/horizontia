<?php

namespace App\Http\Controllers;

use App\Services\LessonProgressService;
use Illuminate\Http\Request;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Services\CourseAccessService;

/** Guarda por AJAX el segundo del video donde va el alumno (para «retomar donde te quedaste»). */
class LessonPositionController extends Controller
{
    public function store(Request $request, LessonProgressService $progress, CourseAccessService $access)
    {
        $data = $request->validate(['curriculum_id' => 'required|integer|min:1', 'seconds' => 'required|integer|min:0|max:86400']);
        $user = $request->user();
        abort_unless($user->hasRole('student'), 403);

        $lesson = Curriculum::with('section.course')->find($data['curriculum_id']);
        abort_unless($lesson && $access->canViewCurriculum($user, $lesson), 403);

        $progress->save($user, $lesson, (int) $data['seconds']);

        return response()->json(['ok' => true]);
    }
}
