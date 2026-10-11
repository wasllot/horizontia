<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Entrega los archivos adjuntos de un chat solo a las dos personas de la conversación. */
class ChatFileController extends Controller
{
    public function show(Request $request, ChatMessage $message)
    {
        $conversation = $message->conversation;
        abort_unless($conversation && $conversation->includes($request->user()) && $message->file_path, 404);

        $disk = Storage::disk(\App\Services\ChatService::disk());
        abort_unless($disk->exists($message->file_path), 404);

        $inline = $message->isImage() && in_array($message->file_mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true);
        $name = $message->file_name ?: basename($message->file_path);

        return response()->stream(function () use ($disk, $message) {
            fpassthru($disk->readStream($message->file_path));
        }, 200, [
            'Content-Type' => $inline ? $message->file_mime : 'application/octet-stream',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment') . '; filename="' . addslashes($name) . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
