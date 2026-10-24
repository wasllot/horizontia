<?php

namespace App\Livewire\Pages\Student;

use App\Casts\OrderStatusCast;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Services\RefundRequestService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/** «Mis facturas» > Garantía de reembolso: pedir la devolución de un pedido reciente con las reglas de la política. */
class RefundRequests extends Component
{
    public ?int $openOrder = null;
    public string $reason = '';

    private function myOrder(int $id): Order
    {
        return Order::where('user_id', Auth::id())->findOrFail($id);
    }

    public function open(int $orderId): void
    {
        $order = $this->myOrder($orderId);
        $check = app(RefundRequestService::class)->eligibility(Auth::user(), $order);
        if (!$check['ok']) {
            $this->toast($check['reason'], 'error');

            return;
        }
        $this->openOrder = $order->id;
        $this->reason = '';
        $this->resetErrorBag();
    }

    public function cancel(): void
    {
        $this->reset('openOrder', 'reason');
        $this->resetErrorBag();
    }

    public function submit(): void
    {
        abort_if($this->openOrder === null, 422);
        $order = $this->myOrder($this->openOrder);
        $result = app(RefundRequestService::class)->request(Auth::user(), $order, $this->reason);

        if (!$result['ok']) {
            $this->addError('reason', $result['message']);

            return;
        }
        $this->cancel();
        $this->toast($result['message']);
    }

    private function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('showAlertMessage', type: $type, title: $type === 'success' ? __('general.success_title') : 'Atención', message: $message);
    }

    public function render()
    {
        $svc = app(RefundRequestService::class);
        $window = max(1, (int) config('academy.refund_days')) + 30;   // un mes más para que se vea cómo terminó

        $rows = Order::query()->where('user_id', Auth::id())->where('amount', '>', 0)
            ->whereIn('status', [OrderStatusCast::$statuses['complete'], OrderStatusCast::$statuses['refunded']])
            ->where('created_at', '>=', now()->subDays($window))->with('items')->latest('id')->limit(8)->get()
            ->map(function (Order $o) use ($svc) {
                $last = RefundRequest::where('order_id', $o->id)->latest('id')->first();

                return (object) ['order' => $o, 'request' => $last, 'check' => $svc->eligibility(Auth::user(), $o)];
            });

        return view('livewire.pages.student.refund-requests', ['rows' => $rows, 'days' => (int) config('academy.refund_days')]);
    }
}
