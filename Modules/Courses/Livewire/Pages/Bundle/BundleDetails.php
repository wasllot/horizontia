<?php

namespace Modules\Courses\Livewire\Pages\Bundle;

use App\Facades\Cart;
use Livewire\Component;
use Modules\Courses\Models\Bundle;
use Modules\Courses\Services\BundleService;

/** Public page of a bundle: its courses, the saving, and "Comprar paquete" (courses go to the cart at their shares). */
class BundleDetails extends Component
{
    public string $slug = '';

    public function mount($slug = null): void
    {
        $this->slug = (string) ($slug ?? request()->route('slug'));
        $this->bundle();   // 404 for drafts and unknown slugs
    }

    private function bundle(): Bundle
    {
        $admin = auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('sub_admin');

        return Bundle::with(['courses.pricing', 'courses.category', 'courses.subCategory', 'courses.thumbnail', 'courses.instructor.profile'])
            ->where('slug', $this->slug)->when(!$admin, fn ($q) => $q->active())->first() ?? abort(404);
    }

    private function put(): bool
    {
        if (!auth()->check()) {
            session(['url.intended' => route('courses.bundles.show', ['slug' => $this->slug])]);
            $this->redirect(route('login'));

            return false;
        }
        if (!auth()->user()->hasRole('student')) {
            $this->dispatch('showAlertMessage', type: 'error', message: __('courses::courses.only_student_can_add_to_cart'));

            return false;
        }
        $result = app(BundleService::class)->addToCart($this->bundle(), auth()->user());
        if (!$result['ok']) {
            $this->dispatch('showAlertMessage', type: 'error', message: $result['message']);

            return false;
        }

        return true;
    }

    public function buyNow()
    {
        if ($this->put()) {
            return redirect()->route('checkout');
        }
    }

    public function addToCart(): void
    {
        if ($this->put()) {
            $this->dispatch('cart-updated', cart_data: Cart::content(), discount: formatAmount(Cart::discount(), true), total: formatAmount(Cart::total(), true), subTotal: formatAmount(Cart::subtotal(), true), toggle_cart: 'open');
        }
    }

    public function render()
    {
        $bundle = $this->bundle();
        $service = app(BundleService::class);
        $user = auth()->user();
        $remaining = $service->remainingFor($user, $bundle);
        $shares = $service->shares($bundle, $remaining);

        return view('courses::livewire.bundle.bundle-details', [
            'bundle' => $bundle,
            'sellable' => $service->isSellable($bundle),
            'listPrice' => $service->listPrice($bundle),
            'savingsPercent' => $service->savingsPercent($bundle),
            'remaining' => $remaining,
            'owned' => $bundle->courses->count() - $remaining->count(),
            'yourPrice' => array_sum($shares),
            'inCart' => $user ? \App\Models\CartItem::where('user_id', $user->id)->get()->contains(fn ($l) => (int) ($l->options['bundle_id'] ?? 0) === $bundle->id) : false,
        ])->extends('layouts.frontend-app', ['pageTitle' => $bundle->title . ' · Paquete', 'pageDescription' => $bundle->subtitle ?: strip_tags((string) $bundle->description)]);
    }
}
