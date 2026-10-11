<?php

namespace App\Services;

use App\Mail\PaymentPendingEmail;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Payments that are not approved at once (OXXO/cash, SPEI, a card under review) keep the order `pending` for hours or days.
 * This remembers how to finish the payment, tells the buyer once by e-mail, and feeds the "pago pendiente" page.
 */
class PendingPayments
{
    private const TTL_DAYS = 10;

    /** Human description of a Mercado Pago payment that is waiting. */
    public static function describe(array $payment): array
    {
        $type = (string) ($payment['payment_type_id'] ?? '');
        $method = strtolower((string) ($payment['payment_method_id'] ?? ''));
        $inReview = ($payment['status'] ?? '') === 'in_process' && in_array($type, ['credit_card', 'debit_card', 'prepaid_card'], true);

        $label = match (true) {
            $method === 'oxxo' => 'OXXO',
            $method === 'spei' || $type === 'bank_transfer' => 'transferencia SPEI',
            $type === 'ticket' => 'pago en efectivo',
            $type === 'atm' => 'cajero automático',
            $inReview => 'tarjeta',
            default => 'Mercado Pago',
        };

        $expires = $payment['date_of_expiration'] ?? null;

        return [
            'kind' => $inReview ? 'review' : 'offline',
            'label' => $label,
            'voucher_url' => $payment['transaction_details']['external_resource_url'] ?? null,
            'expires_at' => $expires ? \Carbon\Carbon::parse($expires)->timezone(config('academy.timezone'))->locale('es')->translatedFormat('j \d\e F, H:i') : null,
        ];
    }

    /** Stores the instructions and e-mails them the first time this payment is seen. */
    public static function remember(Order $order, array $payment): array
    {
        $info = self::describe($payment);
        Cache::put('mp-pending:' . $order->id, $info, now()->addDays(self::TTL_DAYS));

        if (Cache::add('mp-pending-mail:' . $order->id . ':' . ($payment['id'] ?? ''), 1, now()->addDays(self::TTL_DAYS))) {
            try {
                $user = $order->orderBy ?? \App\Models\User::find($order->user_id);
                if ($user?->email) {
                    Mail::to($user->email)->queue(new PaymentPendingEmail($order, $user->profile?->first_name ?: 'estudiante', $info));
                }
            } catch (\Throwable $e) {
                Log::error('No se pudo enviar el aviso de pago pendiente: ' . $e->getMessage(), ['order_id' => $order->id]);
            }
        }

        return $info;
    }

    public static function info(Order $order): ?array
    {
        return Cache::get('mp-pending:' . $order->id);
    }

    public static function forget(Order $order): void
    {
        Cache::forget('mp-pending:' . $order->id);
    }
}
