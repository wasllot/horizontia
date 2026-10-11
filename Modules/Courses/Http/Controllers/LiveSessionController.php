<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Courses\Models\CourseLiveStream;
use Modules\Courses\Services\CourseAccessService;
use Modules\Courses\Services\LiveSessionService;

class LiveSessionController extends Controller
{
    /** Calendar file of a live session, only for people who can access the course. */
    public function ics(Request $request, CourseLiveStream $stream)
    {
        $stream->loadMissing('course');
        abort_unless($stream->course && app(CourseAccessService::class)->canAccessCourse($request->user(), $stream->course), 403);
        abort_if($stream->status == CourseLiveStream::STATUS_CANCELLED, 404);

        return response(LiveSessionService::ics($stream), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="sesion-en-vivo-' . $stream->id . '.ics"',
        ]);
    }
}
