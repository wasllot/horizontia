<?php

namespace App\Jobs;

use App\Models\WhatsappMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\WhatsAppService;

/** Envía una plantilla por la WhatsApp Cloud API y deja el resultado en el registro. Reintenta ante fallos de red o límites. */
class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300];

    /** @param array<int, string> $params */
    public function __construct(public int $messageId, public string $to, public string $template, public string $language, public array $params)
    {
    }

    public function handle(): void
    {
        $row = WhatsappMessage::find($this->messageId);
        if (!$row || $row->status === 'sent') {
            return;
        }
        if (!WhatsAppService::enabled()) {
            $row->update(['status' => 'skipped', 'error' => 'WhatsApp automático desactivado o sin configurar.']);

            return;
        }

        $response = Http::timeout(15)->withToken(WhatsAppService::token())->post(sprintf('https://graph.facebook.com/v21.0/%s/messages', WhatsAppService::phoneNumberId()), [
            'messaging_product' => 'whatsapp',
            'to' => $this->to,
            'type' => 'template',
            'template' => [
                'name' => $this->template,
                'language' => ['code' => $this->language],
                'components' => [['type' => 'body', 'parameters' => array_map(fn ($p) => ['type' => 'text', 'text' => $p], $this->params)]],
            ],
        ]);

        if ($response->successful()) {
            $row->update(['status' => 'sent', 'wamid' => $response->json('messages.0.id'), 'error' => null]);

            return;
        }

        $error = trim((string) $response->json('error.message')) ?: 'HTTP ' . $response->status();
        // 5xx y 429: se reintenta. Un error del mensaje en sí (plantilla inexistente, número inválido) no mejora reintentando.
        if ($response->serverError() || $response->status() === 429) {
            $row->update(['error' => $error]);
            throw new \RuntimeException('WhatsApp: ' . $error);
        }
        $row->update(['status' => 'failed', 'error' => mb_substr($error, 0, 500)]);
        Log::warning('WhatsApp message failed', ['message' => $this->messageId, 'status' => $response->status(), 'error' => $error]);   // sin teléfono ni texto
    }

    public function failed(\Throwable $e): void
    {
        WhatsappMessage::whereKey($this->messageId)->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500)]);
    }
}
