<?php

namespace App\Livewire\Frontend;

use Modules\LaraPayease\Facades\PaymentDriver;
use App\Livewire\Forms\Frontend\OrderForm;
use App\Models\Country;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use App\Facades\Cart;
use App\Services\ProfileService;
use App\Jobs\CompletePurchaseJob;
use App\Services\SiteService;
use Illuminate\Support\Str;
use App\Models\SlotBooking;
use App\Services\BillingService;
use App\Services\BookingService;
use App\Services\OrderService;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Nwidart\Modules\Facades\Module;

class Checkout extends Component
{
    public OrderForm $form;

    public $user;
    public $methods             = [];
    public $address;
    public $content;
    public $countries           = [];
    #[Locked]
    public $payAmount;
    #[Locked]
    public $discount;
    #[Locked]
    public $subTotal;
    #[Locked]
    public $totalAmount         = '';
    #[Locked]
    public $walletBalance       = '';
    public $billingDetail;
    public $payment_methods     = [];
    public $useWalletBalance    = false;
    public $checkoutReady       = true;
    public $subscriptions, $chosenSubscription, $invalidCartItem;
    #[Locked]
    public $orderDetail;
    public $coupon;
    public  $available_payment_methods           = [];
    private ?OrderService $orderService   = null;
    private ?WalletService $walletService   = null;
    private ?BillingService $billingService = null;
    private ?ProfileService $profileService = null;
    private ?SiteService $siteService = null;
    public function boot() {
        $this->user            = Auth::user();
        $this->orderService   = new OrderService();
        $this->siteService   = new SiteService();
        $this->profileService   = new ProfileService(Auth::user()?->id);
        $this->walletService   = new WalletService();
        $this->billingService   = new BillingService(Auth::user());

    }

    public function mount()
    {
        if (isPrivateAcademy()) {
            $this->mountAcademy();
            return;
        }
        $this->dispatch('initSelect2', target: '.am-select2' );
        $order_id = session('order_id') ?? '';
        if($order_id){
            $this->orderDetail      = $this->orderService->getOrderDetail($order_id);
        } else {
            $this->billingDetail      = $this->billingService->getBillingDetail();
            $this->address            = $this->billingService->getUserAddress();    
        }
        $gateways                   = $this->rearrangeArray(PaymentDriver::supportedGateways());
        $this->methods              = array_merge($this->methods, $gateways);
        $this->walletBalance        = $this->walletService->getWalletAmount(Auth::user()->id);
        $this->countries            = $this->siteService->getCountries();
        if(Module::has('subscriptions') && Module::isEnabled('subscriptions')){
            $this->subscriptions = (new \Modules\Subscriptions\Services\SubscriptionService())->getUserSubscription(userId: Auth::user()->id);
        }

        if(!empty($this->orderDetail)){
            $billingData = (object) [
                "first_name"        => $this->orderDetail->first_name ?? '',
                "last_name"         => $this->orderDetail->last_name ?? '',
                "company"           => $this->orderDetail->company ?? '',
                "phone"             => $this->orderDetail->phone ?? '',
                "payment_method"    => $this->orderDetail->payment_method ?? '',
                "email"             => $this->orderDetail->email ?? ''
            ];

            $address = (object) [
                "country_id"        => $this->orderDetail->country ?? '',
                "state"             => $this->orderDetail->state ?? '',
                "zipcode"           => $this->orderDetail->postal_code ?? '',
                "city"              => $this->orderDetail->city ?? ''
            ];

            $this->form->setInfo($billingData);
            $this->form->setUserAddress($address);
            $this->chosenSubscription = $this->orderDetail->subscription_id;

        } elseif(!empty($this->billingDetail) && !empty($this->address)) {

            $this->form->setInfo($this->billingDetail);
            $this->form->setUserAddress($this->address,false);
            $this->form->paymentMethod = setting('admin_settings.default_payment_method') ?? '';
        } else {
            $this->address            = $this->profileService->getUserAddress();
            $profileData = (object) [
                "first_name"        => $this->user->profile->first_name ?? '',
                "last_name"         => $this->user->profile->last_name ?? '',
                "email"             => $this->user->email ?? ''
            ];
            $state = $this->siteService->getState($this->address?->state_id);
            $addressData = (object) [
                "country_id"        => $this->address?->country_id ?? '',
                "state"             => $state->name ?? '',
                "zipcode"           => $this->address?->zipcode ?? '',
                "city"              => $this->address?->city ?? ''
            ];
            $this->form->setInfo($profileData);
            $this->form->setUserAddress($addressData);
        }
        $this->prepareCartAmount();
        if (!empty($this->chosenSubscription)){
            $this->updatedChosenSubscription($this->chosenSubscription);
        }
        $this->getavailablePaymentMethods();
    }

    /**
     * One-page checkout for the private academy: no address, no wallet, no select2/countries lookups.
     * Prefills name/e-mail (from an unpaid order being retried or from the profile) and preselects a payment method.
     */
    protected function mountAcademy(): void
    {
        $orderId = session('order_id');
        $this->orderDetail = $orderId ? $this->orderService->getOrderDetail($orderId) : null;

        if (!empty($this->orderDetail)) {
            $this->form->setInfo((object) [
                'first_name' => $this->orderDetail->first_name, 'last_name' => $this->orderDetail->last_name,
                'email' => $this->orderDetail->email, 'phone' => $this->orderDetail->phone, 'payment_method' => $this->orderDetail->payment_method,
            ]);
        } else {
            $profile = $this->user->profile;
            $this->form->setInfo((object) ['first_name' => $profile->first_name ?? '', 'last_name' => $profile->last_name ?? '', 'email' => $this->user->email]);
        }

        $this->walletBalance = 0;
        $this->methods = array_merge($this->methods, $this->rearrangeArray(PaymentDriver::supportedGateways()));
        $this->prepareCartAmount();
        $this->getavailablePaymentMethods();

        $enabled = collect($this->available_payment_methods)->filter(fn ($m) => ($m['status'] ?? 'off') === 'on')->keys();
        if (!$enabled->contains($this->form->paymentMethod)) {
            // Mercado Pago is the academy's main gateway; otherwise the admin's default, otherwise the first enabled one.
            $default = setting('admin_settings.default_payment_method');
            $this->form->paymentMethod = $enabled->contains('mercadopago') ? 'mercadopago' : ($enabled->contains($default) ? $default : (string) $enabled->first());
        }
    }

    public function getAvailablePaymentMethods()
    {
        $payment_methods = setting('admin_settings.payment_method');
        if (!is_array($payment_methods)) {
            $payment_methods = [];
        }
        if (!empty($payment_methods)) {
            foreach ($payment_methods as $type => $value) {
                if (array_key_exists($type, $this->methods)) {
                    $this->available_payment_methods[$type] = $value;
                }
            }
        }
    }

    #[Layout('layouts.guest')]
    public function render()
    {
        $this->form->walletBalance      = $this->walletBalance;
        $this->form->useWalletBalance   = $this->useWalletBalance;

        if (isPrivateAcademy()) {
            // In-person dates can sell out while the buyer is on this page: warn before they try to pay.
            $dateProblem = app(\Modules\Courses\Services\OnsiteSeatsService::class)->checkCart($this->content ?? []);

            return view('livewire.frontend.checkout-academy', ['dateProblem' => $dateProblem]);
        }

        return view('livewire.frontend.checkout');
    }

    protected function prepareCartAmount()
    {
        $this->content                  = Cart::content();
        $this->subTotal                 = Cart::subtotal();
        $this->discount                 = Cart::discount();
        $this->totalAmount              = Cart::total();
        $this->form->totalAmount        = $this->totalAmount;
        $this->payAmount                = $this->totalAmount;
    }

    public function updatedUseWalletBalance($value)
    {
        if (isPrivateAcademy()) {
            // The wallet belongs to the tutor marketplace; a client-sent toggle must not enable it.
            $this->useWalletBalance = false;
            $this->payAmount = $this->totalAmount;
            return;
        }
        if($value){
            $this->payAmount =  $this->totalAmount - $this->walletBalance ;
        } else {
            $this->payAmount = $this->totalAmount;
        }
    }

    public function updatedForm($value, $key)
    {
        if ($key == 'paymentMethod') {
            $this->chosenSubscription = null;
            $this->checkoutReady = true;
            $this->payAmount = $this->totalAmount;
        }
    }

    public function updatedChosenSubscription($value)
    {
        $this->useWalletBalance = false;
        $subscriptionDiscount = 0;
        $this->form->paymentMethod = 'subscription';
        if (Module::has('subscriptions') && Module::isEnabled('subscriptions') && !empty($value)) {
            $choosedSubscription = $this->subscriptions->where('subscription_id','=',$value)->first();
            if(!empty($choosedSubscription)){
               foreach($this->content as $item){
                    if ($item['cartable_type'] == SlotBooking::class &&
                        (
                            setting('_lernen.subscription_sessions_allowed') == 'all' ||
                            (
                                setting('_lernen.subscription_sessions_allowed') == 'tutor' &&
                                ($item['options']['allowed_for_subscriptions'] ?? 0) == 1
                            ) 
                        ) &&
                        ($choosedSubscription?->remaining_credits['sessions'] ?? 0) > 0 
                    ){
                        $subscriptionDiscount += $item['price'];
                        $this->checkoutReady   = true;
                        if (!empty($item['options']['discount_code']) ) {
                            $this->removeCoupon($item['options']['discount_code']);
                        }
                    } elseif (Module::has('courses') && Module::isEnabled('courses') && $item['cartable_type'] == \Modules\Courses\Models\Course::class && 
                        ($choosedSubscription?->remaining_credits['courses'] ?? 0) > 0
                    ){
                        $this->checkoutReady   = true;
                        $subscriptionDiscount += $item['price'];
                        if (!empty($item['options']['discount_code']) ) {
                            $this->removeCoupon($item['options']['discount_code']);
                        }
                    } else {
                        $this->checkoutReady = false;
                        $this->invalidCartItem = $item;
                        break;
                    }
               }
            }
        }
        if($subscriptionDiscount <= $this->payAmount){
            $this->payAmount = $this->payAmount - $subscriptionDiscount;
        } else {
            $this->payAmount = 0;
        }
    }

    public function rearrangeArray($array) {
        return array_map(function($details) {
            if (isset($details['keys'])) {
                $details = array_merge($details, $details['keys']);
                unset($details['keys']);
            }
            if (isset($details['ipn_url_type'])) {
                unset($details['ipn_url_type']);
            }
            return $details;
        }, $array);
    }

    /**
     * Public Livewire state is client-editable, so every amount used to build the
     * order and the payment is re-derived here from the server-side cart and wallet.
     */
    protected function refreshTrustedAmounts(): void
    {
        if (isPrivateAcademy()) {
            $this->useWalletBalance = false;
        }
        $this->prepareCartAmount();
        $this->walletBalance        = $this->walletService->getWalletAmount(Auth::user()->id);
        $this->form->walletBalance  = $this->walletBalance;

        if ($this->useWalletBalance) {
            $this->payAmount = max(0, $this->totalAmount - $this->walletBalance);
        }

        if (Module::has('subscriptions') && Module::isEnabled('subscriptions') && !empty($this->chosenSubscription)) {
            $this->subscriptions = (new \Modules\Subscriptions\Services\SubscriptionService())->getUserSubscription(userId: Auth::user()->id);
            $this->updatedChosenSubscription($this->chosenSubscription);
        }
    }

    public function updateInfo()
    {
        try {
            $this->refreshTrustedAmounts();
            $this->getavailablePaymentMethods();
            if ($this->payAmount > 0 && ($this->available_payment_methods[$this->form->paymentMethod]['status'] ?? 'off') !== 'on') {
                $this->dispatch('showAlertMessage', type: 'error', message: __('general.payment_cancelled_desc'));
                return;
            }
            if($this->checkoutReady == false){
                $this->dispatch('showAlertMessage', type: 'error',  message: __('subscriptions::subscription.not_applicable_to_cart_item', ['name' => $this->invalidCartItem['name'] ?? '']));
                return;
            }
            // Bundles: every course present at its current share, otherwise the cart is rebuilt and the buyer reviews it.
            if ($bundleMessage = app(\Modules\Courses\Services\BundleService::class)->validateCart($this->content, Auth::user())) {
                $this->prepareCartAmount();
                $this->dispatch('showAlertMessage', type: 'error', message: $bundleMessage);
                return;
            }
            DB::beginTransaction();
            // In-person courses: the chosen date must still be sellable. The row lock makes concurrent checkouts wait for each other.
            if ($problem = app(\Modules\Courses\Services\OnsiteSeatsService::class)->checkCart($this->content, lock: true)) {
                DB::rollBack();
                $this->dispatch('showAlertMessage', type: 'error', message: ($problem[2] ?? '') . ': ' . $problem[1]);
                return;
            }
            $orderItems = [];
            $data = $this->form->updateInfo();
            $applied = Cart::appliedCoupon();
            $data['coupon_code'] = $applied['coupon']->code ?? null;
            $data['discount_amount'] = $applied['discount'] ?? 0;
            if(Module::has('subscriptions') && Module::isEnabled('subscriptions') && !empty($this->chosenSubscription)){
                $data['subscription_id'] = $this->chosenSubscription;
            }
            if(!empty($this->orderDetail)){
                $orderDetail = $this->orderService->updateOrder($this->orderDetail,$data);
            } else {
                $orderDetail = $this->orderService->createOrder($data);
            }
            session(['order_id' => $orderDetail->id]);
            if ($this->form->whatsappOptin && \App\Services\WhatsAppService::enabled()) {
                app(\App\Services\WhatsAppService::class)->optIn(Auth::user(), $this->form->phone);   // el consentimiento queda guardado con la compra
            }

            foreach ($this->content as $item) {
                $orderItemData = [
                    'order_id'       => $orderDetail->id,
                    'title'          => $item['name'],
                    'quantity'       => $item['qty'],
                    'options'        => $item['options'],
                    'price'          => $item['price'],
                    'total'          => max(0, (float)$item['qty'] * (float)$item['price'] - (float) ($applied['shares'][(int) $item['cartable_id']] ?? 0)),
                    'orderable_id'   => $item['cartable_id'],
                    'orderable_type' => $item['cartable_type'],
                ];
                if(Module::has('kupondeal') && Module::isEnabled('kupondeal') && !empty($item['discount_amount'])){
                    $orderItemData['discount_amount'] = $item['discount_amount'];
                }
                $orderItems[] = $orderItemData;
            }

            $this->orderService->storeOrderItems($orderDetail->id,$orderItems);
            if ($this->useWalletBalance ) {
                if ($this->walletBalance >= $this->totalAmount) {
                    $this->walletService->deductFunds(Auth::user()->id, $this->totalAmount, 'deduct_booking', $orderDetail->id);
                } else {
                    $this->walletService->deductFunds(Auth::user()->id, $this->walletBalance, 'deduct_booking', $orderDetail->id);
                }
            }
            if(Module::has('subscriptions') && Module::isEnabled('subscriptions') && !empty($this->chosenSubscription)){
                $this->orderService->updateOrder($orderDetail,['subscription_id' => $this->chosenSubscription]);
            }
            if(($this->useWalletBalance && ($this->walletBalance >= $this->totalAmount) ) || $this->payAmount == 0){
                $orderDetail = $this->orderService->updateOrder($orderDetail,['status'=>'complete']);
                DB::commit();
                session()->forget('order_id');
                dispatch_sync(new CompletePurchaseJob($orderDetail));
                Cart::clear();
                redirect()->route('thank-you', ['id' => $orderDetail->id]);
            } else {
                DB::commit();
                $ipnUrl = PaymentDriver::getIpnUrl($this->form->paymentMethod);
                session(['payment_data' =>  [
                    'amount'        => $this->payAmount,
                    'title'         => setting('_general.site_name') ?? env('APP_NAME', 'Horizontia') . ' Purchase',
                    'description'   => setting('_general.site_name') ?? env('APP_NAME', 'Horizontia') . ' Purchase Order Confirmation for reference #'.$orderDetail->id,
                    'ipn_url'       => !empty($ipnUrl) ? route($ipnUrl, ['payment_method' => $this->form->paymentMethod]) : url('/'),
                    'order_id'      => $orderDetail->id,
                    'track'         => Str::random(36),
                    'cancel_url'    => route('checkout'),
                    'success_url'   => route('thank-you',['id' => $orderDetail->id]),
                    'email'         => $orderDetail->email,
                    'name'          => $orderDetail->first_name,
                    'payment_type'  => $this->form->paymentMethod,
                ]]);
                return redirect()->route('payment.process', ['gateway' => $this->form->paymentMethod]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

    }

    public function removeCart($id, $type)
    {
        if($type == 'App\Models\SlotBooking'){
            (new BookingService($this->user))->removeReservedBooking($id);
        }
        Cart::remove($id, $type);
        $this->prepareCartAmount();
        $this->dispatch('cart-updated', cart_data: Cart::content(), discount: formatAmount(Cart::discount(), true), total: formatAmount(Cart::total(), true), subTotal: formatAmount(Cart::subtotal(), true));
    }

    public function removeCoupon($couponCode = null)
    {
        if (Module::has('kupondeal') && Module::isEnabled('kupondeal') && !empty($couponCode)) {
            $response = \Modules\KuponDeal\Facades\KuponDeal::removeCoupon($couponCode);
            $this->dispatch('showAlertMessage', type: $response['status'],  message: $response['message']);
        } else {
            app(\App\Services\CouponService::class)->forget();
        }
        $this->prepareCartAmount();
        $this->dispatch('cart-updated', cart_data: Cart::content(), discount: formatAmount(Cart::discount(), true), total: formatAmount(Cart::total(), true), subTotal: formatAmount(Cart::subtotal(), true));
    }

    /** Códigos de descuento de la academia (App\Services\CouponService); se revalidan en cada cálculo del carrito. */
    public function applyCoupon()
    {
        $this->validate(['coupon' => 'required|string|max:40'], ['coupon.required' => 'Escribe tu código de descuento.']);

        if (!empty($this->chosenSubscription)) {
            $this->dispatch('showAlertMessage', type: 'error',  message: __('subscriptions::subscription.cupon_not_applicable_with_subscription'));
            return;
        }

        $result = app(\App\Services\CouponService::class)->apply($this->coupon, Auth::user(), Cart::content());
        $this->dispatch('showAlertMessage', type: $result['ok'] ? 'success' : 'error', message: $result['ok'] ? 'Código aplicado: ' . strip_tags(formatAmount($result['discount'], true)) . ' de descuento.' : $result['message']);
        if ($result['ok']) {
            $this->reset('coupon');
        }
        $this->prepareCartAmount();
    }
}
