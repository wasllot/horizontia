<?php

namespace Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Serves private lesson files from a LOCAL disk through a signed, expiring URL.
 * On remote disks (R2/S3) lessonMediaUrl() hands out presigned URLs instead and this route is unused.
 * The signature (issued only to enrolled students / instructors / admins) is the authorization.
 */
class VideoController extends Controller
{
    private const ALLOWED_PREFIXES = ['curriculum_videos/', 'courses/', 'media/videos/', 'assignments/'];

    public function play(Request $request, string $path)
    {
        if (!$request->hasValidSignature()) {
            abort(403);
        }

        $relativePath = ltrim($path, '/');
        $legacyType = base64_decode((string) $request->get('type'), true);
        if (in_array($legacyType, ['courses', 'curriculum_videos'], true)) {
            $relativePath = "$legacyType/$relativePath";
        }

        if (str_contains($relativePath, '..') || !$this->hasAllowedPrefix($relativePath)) {
            abort(404);
        }

        $disk = collect([getPrivateDisk(), getStorageDisk()])->unique()
            ->first(fn ($d) => !isRemoteDisk($d) && Storage::disk($d)->exists($relativePath));

        if (!$disk) {
            abort(404);
        }

        return response()->file(Storage::disk($disk)->path($relativePath), [
            'Cache-Control' => 'private, max-age=3600',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function hasAllowedPrefix(string $path): bool
    {
        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }
        return false;
    }
}
