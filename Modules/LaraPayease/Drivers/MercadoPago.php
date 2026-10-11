<?php

namespace Modules\LaraPayease\Drivers;

use App\Models\Order;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\LaraPayease\BasePaymentDriver;
use Modules\LaraPayease\Traits\Currency;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mercado Pago Checkout Pro. Amount, currency and order are always read from
 * the Order stored in the database, never from request parameters.
 */
class MercadoPago extends BasePaymentDriver
{
    use Currency;

    public const API_URL = 'https://api.mercadopago.com';
    public const PREFERENCE_EXPIRY_HOURS = 72;

    public function driverName(): string
    {
        return 'mercadopago';
    }

    public function chargeCustomer(array $params)
    {
        if (empty($this->keys['mp_access_token'] ?? null)) {
            return ['status' => Response::HTTP_BAD_REQUEST, 'message' => __('mercadopago.missing_credentials')];
        }

        $order = Order::find($params['order_id'] ?? null);
        if (empty($order) || empty(Auth::id()) || (int) $order->user_id !== (int) Auth::id()
            || $order->payment_method !== $this->driverName() || $order->status !== 'pending') {
            return ['status' => Response::HTTP_BAD_REQUEST, 'message' => __('general.payment_cancelled_desc')];
        }

        try {
            $preference = $this->createPreference($order);
        } catch (\Throwable $e) {
            Log::error('Mercado Pago: preference creation failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return ['status' => Response::HTTP_BAD_REQUEST, 'message' => __('mercadopago.preference_failed')];
        }

        $url = $this->getMode() === 'test'
            ? ($preference['sandbox_init_point'] ?? $preference['init_point'] ?? null)
            : ($preference['init_point'] ?? null);

        if (empty($url)) {
            return ['status' => Response::HTTP_BAD_REQUEST, 'message' => __('mercadopago.preference_failed')];
        }

        return redirect()->away($url);
    }

    /**
     * Used by the generic payment flow: validates a payment (by id) against its order.
     */
    public function paymentResponse(array $params = [])
    {
        $payment = !empty($params['payment_id']) ? $this->getPayment($params['payment_id']) : null;
        $order = !empty($payment['external_reference']) ? Order::find($payment['external_reference']) : null;

        if (!empty($payment) && !empty($order) && ($payment['status'] ?? '') === 'approved' && $this->paymentMatchesOrder($payment, $order)) {
            return [
                'status' => Response::HTTP_OK,
                'data'   => ['transaction_id' => (string) $payment['id'], 'order_id' => $order->id],
            ];
        }

        return ['status' => Response::HTTP_BAD_REQUEST, 'order_id' => $order?->id];
    }

    /**
     * Amount and currency Mercado Pago must report for this order.
     *
     * @return array{amount: float, currency: string}
     */
    public function expectedCharge(Order $order): array
    {
        $payable = max(0, (float) $order->amount - (float) $order->used_wallet_amt);

        return [
            'amount'   => round((float) $this->chargeableAmount($payable), 2),
            'currency' => strtoupper($this->getCurrency()),
        ];
    }

    public function paymentMatchesOrder(array $payment, Order $order): bool
    {
        $expected = $this->expectedCharge($order);

        return (string) ($payment['external_reference'] ?? '') === (string) $order->id
            && strtoupper((string) ($payment['currency_id'] ?? '')) === $expected['currency']
            && abs((float) ($payment['transaction_amount'] ?? 0) - $expected['amount']) < 0.01;
    }

    public function createPreference(Order $order): array
    {
        $charge = $this->expectedCharge($order);
        $titles = $order->items()->pluck('title')->filter()->implode(', ');
        $title = Str::limit($titles ?: (setting('_general.site_name') ?: config('app.name')) . ' #' . $order->id, 250, '');

        $payload = [
            'items' => [[
                'id'          => (string) $order->id,
                'title'       => $title,
                'quantity'    => 1,
                'currency_id' => $charge['currency'],
                'unit_price'  => $charge['amount'],
            ]],
            'payer' => array_filter([
                'email'   => $order->email,
                'name'    => $order->first_name,
                'surname' => $order->last_name,
            ]),
            'external_reference' => (string) $order->id,
            'back_urls' => [
                'success' => route('mercadopago.return', ['order' => $order->id]),
                'pending' => route('mercadopago.return', ['order' => $order->id]),
                'failure' => route('mercadopago.return', ['order' => $order->id]),
            ],
            'auto_return'        => 'approved',
            'notification_url'   => secure_url(route('mercadopago.webhook', [], false)),
            'binary_mode'        => !empty($this->keys['binary_mode'] ?? null),
            'expires'            => true,
            'expiration_date_to' => now()->addHours(self::PREFERENCE_EXPIRY_HOURS)->format('Y-m-d\TH:i:s.vP'),
        ];

        // Optional cap on card installments; without it Mercado Pago offers what the seller account allows (incl. meses sin intereses).
        $max = (int) ($this->keys['max_installments'] ?? 0);
        if ($max >= 1 && $max <= 24) {
            $payload['payment_methods'] = ['installments' => $max];
        }

        return $this->client()
            ->withHeaders(['X-Idempotency-Key' => (string) Str::uuid()])
            ->post('/checkout/preferences', $payload)
            ->throw()
            ->json();
    }

    /**
     * @return array|null null when Mercado Pago does not know the payment
     */
    public function getPayment(string|int $paymentId): ?array
    {
        $response = $this->client()->get('/v1/payments/' . rawurlencode((string) $paymentId));

        if ($response->status() === Response::HTTP_NOT_FOUND) {
            return null;
        }

        return $response->throw()->json();
    }

    /**
     * Full refund when $amount is null, partial otherwise.
     */
    public function refund(string|int $paymentId, ?float $amount = null): array
    {
        return $this->client()
            ->withHeaders(['X-Idempotency-Key' => (string) Str::uuid()])
            ->post('/v1/payments/' . rawurlencode((string) $paymentId) . '/refunds', $amount === null ? (object) [] : ['amount' => $amount])
            ->throw()
            ->json();
    }

    /**
     * Validates the x-signature header of a webhook notification.
     */
    public function validSignature(?string $signature, ?string $requestId, ?string $dataId): bool
    {
        $secret = $this->keys['mp_webhook_secret'] ?? '';
        if (empty($secret) || empty($signature)) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signature) as $chunk) {
            [$k, $v] = array_pad(explode('=', $chunk, 2), 2, null);
            $parts[trim((string) $k)] = trim((string) $v);
        }

        if (empty($parts['ts']) || empty($parts['v1'])) {
            return false;
        }

        $manifest = '';
        if (!empty($dataId)) {
            $manifest .= 'id:' . strtolower($dataId) . ';';
        }
        if (!empty($requestId)) {
            $manifest .= 'request-id:' . $requestId . ';';
        }
        $manifest .= 'ts:' . $parts['ts'] . ';';

        return hash_equals(hash_hmac('sha256', $manifest, $secret), strtolower($parts['v1']));
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl(self::API_URL)
            ->withToken($this->keys['mp_access_token'] ?? '')
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->retry(2, 300, fn ($e) => $e instanceof ConnectionException
                || ($e instanceof RequestException && $e->response->serverError()), throw: false);
    }
}
