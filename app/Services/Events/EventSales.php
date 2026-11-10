<?php

namespace App\Services\Events;

use App\Mail\EventTicketsEmail;
use App\Models\Event;
use App\Models\EventOrder;
use App\Models\EventTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/** Reglas de venta de un evento: estado, cupo, descuentos, IVA, reserva de lugares y confirmación del pago. */
class EventSales
{
    public const OPEN = 'open';
    public const SOON = 'soon';            // ventas sin abrir (interruptor, precio o políticas pendientes)
    public const NOT_YET = 'not_yet';      // abren en una fecha futura
    public const ENDED = 'ended';          // cerraron por fecha
    public const SOLD_OUT = 'sold_out';
    public const FINISHED = 'finished';

    public function state(Event $event): string
    {
        if ($event->isFinished()) {
            return self::FINISHED;
        }
        // Sin precio o sin la política propia del evento nunca se cobra (el checkout no hereda la de cursos).
        if (!$event->isPublic() || !$event->sales_enabled || !$event->hasPrice() || !$event->policiesReady()) {
            return self::SOON;
        }
        if ($event->sales_open_at && $event->sales_open_at->isFuture()) {
            return self::NOT_YET;
        }
        if ($event->sales_close_at && $event->sales_close_at->isPast()) {
            return self::ENDED;
        }
        if ($this->available($event) === 0) {
            return self::SOLD_OUT;
        }

        return self::OPEN;
    }

    public function isOpen(Event $event): bool
    {
        return $this->state($event) === self::OPEN;
    }

    /** Entradas pagadas más las que alguien está pagando ahora (reserva vigente). */
    public function taken(Event $event): int
    {
        return (int) EventOrder::where('event_id', $event->id)
            ->where(fn ($q) => $q->where('status', EventOrder::PAID)
                ->orWhere(fn ($p) => $p->where('status', EventOrder::PENDING)->where('expires_at', '>', now())))
            ->sum('quantity');
    }

    /** null = sin límite de cupo. */
    public function available(Event $event): ?int
    {
        return $event->capacity === null ? null : max(0, (int) $event->capacity - $this->taken($event));
    }

    public function maxQuantity(Event $event): int
    {
        $max = max(1, (int) $event->max_per_order);
        $available = $this->available($event);

        return $available === null ? $max : max(0, min($max, $available));
    }

    /**
     * Lo elegido como [clave de paquete => cantidad], solo con paquetes vigentes. Un entero significa «n entradas individuales»
     * (el paquete de 1 persona, o el más chico si no hay).
     *
     * @return array<string, int>
     */
    public function selection(Event $event, int|array|null $selection): array
    {
        $packages = array_column($event->packageList(), null, 'key');
        if (!$packages) {
            return [];
        }
        if (!is_array($selection)) {
            $key = isset($packages['p1']) ? 'p1' : array_key_first($packages);

            return [$key => max(1, (int) $selection)];
        }
        $out = [];
        foreach ($selection as $key => $qty) {
            if ((int) $qty > 0 && isset($packages[(string) $key])) {
                $out[(string) $key] = (int) $qty;
            }
        }

        return $out;
    }

    /** Personas que cubre lo elegido (cada persona lleva su entrada, su pulsera y su diploma). */
    public function persons(Event $event, int|array|null $selection): int
    {
        $total = 0;
        foreach ($this->selection($event, $selection) as $key => $qty) {
            $total += ($event->findPackage($key)['persons'] ?? 0) * $qty;
        }

        return $total;
    }

    /**
     * @return array{unit: float, quantity: int, persons: int, lines: array<int, array{key: string, name: string, persons: int, qty: int, unit: float, subtotal: float}>, subtotal: float, discounts: array<int, array{label: string, percent: float, amount: float}>, discount: float, total: float, iva: float, base: float}
     */
    public function quote(Event $event, int|array|null $selection): array
    {
        $lines = [];
        $persons = 0;
        $subtotal = 0.0;
        foreach ($this->selection($event, $selection) as $key => $qty) {
            $p = $event->findPackage($key);
            $lines[] = ['key' => $key, 'name' => $p['name'], 'persons' => $p['persons'], 'qty' => $qty, 'unit' => $p['price'], 'subtotal' => round($p['price'] * $qty, 2)];
            $persons += $p['persons'] * $qty;
            $subtotal += $p['price'] * $qty;
        }
        $subtotal = round($subtotal, 2);

        $applied = [];
        $now = now();
        foreach ((array) $event->discounts as $key => $rule) {
            if (empty($rule['enabled']) || (float) ($rule['percent'] ?? 0) <= 0) {
                continue;
            }
            if (!empty($rule['starts_at']) && $now->lt(\Illuminate\Support\Carbon::parse($rule['starts_at'], Event::timezone())->startOfDay())) {
                continue;
            }
            if (!empty($rule['ends_at']) && $now->gt(\Illuminate\Support\Carbon::parse($rule['ends_at'], Event::timezone())->endOfDay())) {
                continue;
            }
            if ($key === 'group' && $persons < max(2, (int) ($rule['min_qty'] ?? 4))) {
                continue;
            }
            $percent = min(100.0, (float) $rule['percent']);
            $applied[] = ['label' => (string) ($rule['label'] ?? ($key === 'group' ? 'Descuento por grupo' : 'Preventa')), 'percent' => $percent, 'amount' => round($subtotal * $percent / 100, 2)];
        }
        $discount = min($subtotal, round(array_sum(array_column($applied, 'amount')), 2));
        $total = round($subtotal - $discount, 2);
        $iva = $event->ivaIn($total);

        return ['unit' => (float) ($lines[0]['unit'] ?? 0), 'quantity' => $persons, 'persons' => $persons, 'lines' => $lines, 'subtotal' => $subtotal, 'discounts' => $applied, 'discount' => $discount, 'total' => $total, 'iva' => $iva, 'base' => round($total - $iva, 2)];
    }

    /**
     * Crea la compra con sus entradas nominales y reserva los lugares. Revalida todo con el evento bloqueado
     * para que dos compras simultáneas no rebasen el cupo.
     *
     * @param array{name: string, email: string, phone: string, items?: array<string,int>, quantity?: int, attendees: array<int,string>, minors?: int, has_telescope?: ?bool, heard_from?: ?string, newsletter?: bool, wants_invoice?: bool, invoice?: ?array, utm?: ?array} $data
     */
    public function createOrder(Event $event, array $data): EventOrder
    {
        return DB::transaction(function () use ($event, $data) {
            $locked = Event::lockForUpdate()->findOrFail($event->id);
            $state = $this->state($locked);
            if ($state !== self::OPEN) {
                throw new EventSalesException($state);
            }
            $selection = $data['items'] ?? (int) ($data['quantity'] ?? 0);
            if (!is_array($selection) && $selection < 1) {
                throw new EventSalesException('quantity');
            }
            $quote = $this->quote($locked, $selection);
            $quantity = $quote['persons'];   // el cupo y las entradas cuentan personas, no paquetes
            if ($quantity < 1 || $quantity > $this->maxQuantity($locked)) {
                throw new EventSalesException($this->available($locked) === 0 ? self::SOLD_OUT : 'quantity');
            }
            $order = EventOrder::create([
                'event_id' => $locked->id,
                'code' => strtolower((string) Str::ulid()),
                'name' => $data['name'], 'email' => mb_strtolower($data['email']), 'phone' => $data['phone'],
                'quantity' => $quantity,
                'items' => array_map(fn ($l) => ['key' => $l['key'], 'name' => $l['name'], 'persons' => $l['persons'], 'qty' => $l['qty'], 'unit_price' => $l['unit'], 'subtotal' => $l['subtotal']], $quote['lines']),
                'minors' => (int) ($data['minors'] ?? 0),
                'has_telescope' => $data['has_telescope'] ?? null,
                'heard_from' => $data['heard_from'] ?? null,
                'newsletter' => (bool) ($data['newsletter'] ?? false),
                'wants_invoice' => (bool) ($data['wants_invoice'] ?? false),
                'invoice' => !empty($data['wants_invoice']) ? ($data['invoice'] ?? null) : null,
                'policies_accepted_at' => now(),
                'unit_price' => round($quote['subtotal'] / $quantity, 2), 'discount' => $quote['discount'],
                'discount_label' => $quote['discounts'] ? implode(' + ', array_map(fn ($d) => $d['label'] . ' ' . rtrim(rtrim(number_format($d['percent'], 2), '0'), '.') . ' %', $quote['discounts'])) : null,
                'total' => $quote['total'], 'iva' => $quote['iva'],
                'status' => EventOrder::PENDING,
                'expires_at' => now()->addMinutes(max(5, (int) $locked->hold_minutes)),
                'utm' => $data['utm'] ?? null,
            ]);

            $attendees = array_values($data['attendees'] ?? []);
            for ($i = 0; $i < $quantity; $i++) {
                $name = $attendees[$i] ?? '';
                EventTicket::create([
                    'event_id' => $locked->id, 'event_order_id' => $order->id,
                    'attendee_name' => $name ?: ($i === 0 ? $data['name'] : 'Asistente ' . ($i + 1)),
                    'code' => $this->ticketCode(),
                ]);
            }

            return $order;
        });
    }

    /** Clave corta y legible (sin 0/O/1/I) que también va en el QR. */
    public function ticketCode(): string
    {
        do {
            $code = 'AF-' . substr(str_shuffle(str_repeat('ABCDEFGHJKMNPQRSTUVWXYZ23456789', 3)), 0, 4) . '-' . substr(str_shuffle(str_repeat('ABCDEFGHJKMNPQRSTUVWXYZ23456789', 3)), 0, 4);
        } while (EventTicket::where('code', $code)->exists());

        return $code;
    }

    /** Pago aprobado: idempotente. Un pago aprobado tarde se respeta aunque la reserva ya hubiera vencido. */
    public function markPaid(EventOrder $order, string $paymentId, ?string $method = null): EventOrder
    {
        $paid = DB::transaction(function () use ($order, $paymentId, $method) {
            $locked = EventOrder::lockForUpdate()->findOrFail($order->id);
            if ($locked->status === EventOrder::PAID) {
                return $locked;
            }
            if ($locked->status === EventOrder::EXPIRED) {
                Log::warning('Evento: pago aprobado sobre una reserva vencida', ['order' => $locked->id, 'payment' => $paymentId]);
            }
            $locked->update(['status' => EventOrder::PAID, 'paid_at' => now(), 'payment_id' => $paymentId, 'payment_method' => $method, 'expires_at' => null]);
            app(EventHotelNotifier::class)->assignCode($locked);

            return $locked;
        });

        $this->sendConfirmation($paid);
        app(EventHotelNotifier::class)->notify($paid);

        return $paid;
    }

    /** Efectivo/transferencia: la reserva se alarga el tiempo definido para pagos fuera de línea. */
    public function holdForOfflinePayment(EventOrder $order, ?string $method = null): void
    {
        if ($order->status === EventOrder::PENDING || $order->status === EventOrder::EXPIRED) {
            $order->update(['status' => EventOrder::PENDING, 'payment_method' => $method ?: $order->payment_method,
                'expires_at' => now()->addHours(max(1, (int) $order->event->offline_hold_hours))]);
        }
    }

    public function markRefunded(EventOrder $order): void
    {
        if ($order->status === EventOrder::PAID) {
            $order->update(['status' => EventOrder::REFUNDED]);
        }
    }

    public function expireStale(): int
    {
        return EventOrder::where('status', EventOrder::PENDING)->where('expires_at', '<', now())->update(['status' => EventOrder::EXPIRED]);
    }

    public function sendConfirmation(EventOrder $order): void
    {
        if (!$order->isPaid() || $order->confirmation_sent_at) {
            return;
        }
        $claimed = EventOrder::where('id', $order->id)->whereNull('confirmation_sent_at')->update(['confirmation_sent_at' => now()]);
        if (!$claimed) {
            return;
        }
        try {
            Mail::to($order->email)->queue(new EventTicketsEmail($order->fresh(['event', 'tickets'])));
        } catch (\Throwable $e) {
            EventOrder::where('id', $order->id)->update(['confirmation_sent_at' => null]);
            Log::error('Evento: no se pudo enviar la confirmación', ['order' => $order->id, 'error' => $e->getMessage()]);
        }
    }
}
