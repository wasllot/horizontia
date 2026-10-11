<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Courses\Models\CourseLiveStream;
use Modules\Courses\Services\LiveRecordingService;

/**
 * Webhook de Zoom: cuando termina de procesarse la grabación en la nube (`recording.completed`), el enlace se guarda en la
 * sesión en vivo que usa esa reunión y se publica como lección. Requiere ZOOM_WEBHOOK_SECRET (Zoom → Tu app → Features → Event Subscriptions).
 */
class ZoomWebhookController extends Controller
{
    public function handle(Request $request, LiveRecordingService $recordings)
    {
        $secret = (string) config('services.zoom.webhook_secret');
        if ($secret === '') {
            return response()->json(['message' => 'Webhook de Zoom no configurado.'], 404);
        }

        $body = $request->getContent();
        $payload = json_decode($body, true) ?: [];

        // Zoom comprueba que la URL es nuestra: hay que responder con el token cifrado con el secreto.
        if (($payload['event'] ?? null) === 'endpoint.url_validation' && !empty($payload['payload']['plainToken'])) {
            $plain = (string) $payload['payload']['plainToken'];

            return response()->json(['plainToken' => $plain, 'encryptedToken' => hash_hmac('sha256', $plain, $secret)]);
        }

        $timestamp = (string) $request->header('x-zm-request-timestamp');
        $expected = 'v0=' . hash_hmac('sha256', "v0:{$timestamp}:{$body}", $secret);
        if ($timestamp === '' || !hash_equals($expected, (string) $request->header('x-zm-signature')) || abs(time() - (int) $timestamp) > 300) {
            return response()->json(['message' => 'Firma no válida.'], 401);
        }

        if (($payload['event'] ?? null) !== 'recording.completed') {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        $object = $payload['payload']['object'] ?? [];
        $meetingId = preg_replace('/\D/', '', (string) ($object['id'] ?? ''));
        $share = (string) ($object['share_url'] ?? '');
        if ($meetingId === '' || !str_starts_with($share, 'https://')) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        // La reunión de Zoom se reconoce por el número que va en el enlace que pegó el instructor (…/j/123456789).
        $stream = CourseLiveStream::whereNull('recording_curriculum_id')->where(fn ($q) => $q->whereNull('recording_url')->orWhere('recording_url', ''))
            ->where('meeting_link', 'like', '%/j/' . $meetingId . '%')->orderByDesc('date_time')->first();
        if (!$stream) {
            return response()->json(['ok' => true, 'matched' => false]);
        }

        $stream->update(['recording_url' => mb_substr($share, 0, 255)]);
        $recordings->publishIfReady($stream->refresh(), $object['password'] ?? $object['recording_play_passcode'] ?? null);

        return response()->json(['ok' => true, 'matched' => true]);
    }
}
