<?php

namespace App\Services\Events;

use App\Models\EventOrder;
use Illuminate\Support\Facades\Log;
use Modules\LaraPayease\Drivers\MercadoPago;

/** Aplica un pago de Mercado Pago (siempre releído de su API) a una compra de evento. Seguro de repetir. */
class EventPaymentSettler
{
    public function __construct(private EventSales $sales)
    {
    }

    public static function owns(?string $reference): bool
    {
        return str_starts_with((string) $reference, 'EV-');
    }

    /** @return string complete|pending|failed|refunded|ignored */
    public function settle(MercadoPago $driver, array $payment): string
    {
        $reference = (string) ($payment['external_reference'] ?? '');
        $order = EventOrder::with('event')->find((int) substr($reference, 3));
        if (!self::owns($reference) || empty($order)) {
            Log::warning('Mercado Pago: pago de evento sin compra', ['payment_id' => $payment['id'] ?? null]);

            return 'ignored';
        }

        switch ($payment['status'] ?? '') {
            case 'approved':
                if (!$driver->paymentMatchesReference($payment, $order->reference(), (float) $order->total)) {
                    Log::warning('Mercado Pago: monto o moneda distintos en compra de evento', ['order' => $order->id, 'payment_id' => $payment['id'] ?? null]);

                    return 'ignored';
                }
                $this->sales->markPaid($order, (string) $payment['id'], (string) ($payment['payment_type_id'] ?? $payment['payment_method_id'] ?? ''));

                return 'complete';

            case 'rejected':
            case 'cancelled':
                return 'failed';

            case 'refunded':
            case 'charged_back':
                if ((string) $order->payment_id === (string) ($payment['id'] ?? '')) {
                    $this->sales->markRefunded($order);

                    return 'refunded';
                }

                return 'ignored';

            default:
                // pending / in_process / authorized: efectivo, SPEI o tarjeta en revisión.
                if ($order->status !== EventOrder::PAID) {
                    $this->sales->holdForOfflinePayment($order, (string) ($payment['payment_type_id'] ?? ''));
                }

                return 'pending';
        }
    }
}
