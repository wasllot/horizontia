<?php

namespace Modules\Courses\Services;

use Modules\Courses\Models\Course;
use Modules\Courses\Models\CourseSession;
use Modules\Courses\Models\Enrollment;

/**
 * Server-side rules for buying a date of an in-person course. The date travels in the cart/order item
 * options (`session_id`) and is re-checked here every time it matters: at checkout (with a row lock so two
 * simultaneous purchases cannot take the last seat) and when the payment is confirmed.
 */
class OnsiteSeatsService
{
    public const MESSAGES = [
        'missing' => 'Elige la fecha a la que quieres asistir antes de pagar.',
        'invalid' => 'La fecha elegida ya no está disponible para este curso. Vuelve al curso y elige otra.',
        'cancelled' => 'La fecha elegida fue cancelada. Vuelve al curso y elige otra.',
        'closed' => 'Las ventas de la fecha elegida ya cerraron. Vuelve al curso y elige otra.',
        'sold_out' => 'La fecha elegida se agotó. Vuelve al curso y elige otra.',
    ];

    /**
     * Why this cart line cannot be bought (null = it can). `$lock` must be true inside the checkout transaction.
     *
     * @return array{0: string, 1: string}|null  [code, message]
     */
    public function check(Course $course, mixed $sessionId, bool $lock = false): ?array
    {
        if (!$course->isOnsite()) {
            return null;
        }
        if (empty($sessionId)) {
            return ['missing', self::MESSAGES['missing']];
        }

        $session = CourseSession::query()->where('course_id', $course->id)->whereKey((int) $sessionId)
            ->when($lock, fn ($q) => $q->lockForUpdate())->first();   // another course's date (IDOR) is simply "invalid"
        if (!$session) {
            return ['invalid', self::MESSAGES['invalid']];
        }

        $reason = $session->unavailableReason();

        return $reason ? [$reason, self::MESSAGES[$reason]] : null;
    }

    /** Checks every in-person line of a cart; returns the first problem found. */
    public function checkCart(iterable $content, bool $lock = false): ?array
    {
        foreach ($content as $item) {
            if (($item['cartable_type'] ?? null) !== Course::class) {
                continue;
            }
            $course = Course::find($item['cartable_id']);
            if ($course && ($problem = $this->check($course, $item['options']['session_id'] ?? null, $lock))) {
                return $problem + [2 => $course->title];
            }
        }

        return null;
    }

    /** The date to store in the enrollment for a paid order line (null for online courses or an invalid/foreign date). */
    public function sessionForEnrollment(Course $course, mixed $sessionId): ?CourseSession
    {
        if (!$course->isOnsite() || empty($sessionId)) {
            return null;
        }

        return CourseSession::where('course_id', $course->id)->find((int) $sessionId);
    }

    /** True when a date has more active enrollments than seats (paid in parallel for the last place). */
    public function isOversold(CourseSession $session): bool
    {
        return $session->capacity !== null && $session->enrollments()->where('status', Enrollment::STATUSES['active'])->count() > $session->capacity;
    }
}
