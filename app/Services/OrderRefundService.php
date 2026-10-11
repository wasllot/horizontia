<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\CourseSession;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Services\CourseService;
use Modules\Courses\Services\OnsiteNotifier;

/**
 * Money going back and access being granted by hand. One place so the Mercado Pago webhook and the admin panel
 * leave the same trace: the order becomes `refunded` (it is never deleted) and the student loses the courses of that order.
 */
class OrderRefundService
{
    /** Removes the access granted by a paid order and marks it refunded. Idempotent. Returns false when there was nothing to revoke. */
    public function revoke(Order $order, string $reason = 'manual'): bool
    {
        if (in_array($order->status, ['pending', 'refunded'], true)) {
            return false;
        }

        DB::transaction(function () use ($order) {
            $courseIds = $order->items()->where('orderable_type', Course::class)->pluck('orderable_id');
            Enrollment::where('student_id', $order->user_id)->whereIn('course_id', $courseIds)->delete();
            $order->update(['status' => 'refunded']);
        });

        Log::info('Order refunded, access revoked', ['order_id' => $order->id, 'reason' => $reason]);

        return true;
    }

    /**
     * Asks the gateway for a full refund when the order was paid with Mercado Pago, then revokes the access.
     *
     * @return array{ok: bool, via: string, message: string}
     */
    public function refund(Order $order): array
    {
        if ($order->status !== 'complete') {
            return ['ok' => false, 'via' => 'none', 'message' => 'Solo se pueden reembolsar pedidos pagados.'];
        }

        $via = 'manual';
        if ($order->payment_method === 'mercadopago' && !empty($order->transaction_id) && (float) $order->amount > 0) {
            $driver = getGatewayObject('mercadopago');
            if (empty($driver)) {
                return ['ok' => false, 'via' => 'mercadopago', 'message' => 'Mercado Pago no está configurado: reembolsa desde su panel y usa «Marcar como reembolsado».'];
            }
            try {
                $driver->refund($order->transaction_id);
                $via = 'mercadopago';
            } catch (\Throwable $e) {
                Log::error('Mercado Pago refund failed: ' . $e->getMessage(), ['order_id' => $order->id]);

                return ['ok' => false, 'via' => 'mercadopago', 'message' => 'Mercado Pago rechazó el reembolso (¿ya está reembolsado o pasó el plazo?). Revisa su panel.'];
            }
        }

        $this->revoke($order, $via);

        return ['ok' => true, 'via' => $via, 'message' => $via === 'mercadopago' ? 'Reembolso enviado a Mercado Pago y acceso retirado.' : 'Pedido marcado como reembolsado y acceso retirado (haz la devolución del dinero por tu cuenta).'];
    }

    /** Gives a student a course without an order (cortesía, pago por transferencia, error de cobro…). Returns the enrollment. */
    public function enrollManually(User $student, Course $course, ?CourseSession $date = null): Enrollment
    {
        $data = [
            'student_id' => $student->id, 'course_id' => $course->id, 'tutor_id' => $course->instructor_id,
            'course_price' => 0, 'course_discount' => 0, 'status' => 'active',
        ];
        if ($date && $course->isOnsite() && $date->course_id === $course->id) {
            $data['session_id'] = $date->id;
        }

        (new CourseService())->addStudentCourse($data);
        $enrollment = Enrollment::where('student_id', $student->id)->where('course_id', $course->id)->firstOrFail();
        if (!empty($data['session_id']) && $enrollment->session_id !== $data['session_id']) {
            $enrollment->update(['session_id' => $data['session_id']]);
        }

        if ($enrollment->session_id && ($session = CourseSession::find($enrollment->session_id))) {
            OnsiteNotifier::mail($student, $course, $session, 'confirmation');
        }

        Log::info('Manual enrollment', ['student_id' => $student->id, 'course_id' => $course->id, 'by' => auth()->id()]);

        return $enrollment;
    }
}
