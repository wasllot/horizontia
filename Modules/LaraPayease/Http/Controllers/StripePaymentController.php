<?php

namespace Modules\LaraPayease\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StripePaymentController extends Controller{

    public function prepareCharge(Request $request){
        try{
            // Never trust the client: the order, amount and URLs come from the payment
            // data stored server-side by SiteController::preparePayment().
            $paymentData = session('payment_data');
            if (empty($paymentData) || (string) $paymentData['order_id'] !== (string) $request->input('order_id')) {
                return response()->json(['msg' => __('general.payment_cancelled_desc'), 'type' => 'danger'], 422);
            }

            $gateway = getGatewayObject('stripe');
            if (empty($gateway)) {
                return response()->json(['msg' => __('general.payment_cancelled_desc'), 'type' => 'danger'], 422);
            }

            $stripeSession = $gateway->prepareCharge($paymentData);
            return response()->json(['id' => $stripeSession['id']]);
        }catch(\Exception $e){
            report($e);
            return response()->json(['msg' => __('general.something_went_wrong'), 'type' => 'danger'], 500);
        }
    }

}
