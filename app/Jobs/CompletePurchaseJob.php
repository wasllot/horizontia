<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\SlotBooking;
use App\Models\User;
use App\Services\BookingService;
use App\Services\OrderService;
use App\Services\WalletService;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Nwidart\Modules\Facades\Module;

class CompletePurchaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Order $order;
    protected BookingService $bookingService;
    protected OrderService $orderService;
    protected WalletService $walletService;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order            = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(BookingService $bookingService, OrderService $orderService, WalletService $walletService): void
    {
        try {
            $this->bookingService   = $bookingService;
            $this->orderService     = $orderService;
            $this->walletService    = $walletService;
            $studentSubscription = $tutorSubscriptions = null;
            $studentRemainingCredits = $tutorRemainingCredits = [];
            if (Module::has('subscriptions') && Module::isEnabled('subscriptions') && !empty($this->order->subscription_id)) {
               $studentSubscription = (new \Modules\Subscriptions\Services\SubscriptionService())->getUserSubscription(userId:$this->order->user_id, subscriptionId: $this->order->subscription_id);
               $studentRemainingCredits = $studentSubscription?->remaining_credits ?? [];
            }
            $tutorBookings = $tutorFunds = [];

            app(\App\Services\CouponService::class)->redeem($this->order);
            app(\App\Services\WhatsAppService::class)->purchaseConfirmed($this->order);

            DB::beginTransaction();
            if (!empty($this->order->items)) {
                foreach($this->order->items as $item) {
                    if ($item->orderable instanceof SlotBooking) {

                        $this->bookingService->updateBooking($item->orderable, ['status' => 'active']);
                        $this->bookingService->addBookingLog($item->orderable, [
                            'activityable_id'   => $item->orderable->student_id,
                            'activityable_type' => User::class,
                            'type'              => 'active'
                        ]);

                        //Calculate tutor funds
                        $platformFee = number_format(getCommission($item->price), 2);
                        $tutorEarning = $item->price - $platformFee;
                        
                        if (Module::has('subscriptions') && Module::isEnabled('subscriptions')) {
                            if (!empty($this->order->subscription_id)) {
                                if ( !empty($studentSubscription) && ($studentRemainingCredits['sessions'] ?? 0) > 0) {
                                    $studentRemainingCredits['sessions'] = $studentRemainingCredits['sessions'] - 1;
                                    $tutorEarning = (new \Modules\Subscriptions\Services\SubscriptionService())->getSubscriptionTutorPayout($studentSubscription);
                                    $platformFee = 0;
                                }   
                            } else {
                                $tutorSubscription = (new \Modules\Subscriptions\Services\SubscriptionService())->getUserSubscription(userId:$item->orderable?->bookee?->id)->first();
                                $tutorRemainingCredits[$tutorSubscription?->id] = $tutorSubscription?->remaining_credits ?? [];
                                if(!empty($tutorSubscription) && ($tutorRemainingCredits[$tutorSubscription?->id]['sessions'] ?? 0) > 0) {
                                    $tutorRemainingCredits[$tutorSubscription?->id]['sessions'] = $tutorRemainingCredits[$tutorSubscription?->id]['sessions'] - 1;
                                    $tutorEarning = $item->price;
                                    $platformFee = 0;
                                }
                                $tutorSubscriptions[] = $tutorSubscription;
                            }
                        }

                        //Update platform fee in order_items table
                        $this->orderService->updateOrderItem($item, ['platform_fee'=> $platformFee, 'options' => array_merge($item->options, ['tutor_payout' => $tutorEarning])]);
                        $tutorFunds[$item->orderable?->bookee?->id] = ($tutorFunds[$item->orderable?->bookee?->id]??0) + $tutorEarning ;

                        //Update tutorBookings
                        $tutorBookings[$item->orderable?->bookee?->id][] = $item;

                        $this->bookingService->createBookingEventGoogleCalendar($item->orderable);
                        $this->bookingService->createSlotEventGoogleCalendar($item->orderable);
                        $this->bookingService->createMeetingLink($item->orderable);
                        // Calculate delay until the end time of the booking
                        $endTime = $item->orderable?->end_time;
                        $delay = now()->diffInSeconds($endTime, false);
                        if ($delay > 0) {
                            dispatch(new SendNotificationJob('bookingCompletionRequest',$item->orderable?->booker, [
                                'tutorName'           => $item->orderable?->tutor?->full_name,
                                'userName'            => $item->orderable?->student?->full_name,
                                'sessionDateTime'     => $this->bookingService->getBookingTime($item->orderable, 'booker'),
                                'completeBookingLink' => route('student.complete-booking', $item->orderable?->id),
                                'days'                => setting('_lernen.complete_booking_after_days') ?? 3    
                            ]))->delay($delay);
                            
                            dispatch(new SendDbNotificationJob('bookingCompletionRequest', $item->orderable?->booker, [
                                'tutorName'           => $item->orderable?->tutor?->full_name,
                                'sessionDateTime'     => $this->bookingService->getBookingTime($item->orderable, 'booker'),
                                'completeBookingLink' => route('student.complete-booking', $item->orderable?->id),
                                'days'                => setting('_lernen.complete_booking_after_days') ?? 3    
                            ]))->delay($delay);
                        }

                        $completeBookingDelay = Carbon::parse($endTime)->addDays((int)setting('_lernen.complete_booking_after_days') ?? 3);
                        dispatch(new CompleteBookingJob($item->orderable))->delay($completeBookingDelay);
                    } elseif(\Nwidart\Modules\Facades\Module::has('courses') && \Nwidart\Modules\Facades\Module::isEnabled('courses') && $item->orderable instanceof \Modules\Courses\Models\Course) {
                        $tutorBookings[$item->orderable?->instructor_id][] = $item;
                        //Calculate tutor funds
                        $platformFee = number_format(getCommission($item->price, 'courses'), 2);
                        $tutorEarning = $item->price - $platformFee;

                        if (Module::has('subscriptions') && Module::isEnabled('subscriptions')) {
                            if (!empty($this->order->subscription_id)) {
                                if ( !empty($studentSubscription) && ($studentRemainingCredits['courses'] ?? 0) > 0) {
                                    $studentRemainingCredits['courses'] = $studentRemainingCredits['courses'] - 1;
                                    $tutorEarning = (new \Modules\Subscriptions\Services\SubscriptionService())->getSubscriptionTutorPayout($studentSubscription, 'courses');
                                    $platformFee = 0;
                                }
                            } else {
                                $tutorSubscription = (new \Modules\Subscriptions\Services\SubscriptionService())->getUserSubscription(userId:$item->orderable?->instructor_id)->first();
                                $tutorRemainingCredits[$tutorSubscription?->id] = $tutorSubscription?->remaining_credits ?? [];
                                if(!empty($tutorSubscription) && ($tutorRemainingCredits[$tutorSubscription?->id]['courses'] ?? 0) > 0) {
                                    $tutorRemainingCredits[$tutorSubscription?->id]['courses'] = $tutorRemainingCredits[$tutorSubscription?->id]['courses'] - 1;
                                    $tutorEarning = $item->price;
                                    $platformFee = 0;
                                }
                                $tutorSubscriptions[] = $tutorSubscription;
                            }
                        }

                        $tutorFunds[$item->orderable?->instructor_id] = ($tutorFunds[$item->orderable?->instructor_id]??0) + $tutorEarning ;
                        $this->orderService->updateOrderItem($item, ['platform_fee'=> $platformFee, 'options' => array_merge($item->options, ['tutor_payout' => $tutorEarning])]);
                        if(!empty($item->orderable)) {
                            $courseData = [
                                'student_id'        => $this->order?->user_id,
                                'course_id'         => $item->orderable?->id,
                                'tutor_id'          => $item->orderable?->instructor_id,
                                'course_price'      => $item->orderable?->pricing?->price,
                                'course_discount'   => $item->orderable?->pricing?->discount,
                                'status'            => 'active',
                            ];
                            // In-person course: remember the date the student bought (a manipulated/foreign id is ignored).
                            $seats = new \Modules\Courses\Services\OnsiteSeatsService();
                            $date = $seats->sessionForEnrollment($item->orderable, $item->options['session_id'] ?? null);
                            if ($date) {
                                $courseData['session_id'] = $date->id;
                            }
                            $alreadyEnrolled = \Modules\Courses\Models\Enrollment::where('student_id', $courseData['student_id'])->where('course_id', $courseData['course_id'])->exists();
                            (new \Modules\Courses\Services\CourseService())->addStudentCourse($courseData);
                            if ($date && !$alreadyEnrolled) {   // payment webhooks can repeat: confirm only once
                                \Modules\Courses\Services\OnsiteNotifier::enrolled($this->order, $item->orderable, $date, $seats->isOversold($date));
                            }
                            $completeCourseDelay = Carbon::parse(now())->addDays((int)setting('_lernen.clear_course_amount_after_days') ?? 3);
                            // A free course moves no money: there is nothing to release to the instructor later.
                            if ($tutorEarning > 0) {
                                dispatch(new \Modules\Courses\Jobs\ClearCourseFundsJob($item->orderable?->instructor_id, $tutorEarning, $this->order->id))->delay($completeCourseDelay);
                            }
                        }
                    } elseif(Module::has('subscriptions') && Module::isEnabled('subscriptions') && $item->orderable instanceof \Modules\Subscriptions\Models\Subscription) {
                        if ($item->orderable?->role_id == getRoleByName('tutor')) {
                            $tutorBookings[$this->order?->user_id][] = $item;
                        }
                        if(!empty($item->orderable?->revenue_share['admin_share'])){
                            $platformFee = $item->orderable?->price * $item->orderable?->revenue_share['admin_share'] / 100;
                        } else {
                            $platformFee = $item->orderable?->price;
                        }
                        $this->orderService->updateOrderItem($item, ['platform_fee' => $platformFee]);
                        $subscriptionData = [
                            'user_id'           => $this->order->user_id,
                            'order_item_id'     => $item->id,
                            'subscription_id'   => $item->orderable?->id,
                            'price'             => $item->orderable?->price,
                            'credit_limits'     => $item->orderable?->credit_limits,
                            'remaining_credits' => $item->orderable?->credit_limits,
                            'auto_renew'        => $item->orderable?->auto_renew,
                            'expires_at'        => getSubscriptionExpiry($item->orderable),
                            'revenue_share'     => $item->orderable?->revenue_share,
                            'status'            => 'active'
                        ];
                        $userSubscription = (new \Modules\Subscriptions\Services\SubscriptionService())->addUserSubscription($subscriptionData);
                        $expiryDate        = getSubscriptionExpiry($item->orderable);
                        if ($item->orderable?->auto_renew == 'yes') {
                            $daysToSubtract    = (int)setting('_lernen.notify_subscription_expiry_before_days') ?? 0;
                            $notificationDelay = Carbon::parse($expiryDate)->subDays($daysToSubtract);
                            dispatch(new \Modules\Subscriptions\Jobs\SubscriptionRenewNotificationJob($userSubscription))->delay($notificationDelay);
                        }
                        dispatch(new \Modules\Subscriptions\Jobs\SubscriptionExpiryJob($userSubscription))->delay($expiryDate);
                    }
                }

                if (!empty($tutorFunds)) {
                    foreach ($tutorFunds as $tutorId => $amount) {
                        $walletService->pendingAvailableFunds( $tutorId, $amount, $this->order->id);
                    }
                }

                //Tutor bookings
                if (!empty($tutorBookings)) {
                    $tutorForEmail = null;
                    foreach ($tutorBookings as $tutorId => $bookings) {
                        $emailData = [];
                        $emailData['emailFor']  = 'tutor';
                        foreach($bookings as $booking) {
                            if($booking->orderable instanceof SlotBooking) {
                                $emailData['tutorName'] = $booking->orderable?->tutor?->full_name;
                                $tutorForEmail = $booking->orderable?->bookee;
                                $emailData['bookings'][]=[
                                    'studentName' => $booking->orderable?->student?->full_name,
                                    'studentImg'  => $booking->orderable?->student?->image,
                                    'subjectName' => $booking->options['subject_group'] . ' <br /> ' . $booking->options['subject'],
                                    'sessionTime' => $this->bookingService->getBookingTime($booking->orderable, 'bookee', true)
                                ];
                            } elseif(\Nwidart\Modules\Facades\Module::has('courses') && \Nwidart\Modules\Facades\Module::isEnabled('courses') && $booking->orderable instanceof \Modules\Courses\Models\Course) {
                                $emailData['tutorName'] = $booking->orderable->instructor?->profile?->full_name;
                                $tutorForEmail = $booking->orderable->instructor;
                                $emailData['courses'][]=[
                                    'studentName' => $this->order?->userProfile?->full_name,
                                    'studentImg'  => $this->order?->userProfile?->image,
                                    'courseTitle' => $booking->orderable?->title,
                                    'coursePrice' => $booking->orderable?->pricing?->price,
                                ];
                            } elseif (Module::has('subscriptions') && Module::isEnabled('subscriptions') && $booking->orderable instanceof \Modules\Subscriptions\Models\Subscription) {
                                $emailData['tutorName'] = $this->order?->userProfile?->full_name;
                                $tutorForEmail = $this->order?->orderBy;
                                $emailData['subscriptions'][] = [
                                    'subscriptionName'   => $item->orderable?->name,
                                    'subscriptionPrice'  => $item->orderable?->price,
                                    'subscriptionPeriod' => $item->orderable?->period,
                                    'expires_at'         => getSubscriptionExpiry($item->orderable)
                                ];
                            }
                        }
                        dispatch(new SendNotificationJob('sessionBooking',$tutorForEmail, $emailData));
                        dispatch(new SendDbNotificationJob('sessionBooking', $tutorForEmail, ['bookingLink' => route('tutor.bookings.upcoming-bookings')]));
                    }
                }

                //Student bookings
                $emailData = [];
                $emailData['emailFor']  = 'student';
                $emailData['studentName'] = $this->order?->userProfile?->full_name;
                foreach ($this->order?->items as $item) {
                    if($item->orderable instanceof SlotBooking) {
                        $emailData['bookings'][] = [
                            'tutorName'   => $item->orderable?->tutor?->full_name,
                            'tutorImg'    => $item->orderable?->tutor?->image,
                            'subjectName' => $item->options['subject_group'] . ' <br /> ' . $item->options['subject'],
                            'sessionTime' => $this->bookingService->getBookingTime($item->orderable, 'booker', true)
                        ];
                    } elseif(Module::has('courses') && Module::isEnabled('courses') && $item->orderable instanceof \Modules\Courses\Models\Course) {
                        $emailData['studentName'] = $this->order?->userProfile?->full_name;
                        $emailData['courses'][] = [
                            'tutorName'   => $item->orderable?->instructor?->profile?->full_name,
                            'tutorImg'    => $item->orderable?->instructor?->profile?->image,
                            'courseTitle' => $item->orderable?->title,
                            'coursePrice' => $item->orderable?->pricing?->price,
                        ];
                    } elseif(Module::has('subscriptions') && Module::isEnabled('subscriptions') && $item->orderable instanceof \Modules\Subscriptions\Models\Subscription && $item->orderable?->role_id == getRoleByName('student')) {
                        $emailData['subscriptions'][] = [
                            'subscriptionName'   => $item->orderable?->name,
                            'subscriptionPrice'  => $item->orderable?->price,
                            'subscriptionPeriod' => $item->orderable?->period,
                            'expires_at'         => getSubscriptionExpiry($item->orderable)
                        ];
                    }
                }
                
                if (isPrivateAcademy()) {
                    // The academy has no "bookings": the buyer gets a Spanish receipt with the way in (once per order).
                    $this->sendPurchaseReceipt();
                } elseif (!empty($emailData['subscriptions']) || !empty($emailData['courses']) || !empty($emailData['bookings'])) {
                    dispatch(new SendNotificationJob('sessionBooking', $this->order?->orderBy, $emailData));
                    dispatch(new SendDbNotificationJob('sessionBooking', $this->order?->orderBy, ['bookingLink' => route('student.bookings')]));
                }

                if(!empty($tutorSubscriptions)){
                    foreach($tutorSubscriptions as $tutorSubscription){
                        if(Module::has('subscriptions') && Module::isEnabled('subscriptions') && !empty($tutorSubscription)) {
                            (new \Modules\Subscriptions\Services\SubscriptionService())->updateUserSubscription($tutorSubscription->user_id, $tutorSubscription->subscription_id, ['remaining_credits' => $tutorRemainingCredits[$tutorSubscription?->id]]);
                        }
                    }
                }

                if(Module::has('subscriptions') && Module::isEnabled('subscriptions') && !empty($studentSubscription)) {
                    (new \Modules\Subscriptions\Services\SubscriptionService())->updateUserSubscription($studentSubscription->user_id, $studentSubscription->subscription_id, ['remaining_credits' => $studentRemainingCredits]);
                }

                DB::commit();
            }


        } catch (Exception $ex) {
            throw new Exception($ex);
            DB::rollBack();
        }

    }

    /** Receipt for the course lines of the order. In-person dates also get their own e-mail (see OnsiteNotifier). */
    private function sendPurchaseReceipt(): void
    {
        $student = $this->order?->orderBy;
        if (!$student?->email || !\Illuminate\Support\Facades\Cache::add('purchase-receipt:' . $this->order->id, 1, now()->addDays(30))) {
            return;
        }

        $courses = [];
        foreach ($this->order->items()->where('orderable_type', \Modules\Courses\Models\Course::class)->get() as $item) {
            $date = null;
            if (!empty($item->options['session_label'])) {
                $date = $item->options['session_label'];
            }
            $courses[] = ['title' => (string) $item->title, 'date' => $date];
        }
        if ($courses === []) {
            return;
        }

        try {
            \Illuminate\Support\Facades\Mail::to($student->email)->queue(new \App\Mail\PurchaseConfirmedEmail($this->order, $student->profile?->first_name ?: 'estudiante', $courses));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('No se pudo enviar el recibo de compra: ' . $e->getMessage(), ['order_id' => $this->order->id]);
        }
    }
}
