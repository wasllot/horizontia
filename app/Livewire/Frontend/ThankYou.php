<?php

namespace App\Livewire\Frontend;

use App\Models\OrderItem;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ThankYou extends Component
{
    public $orderId;

    public function mount($id)
    {
        $this->orderId = $id;
    }

    #[Layout('layouts.guest')]
    public function render()
    {

        $orderItem = OrderItem::where('order_id', $this->orderId)->get();
        if($orderItem->count() > 0){
            return view('livewire.frontend.thank-you', ['orderItem' => $orderItem, 'onsiteDates' => $this->onsiteDates($orderItem)]);
        }
        abort('404');
    }

    /** In-person dates bought in this order (only shown to the buyer: they include the venue). */
    private function onsiteDates($orderItems)
    {
        if (!auth()->check() || \App\Models\Order::whereKey($this->orderId)->value('user_id') !== auth()->id()) {
            return collect();
        }

        return $orderItems->where('orderable_type', \Modules\Courses\Models\Course::class)
            ->map(fn ($i) => !empty($i->options['session_id']) ? \Modules\Courses\Models\CourseSession::where('course_id', $i->orderable_id)->with('course')->find($i->options['session_id']) : null)
            ->filter()->values();
    }
}
