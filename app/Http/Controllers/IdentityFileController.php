<?php

namespace App\Http\Controllers;

use App\Models\UserIdentityVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Streams an identity document (photo, ID card, transcript) to its owner and to admins only; never a public URL. */
class IdentityFileController extends Controller
{
    private const FIELDS = ['personal_photo', 'attachments', 'transcript'];

    public function show(Request $request, UserIdentityVerification $verification, string $field)
    {
        abort_unless(in_array($field, self::FIELDS, true), 404);
        $user = $request->user();
        abort_unless($user && ($user->hasRole('admin') || $user->hasRole('sub_admin') || $user->id === $verification->user_id), 403);

        $path = $verification->{$field};
        abort_unless(is_string($path) && $path !== '' && !str_contains($path, '..'), 404);

        // New files are private; documents uploaded before this change may still sit on the storage disk.
        foreach (array_unique([identityDisk(), getStorageDisk()]) as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                $mime = Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream';
                abort_unless(str_starts_with($mime, 'image/') || $mime === 'application/pdf', 404);

                return response(Storage::disk($disk)->get($path), 200, [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline',
                    'Cache-Control' => 'private, no-store',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }
        }

        abort(404);
    }
}
