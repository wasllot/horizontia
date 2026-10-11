<?php

namespace App\Http\Controllers;

use App\Models\LearningPath;
use App\Services\LearningPathService;
use Illuminate\Support\Facades\Auth;

/** Páginas públicas de las rutas de aprendizaje. */
class LearningPathController extends Controller
{
    public function index(LearningPathService $service)
    {
        $paths = LearningPath::active()->with('courses.thumbnail')->orderBy('sort')->orderByDesc('id')->get()->filter(fn ($p) => $service->isPublic($p))->values();

        return view('paths.index', compact('paths'));
    }

    public function show(string $slug, LearningPathService $service)
    {
        $path = LearningPath::active()->where('slug', $slug)->with('courses.thumbnail', 'courses.pricing')->first();
        abort_unless($path && $service->isPublic($path), 404);

        $student = Auth::user()?->hasRole('student') ? Auth::user() : null;
        $status = $service->status($path, $student);
        $certificate = $student ? $service->issueIfComplete($student, $path) : null;

        return view('paths.show', compact('path', 'status', 'certificate', 'student'));
    }
}
