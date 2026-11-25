<?php

namespace App\Livewire\Pages\Admin\Events;

use App\Models\Event;
use App\Models\EventOrder;
use App\Services\Events\EventHotelNotifier;
use App\Services\Events\EventSales;
use App\Support\MailDiagnostics;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Admin > Eventos > Compras: compras de entradas de un evento, con filtro por estado, CSV y reenvío de la confirmación. */
class EventOrders extends Component
{
    use \App\Livewire\Concerns\AuthorizesAdmin;

    #[Url(as: 'evento')]
    public ?int $eventId = null;

    #[Url(as: 'estado')]
    public string $orderStatus = 'paid';

    public function mount(): void
    {
        // Sin evento en la URL se muestra el más reciente (hoy hay uno solo).
        $this->eventId ??= Event::orderByDesc('start_at')->value('id');
    }

    public function updatedOrderStatus(): void
    {
        if (!in_array($this->orderStatus, ['paid', 'pending', 'expired', 'refunded', 'all'], true)) {
            $this->orderStatus = 'paid';
        }
    }

    private function toast(string $message, string $type = 'success', int $autoclose = 2000): void
    {
        $this->dispatch('showAlertMessage', type: $type, title: $type === 'success' ? __('general.success_title') : 'Atención', message: $message, autoclose: $autoclose);
    }

    /**
     * Reenvía los boletos AHORA (directo por SMTP, sin pasar por la cola) al correo indicado, o al del comprador. Si el servidor
     * de correo falla, el aviso dice por qué; si lo acepta, dice que llegó al servidor (no que ya esté en la bandeja).
     */
    public function resend(int $orderId, ?string $to = null): void
    {
        $order = EventOrder::where('event_id', $this->eventId)->findOrFail($orderId);
        if (!$order->isPaid()) {
            $this->toast('Solo se reenvía la confirmación de compras pagadas.', 'error', 5000);

            return;
        }
        $to = mb_strtolower(trim((string) ($to ?: $order->email)));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->toast('«' . mb_substr($to, 0, 80) . '» no es un correo válido.', 'error', 5000);

            return;
        }
        try {
            app(EventSales::class)->sendConfirmationNow($order, $to);
        } catch (\Throwable $e) {
            $info = MailDiagnostics::describe($e, [config('mail.mailers.smtp.password'), config('mail.mailers.smtp.username')]);
            Log::error('Evento: el reenvío de boletos falló', ['order' => $order->id, 'error' => $info['message']]);
            $this->toast('No se pudo enviar a ' . $to . '. ' . ($info['hint'] ?: mb_substr($info['message'], 0, 220)) . ' Mientras, usa «Copiar enlace de los boletos» y mándalo por WhatsApp.', 'error', 15000);

            return;
        }
        $this->toast('El servidor de correo aceptó los boletos para ' . $to . '. Si no los ve en unos minutos, que revise spam o usa «Copiar enlace de los boletos».', 'success', 9000);
    }

    public function resendHotel(int $orderId): void
    {
        $order = EventOrder::with('event')->where('event_id', $this->eventId)->findOrFail($orderId);
        if (!$order->isPaid() || !$order->roomCode()) {
            $this->toast('Solo se avisa al hotel de compras pagadas con código de habitación.', 'error');

            return;
        }
        if (!$order->event->hotelEmails()) {
            $this->toast('Captura el correo del hotel en Eventos → Hotel y habitaciones.', 'error');

            return;
        }
        $order->update(['hotel_notified_at' => null]);
        app(EventHotelNotifier::class)->notify($order->fresh());
        $this->toast('Aviso reenviado al hotel.');
    }

    public function sendHotelList(): void
    {
        $event = Event::findOrFail($this->eventId);
        if (!$event->hotelEmails()) {
            $this->toast('Captura el correo del hotel en Eventos → Hotel y habitaciones.', 'error');

            return;
        }
        $this->toast(app(EventHotelNotifier::class)->sendSummary($event) ? 'Lista enviada al hotel.' : 'Todavía no hay compras pagadas con código de habitación.', app(EventHotelNotifier::class)->orders($event)->isEmpty() ? 'error' : 'success');
    }

    #[Layout('layouts.admin-app')]
    public function render()
    {
        $events = Event::orderByDesc('start_at')->get();
        $current = $events->firstWhere('id', $this->eventId);
        $status = in_array($this->orderStatus, ['paid', 'pending', 'expired', 'refunded', 'all'], true) ? $this->orderStatus : 'paid';
        $base = fn () => EventOrder::where('event_id', $current?->id);

        return view('livewire.pages.admin.events.event-orders', [
            'events' => $events,
            'current' => $current,
            'status' => $status,
            'orders' => $current ? $base()->when($status !== 'all', fn ($q) => $q->where('status', $status))->with(['tickets', 'event'])->latest('id')->limit(100)->get() : collect(),
            'stats' => $current ? [
                'paid' => (int) $base()->where('status', EventOrder::PAID)->sum('quantity'),
                'revenue' => (float) $base()->where('status', EventOrder::PAID)->sum('total'),
                'pending' => (int) $base()->where('status', EventOrder::PENDING)->where('expires_at', '>', now())->sum('quantity'),
            ] : null,
        ]);
    }
}
