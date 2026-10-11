<?php

namespace Modules\LaraPayease\Http\Controllers;

use App\Facades\Cart;
use App\Http\Controllers\Controller;
use App\Jobs\CompletePurchaseJob;
use App\Models\Order;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\LaraPayease\Drivers\MercadoPago;
use Nwidart\Modules\Facades\Module;

class MercadoPagoController extends Controller
{
    /**
     * Webhook: the body is never trusted; the payment is always re-fetched from the API.
     */
    public function webhook(Request $request)
    {
        $driver = getGatewayObject('mercadopago');
        if (empty($driver)) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        // PHP rewrites the "data.id" query key to "data_id".
        $dataId = $request->query('data_id') ?? $request->query('data.id') ?? $request->input('data.id');
        $dataId = is_scalar($dataId) ? (string) $dataId : null;

        if (!$driver->validSignature($request->header('x-signature'), $request->header('x-request-id'), $dataId)) {
            return response()->json(['message' => 'invalid signature'], 401);
        }

        $type = $request->query('type') ?? $request->input('type');
        if ($type !== 'payment' || empty($dataId)) {
            return response()->json(['message' => 'ignored']);
        }

        try {
            $payment = $driver->getPayment($dataId);
        } catch (\Throwable $e) {
            Log::error('Mercado Pago webhook: payment lookup failed', ['payment_id' => $dataId, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'retry later'], 500);
        }

        if (empty($payment)) {
            return response()->json(['message' => 'payment not found']);
        }

        $outcome = $this->settle($driver, $payment);

        return response()->json(['message' => $outcome]);
    }

    /**
     * Browser return (back_urls). It never completes anything from the query string:
     * the payment status is read from Mercado Pago and the order must belong to the user.
     */
    public function return(Request $request)
    {
        $order = Order::find($request->query('order'));
        if (empty($order) || (int) $order->user_id !== (int) auth()->id() || $order->payment_method !== 'mercadopago') {
            abort(403);
        }

        $driver = getGatewayObject('mercadopago');
        $paymentId = $request->query('payment_id') ?? $request->query('collection_id');
        $outcome = $order->status === 'pending' ? 'pending' : 'complete';

        if ($order->status === 'pending' && !empty($driver) && !empty($paymentId) && is_scalar($paymentId)) {
            try {
                $payment = $driver->getPayment($paymentId);
            } catch (\Throwable $e) {
                Log::error('Mercado Pago return: payment lookup failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
                $payment = null;
            }

            if (!empty($payment) && (string) ($payment['external_reference'] ?? '') === (string) $order->id) {
                $outcome = $this->settle($driver, $payment);
            }
        }

        if ($outcome === 'complete') {
            Cart::clear();
            session()->forget(['order_id', 'payment_data']);
            return redirect()->route('thank-you', ['id' => $order->id]);
        }

        if ($outcome === 'pending') {
            // Cash/SPEI/review: the order exists and waits for the payment. Clear the cart so it cannot be paid twice.
            Cart::clear();
            session()->forget(['order_id', 'payment_data']);
            return redirect()->route('payment.pending', ['order' => $order->id]);
        }

        return redirect()->route('checkout')->with('error', __('mercadopago.payment_rejected'));
    }

    /**
     * Applies a payment fetched from Mercado Pago to its order. Safe to call any number of times.
     *
     * @return string complete|pending|failed|refunded|ignored
     */
    protected function settle(MercadoPago $driver, array $payment): string
    {
        $order = Order::find($payment['external_reference'] ?? null);
        if (empty($order) || $order->payment_method !== 'mercadopago') {
            Log::warning('Mercado Pago: payment without a matching order', ['payment_id' => $payment['id'] ?? null]);
            return 'ignored';
        }

        switch ($payment['status'] ?? '') {
            case 'approved':
                if (!$driver->paymentMatchesOrder($payment, $order)) {
                    Log::warning('Mercado Pago: amount/currency mismatch', ['order_id' => $order->id, 'payment_id' => $payment['id'] ?? null]);
                    return 'ignored';
                }
                return $this->completeOrder($order, (string) $payment['id']);

            case 'rejected':
            case 'cancelled':
                // orders.status has no failed state: the order stays pending so the user can retry.
                Log::info('Mercado Pago: payment not approved', ['order_id' => $order->id, 'status' => $payment['status']]);
                return 'failed';

            case 'refunded':
            case 'charged_back':
                return $this->revokeAccess($order, (string) $payment['id']) ? 'refunded' : 'ignored';

            default:
                // pending / in_process / authorized: cash (OXXO), SPEI or a card under review. Remember how to finish it and tell the buyer once.
                \App\Services\PendingPayments::remember($order, $payment);
                return 'pending';
        }
    }

    protected function completeOrder(Order $order, string $paymentId): string
    {
        DB::transaction(function () use ($order, $paymentId) {
            $locked = Order::lockForUpdate()->find($order->id);
            if ($locked->status !== 'pending') {
                return;
            }
            $locked->update(['status' => 'complete', 'transaction_id' => $paymentId]);
            \App\Services\PendingPayments::forget($locked);
            dispatch_sync(new CompletePurchaseJob($locked));
        });

        return 'complete';
    }

    protected function revokeAccess(Order $order, string $paymentId): bool
    {
        if ((string) $order->transaction_id !== $paymentId) {
            return false;
        }

        return app(\App\Services\OrderRefundService::class)->revoke($order, 'mercadopago-webhook');
    }

    /** "Tu pago está pendiente": what to do next for cash/SPEI, or that the card payment is being reviewed. */
    public function pending(Request $request, Order $order)
    {
        abort_unless((int) $order->user_id === (int) auth()->id(), 403);

        if ($order->status !== 'pending') {
            return redirect()->route($order->status === 'refunded' ? 'courses.course-list' : 'thank-you', $order->status === 'refunded' ? [] : ['id' => $order->id]);
        }

        return response()->view('payments.pending', [
            'order' => $order,
            'info' => \App\Services\PendingPayments::info($order),
            'items' => $order->items()->pluck('title'),
        ]);
    }
}
