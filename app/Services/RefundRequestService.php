<?php

namespace App\Services;

use App\Mail\RefundDecisionEmail;
use App\Mail\RefundRequestedEmail;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Enrollment;

/**
 * Garantía de reembolso hecha producto: la persona lo pide desde «Mis facturas» con las reglas de la política
 * (dentro de N días del pago, sin haber consumido más del 20 % ni sacado certificado) y el equipo lo aprueba o rechaza
 * desde una bandeja. Al aprobar se devuelve por Mercado Pago y se retira el acceso (OrderRefundService).
 */
class RefundRequestService
{
    public const MAX_PROGRESS = 20;

    /** @return \Illuminate\Support\Collection<int, Course> */
    private function courses(Order $order)
    {
        $ids = $order->items()->where('orderable_type', Course::class)->pluck('orderable_id');

        return Course::whereIn('id', $ids)->get();
    }

    /** Cuándo se aprobó el pago: el alta en el curso (los pagos en efectivo se aprueban días después del pedido). */
    public function paidAt(Order $order): \Carbon\CarbonInterface
    {
        $ids = $order->items()->where('orderable_type', Course::class)->pluck('orderable_id');
        $enrolled = $ids->isEmpty() ? null : Enrollment::where('student_id', $order->user_id)->whereIn('course_id', $ids)->max('created_at');

        return $enrolled ? \Carbon\Carbon::parse($enrolled) : $order->created_at;
    }

    public function progressOf(Order $order): int
    {
        $svc = app(CourseCertificateService::class);
        $student = User::find($order->user_id);

        return $student ? (int) $this->courses($order)->map(fn ($c) => $svc->progress($student, $c))->max() : 0;
    }

    /** @return array{ok: bool, reason: ?string, deadline: ?\Carbon\CarbonInterface, progress: int} */
    public function eligibility(User $user, Order $order): array
    {
        $days = (int) config('academy.refund_days');
        $fail = fn (string $why, $deadline = null, int $progress = 0) => ['ok' => false, 'reason' => $why, 'deadline' => $deadline, 'progress' => $progress];

        if ((int) $order->user_id !== (int) $user->id) {
            return $fail('Este pedido no es tuyo.');
        }
        if ($order->status === 'refunded') {
            return $fail('Este pedido ya fue reembolsado.');
        }
        if ($order->status !== 'complete' || (float) $order->amount <= 0) {
            return $fail((float) $order->amount <= 0 ? 'Los cursos gratuitos no generan reembolso.' : 'Solo se pueden reembolsar pedidos pagados.');
        }
        if ($days <= 0) {
            return $fail('La garantía de reembolso no está activa.');
        }
        $deadline = $this->paidAt($order)->copy()->addDays($days);
        if (now()->greaterThan($deadline)) {
            return $fail("Pasaron más de {$days} días desde tu pago.", $deadline);
        }
        if (RefundRequest::where('order_id', $order->id)->where('status', RefundRequest::PENDING)->exists()) {
            return $fail('Ya tienes una solicitud en revisión para este pedido.', $deadline);
        }

        $progress = $this->progressOf($order);
        if ($progress > self::MAX_PROGRESS) {
            return $fail('Llevas ' . $progress . ' % del curso y la garantía cubre hasta ' . self::MAX_PROGRESS . ' %.', $deadline, $progress);
        }
        $student = User::find($order->user_id);
        $cert = app(CourseCertificateService::class);
        if ($student && $this->courses($order)->contains(fn ($c) => $cert->find($student, $c))) {
            return $fail('Ya obtuviste el certificado de este curso.', $deadline, $progress);
        }

        return ['ok' => true, 'reason' => null, 'deadline' => $deadline, 'progress' => $progress];
    }

    /** @return array{ok: bool, message: string, request: ?RefundRequest} */
    public function request(User $user, Order $order, string $reason): array
    {
        $reason = trim(strip_tags($reason));
        if (mb_strlen($reason) < 10) {
            return ['ok' => false, 'message' => 'Cuéntanos con un poco más de detalle por qué lo pides (mínimo 10 caracteres).', 'request' => null];
        }
        $check = $this->eligibility($user, $order);
        if (!$check['ok']) {
            return ['ok' => false, 'message' => $check['reason'], 'request' => null];
        }

        $request = RefundRequest::create(['order_id' => $order->id, 'user_id' => $user->id, 'reason' => mb_substr($reason, 0, 500), 'progress' => $check['progress']])->fresh();   // con los valores por defecto de la base (estado «pending»)
        $this->notifyTeam($request);

        return ['ok' => true, 'message' => 'Recibimos tu solicitud. Te respondemos por correo en un máximo de 3 días hábiles.', 'request' => $request];
    }

    /** @return array{ok: bool, message: string} */
    public function approve(RefundRequest $request, User $admin): array
    {
        if ($request->status !== RefundRequest::PENDING) {
            return ['ok' => false, 'message' => 'Esta solicitud ya se resolvió.'];
        }
        $result = app(OrderRefundService::class)->refund($request->order);
        if (!$result['ok']) {
            return ['ok' => false, 'message' => $result['message']];
        }

        $request->update(['status' => RefundRequest::APPROVED, 'reviewed_by' => $admin->id, 'reviewed_at' => now()]);
        $this->notifyStudent($request->fresh());

        return ['ok' => true, 'message' => $result['message']];
    }

    /** @return array{ok: bool, message: string} */
    public function reject(RefundRequest $request, User $admin, string $note): array
    {
        $note = trim(strip_tags($note));
        if ($request->status !== RefundRequest::PENDING) {
            return ['ok' => false, 'message' => 'Esta solicitud ya se resolvió.'];
        }
        if (mb_strlen($note) < 5) {
            return ['ok' => false, 'message' => 'Escribe el motivo: se lo enviamos a la persona.'];
        }

        $request->update(['status' => RefundRequest::REJECTED, 'reviewed_by' => $admin->id, 'reviewed_at' => now(), 'review_note' => mb_substr($note, 0, 300)]);
        $this->notifyStudent($request->fresh());

        return ['ok' => true, 'message' => 'Solicitud rechazada y avisada a la persona.'];
    }

    private function notifyTeam(RefundRequest $request): void
    {
        $to = config('academy.contact_email') ?: config('mail.from.address');
        try {
            if ($to) {
                Mail::to($to)->queue(new RefundRequestedEmail($request));
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo avisar al equipo del reembolso: ' . $e->getMessage(), ['refund_request' => $request->id]);
        }
    }

    private function notifyStudent(RefundRequest $request): void
    {
        try {
            if ($request->user?->email) {
                Mail::to($request->user->email)->queue(new RefundDecisionEmail($request));
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo avisar de la decisión del reembolso: ' . $e->getMessage(), ['refund_request' => $request->id]);
        }
    }
}
