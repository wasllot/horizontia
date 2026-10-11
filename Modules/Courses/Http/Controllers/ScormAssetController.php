<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Services\CourseAccessService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams extracted SCORM package files stored on a remote private disk (R2/S3).
 * Relative URLs inside the package keep working because every file is served under the same prefix.
 */
class ScormAssetController extends Controller
{
    private const MIME_BY_EXT = [
        'html' => 'text/html; charset=UTF-8', 'htm' => 'text/html; charset=UTF-8',
        'js' => 'application/javascript', 'css' => 'text/css', 'json' => 'application/json',
        'xml' => 'application/xml', 'svg' => 'image/svg+xml', 'png' => 'image/png',
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp',
        'mp4' => 'video/mp4', 'mp3' => 'audio/mpeg', 'woff' => 'font/woff', 'woff2' => 'font/woff2',
        'ttf' => 'font/ttf', 'swf' => 'application/x-shockwave-flash', 'pdf' => 'application/pdf',
    ];

    public function show(Request $request, int $curriculumId, string $path): StreamedResponse
    {
        $curriculum = Curriculum::with('section.course')->findOrFail($curriculumId);

        if ($curriculum->type !== 'scorm' || !app(CourseAccessService::class)->canViewCurriculum($request->user(), $curriculum)) {
            abort(403);
        }

        $path = ltrim($path, '/');
        if ($path === '' || str_contains($path, '..')) {
            abort(404);
        }

        $disk = Storage::disk(getPrivateDisk());
        $key = "scorm/{$curriculumId}/{$path}";
        if (!$disk->exists($key)) {
            abort(404);
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return response()->stream(function () use ($disk, $key) {
            $stream = $disk->readStream($key);
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => self::MIME_BY_EXT[$ext] ?? 'application/octet-stream',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
