<?php

namespace App\Http\Controllers;

use Modules\LaraPayease\Facades\PaymentDriver;
use App\Facades\Cart;
use App\Jobs\CompleteBookingJob;
use App\Jobs\CompletePurchaseJob;
use App\Livewire\Actions\Logout;
use App\Models\Order;
use App\Models\SlotBooking;
use App\Services\BookingService;
use App\Services\GoogleCalender;
use App\Services\OrderService;
use App\Services\UserService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SiteController extends Controller
{
    private $userService             = null;
    public  $googleSetToken          = null;
    private $googleCalenderService   = null;

    public function getGoogleToken(Request $request) {

        $this->googleCalenderService    = new GoogleCalender(Auth::user());
        $this->userService              = new UserService(Auth::user());
        $googleToken                    = $this->googleCalenderService->getAccessTokenInfo($request->get('code'));

        if(!empty($googleToken['error'])){
            return redirect()->route('tutor.profile.account-settings')->with('error',__('passwords.failed_retrieve_Google_token'));
        }

        $primaryCalendar         = $this->googleCalenderService->getUserPrimaryCalendar($googleToken['access_token']);
        $primaryCalendar['data']['minutes'] = 30;
        $this->userService->setAccountSetting(['google_access_token','google_calendar_info'],[$googleToken,$primaryCalendar['data']]);
        return redirect()->route(Auth::user()->role.'.profile.account-settings')->with('success',__('passwords.connect_calender'));
    }

    public function completeBooking($id, BookingService $bookingService) {
        $booking = $bookingService->getBookingById($id);
        if(empty($booking)) {
            return redirect()->route('student.bookings')->with('error',__('general.booking_not_found'));
        }
        if($booking->status != 'active' || Carbon::parse($booking->end_time)->isFuture()) {
            return redirect()->route('student.bookings')->with('error',__('calendar.unable_to_complete_booking'));
        }
        $bookingService->updateBooking($booking, ['status' => 'completed']);
        (new WalletService())->makePendingFundsAvailable($booking->tutor_id, ($booking->session_fee - $booking?->orderItem?->platform_fee), $booking?->orderItem?->order_id);
        dispatch(new CompleteBookingJob($booking));
        return redirect()->route('student.bookings')->with('success',__('calendar.booking_completed'));
    }

    public function processPayment($gateway){

        $paymentData = session('payment_data');
        if (empty($paymentData)) {
            return redirect()->route('checkout')->with('error',__('general.payment_cancelled_desc'));
        }

        $gatewayObj = getGatewayObject($gateway);
        if(!empty($gatewayObj)) {
            $response = $gatewayObj->chargeCustomer($paymentData);
            if(is_array($response) && array_key_exists('message', $response)){
                return redirect()->route('checkout')->with('error', $response['message']);
            }
            return $response;
        }
    }

    public function payfastWebhook(Request $request){
        header('HTTP/1.0 200 OK');
        flush();
        $gatewayObj = getGatewayObject('payfast');
        if(!empty($gatewayObj)) {
            $paymentData = $gatewayObj->paymentResponse($request->all());
            if (!empty($paymentData) && $paymentData['status'] == Response::HTTP_OK) {
                $this->paymentSuccess($request, webhook: true);
            }
        }
    }

    public function paymentSuccess(Request $request, $webhook = false) {
        $orderServices   = new OrderService();
        if($request['payment_method'] == 'payfast' && empty($webhook)) {
            Cart::clear();
            session()->forget('order_id');
            $request->session()->forget('payment_data');
            if ($request->source == 'api' && $request->upi) {
                return response()->json(['success' => true, 'message' => __('general.payment_successful')], Response::HTTP_OK);
            }
            return redirect()->route('thank-you', ['id' => Order::latest()->first()?->id]);
        } else {
            $gatewayObj = getGatewayObject(empty($request['payment_method']) ? 'payfast' : $request['payment_method']);
            if(!empty($gatewayObj)) {
                $paymentData = $gatewayObj->paymentResponse($request->all());
                if (!empty($paymentData) && $paymentData['status'] == Response::HTTP_OK) {
                    $orderDetail   = $orderServices->getOrderDetail($paymentData['data']['order_id']);
                    $status = $orderServices->updateOrder($orderDetail ,['status'=>'complete','transaction_id'=>$paymentData['data']['transaction_id']]);
                    if($status){
                        dispatch(new CompletePurchaseJob($orderDetail));
                        $request->session()->forget('payment_data');
                        Cart::clear();
                        session()->forget('order_id');
                        
                        if ($request->source == 'api' && $request->upi) {
                            return response()->json(['success' => true, 'message' => __('general.payment_successful')], Response::HTTP_OK);
                        }
                        return redirect()->route('thank-you', ['id' => $paymentData['data']['order_id']]);
                    }
                } else {
                    if ($request->source == 'api' && $request->upi) {
                        return response()->json(['success' => true, 'message' => __('general.payment_cancelled')], Response::HTTP_BAD_REQUEST);
                    }
                    return redirect(route('checkout'))->with('error',__('general.payment_cancelled_desc'));
                }
            }
        }
    }

    public function removeCart(Request $request)
    {
        try {
            $bookingService = new BookingService();
            $itemType = !empty($request->cartable_type) ? $request->cartable_type : SlotBooking::class;

            if($itemType == SlotBooking::class) {
                $bookingService->removeReservedBooking($request->cartable_id);
            }

            Cart::remove($request->cartable_id, $itemType);
            $cartData = Cart::content();
            $total = formatAmount(Cart::total(), true);
            $subTotal = formatAmount(Cart::subtotal(), true);
            return response()->json([
                'success'       => true,
                'cart_data'     => $cartData,
                'total'         => $total,
                'subTotal'      => $subTotal,
                'discount'      => formatAmount(Cart::discount(), true),
                'toggle_cart'   => 'open',
            ]);
        } catch (\Exception $e) {
            Log::error('Error removing item from cart: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error removing item'], 500);
        }
    }

    public function logout(Logout $logout){
        $logout();
        return redirect('/login');
    }

    public function preparePayment($id){
        $orderDetail = (new OrderService())->getOrderDetailForPayment($id);
        if(empty($orderDetail)) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }
        if($orderDetail->status != 'pending') {
            return response()->json(['success' => false, 'message' => 'Order not in payable state'], 400);
        }
        $ipnUrl = PaymentDriver::getIpnUrl($orderDetail->payment_method);
        session(['payment_data' =>  [
            'amount'        => $orderDetail->amount - $orderDetail->used_wallet_balance,
            'title'         => 'Lernen Purchase',
            'description'   => 'Lernen Purchase Order Confirmation for reference #'.$orderDetail->id,
            'ipn_url'       => !empty($ipnUrl) ? route($ipnUrl, ['payment_method' => $orderDetail->payment_method,'upi' => $orderDetail->unique_payment_id, 'source' => request()->get('source') ?? 'web']) : url('/'),
            'order_id'      => $orderDetail->id,
            'track'         => $orderDetail->unique_payment_id,
            'cancel_url'    => route('checkout'),
            'success_url'   => route('thank-you',['id' => $orderDetail->id]),
            'email'         => $orderDetail->email,
            'name'          => $orderDetail->first_name,
            'payment_type'  => $orderDetail->payment_method,
        ]]);

        return redirect()->route('payment.process', ['gateway' => $orderDetail->payment_method]);
    }

    /**
     * Switch Language Via Session.
     * @param \Illuminate\Http\Request.
     */

    public function switchLang(Request $request){
        $locale = $request->get('am-locale');
        $translatedLangs = array_keys(getTranslatedLanguages());
        if (in_array($locale, $translatedLangs)) {
            session()->put('locale', $locale);
        }
        return redirect()->back();
    }

    /**
     * Switch currency Via Session.
     * @param \Illuminate\Http\Request.
     */

    public function switchCurrency(Request $request){
        $currency = $request->get('am-currency');
        if (array_key_exists($currency, currencyList())) {
            session()->put('selected_currency', $currency);
        }
        return redirect()->back();
    }
}



