<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BookingService;
use Illuminate\Http\Request;
use App\Models\SlotBooking;
use App\Services\CartService;
use App\Http\Requests\Common\CartRequest\CartRequest;
use App\Traits\ApiResponser;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\CartResource;
use App\Models\User;
use App\Services\UserService;


class CartController extends Controller
{
    use ApiResponser;

    public function index()
    {
      
        $cartService   = new CartService();
        $items = $cartService->content();
        $total = $cartService->total();
        $subtotal = $cartService->subtotal();
        return $this->success(data: [
            'cartItems' => CartResource::collection($items),
            'total'     => $total,
            'subtotal'  => $subtotal
        ], message: __('api.cart_items_fetched_successfully'));
    }

    public function store(Request $request)
    {
        $response = isDemoSite();
        if($response ){
            return $this->error(data: null,message: __('general.demosite_res_txt'),code: Response::HTTP_FORBIDDEN);
        }

        $user = auth()->user();
        if($user->role == 'tutor'){
            return $this->error(data: null,message: __('api.unauthorized_access'),code: Response::HTTP_FORBIDDEN);
        }

        $currency               = setting('_general.currency');
        $currency_detail        = !empty( $currency)  ? currencyList($currency) : array();
        if( !empty($currency_detail['symbol']) ){

            $currency_symbol = $currency_detail['symbol'];
        }
        $timezone = getUserTimezone();
    
        $bookingService = new BookingService($user);
        $cartService = new CartService();
        $slot =  $bookingService->getSlotDetail($request->id);

        if (!empty($slot->subjectGroupSubjects->userSubjectGroup?->user_id)) {
            $userId = $slot->subjectGroupSubjects->userSubjectGroup?->user_id;
            $tutor = (new UserService($user))->getUser($userId);
        } else {
            $tutor = null;
        }
        
        if(!empty($slot) && !empty($tutor)){
            if( $slot->total_booked < $slot->spaces) {
                $bookedSlot = $bookingService->reservedBookingSlot($slot, $tutor);
                $data = [
                    'id' => $bookedSlot->id,
                    'slot_id' => $slot->id,
                    'tutor_id' => $tutor->id,
                    'tutor_name' => $tutor?->profile?->full_name,
                    'session_time' => parseToUserTz($slot->start_time, $timezone)->format('h:i a').' - '.parseToUserTz($slot->end_time, $timezone)->format('h:i a'),
                    'subject_group' => $slot->subjectGroupSubjects?->userSubjectGroup?->group?->name,
                    'subject' => $slot->subjectGroupSubjects?->subject?->name,
                    'image' => $slot->subjectGroupSubjects?->image,
                    'currency_symbol' => $currency_symbol,
                    'price' => number_format($slot->session_fee, 2),
                ];

                $cartItem      = $cartService->add($data['id'], SlotBooking::class , $data['subject'], 1, $slot->session_fee,$data);
                $total         = $cartService->total();
                $subtotal      = $cartService->subtotal();
                return $this->success(data: [
                    'cartItem'  => new CartResource($cartItem),
                    'total'     => $total,
                    'subtotal'  => $subtotal
                ], message: __('api.cart_items_fetched_successfully'));
            } else {
                return $this->error(message: __('api.not_available_slot'));
            }
        }
        return $this->error(message: __('api.slot_not_found'));
    }

    public function destroy($id)
    {
        $response = isDemoSite();
        if( $response ){
            return $this->error(data: null,message: __('general.demosite_res_txt'),code: Response::HTTP_FORBIDDEN);
        }
        $user = auth()->user();
        if($user->role == 'tutor'){
            return $this->error(data: null,message: __('api.unauthorized_access'),code: Response::HTTP_FORBIDDEN);
        }
        $cartService = new CartService();
        $bookingService = new BookingService(auth()->user());
        $cartItem = $cartService->get($id, SlotBooking::class);
        $bookingService->removeReservedBooking($id);
        if($cartItem){
            $cartService->remove($id, SlotBooking::class);
            return $this->success(message: __('api.item_removed_from_cart_successfully'));
        }
        return $this->error(message: __('api.item_not_found_in_cart'));
    }
}
