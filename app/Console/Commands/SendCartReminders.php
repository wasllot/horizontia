<?php

namespace App\Console\Commands;

use App\Mail\CartReminderEmail;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Services\OnsiteSeatsService;

class SendCartReminders extends Command
{
    protected $signature = 'app:send-cart-reminders {--dry-run : List who would be e-mailed without sending}';

    protected $description = 'Reminds students about courses left in their cart (first after 3 h, second after 2 more days, then never)';

    /** Hours of inactivity before the 1st reminder, hours between the 1st and 2nd, and how long a cart stays "recoverable". */
    private const FIRST_AFTER_HOURS = 3;
    private const SECOND_AFTER_HOURS = 48;
    private const RECOVERABLE_DAYS = 7;

    public function handle(): int
    {
        if (!config('academy.cart_reminders')) {
            $this->info('Recordatorios de carrito desactivados (ACADEMY_CART_REMINDERS=false).');

            return self::SUCCESS;
        }

        $sent = 0;
        $byUser = CartItem::query()->whereNotNull('user_id')->where('cartable_type', Course::class)->get()->groupBy('user_id');

        foreach ($byUser as $userId => $lines) {
            $user = User::with('profile')->find($userId);
            if (!$user || !$user->email || $user->cart_reminders_off_at || !$user->hasRole('student')) {
                continue;
            }

            $count = (int) $lines->max('reminders_count');
            $lastActivity = $lines->max('updated_at');
            $remindedAt = $lines->max('reminded_at');
            $due = $count === 0
                ? $lastActivity->lte(now()->subHours(self::FIRST_AFTER_HOURS))
                : ($count === 1 && $remindedAt && $remindedAt->lte(now()->subHours(self::SECOND_AFTER_HOURS)));
            if (!$due || $lastActivity->lt(now()->subDays(self::RECOVERABLE_DAYS))) {
                continue;
            }
            // Someone waiting for an OXXO/SPEI payment must not be nagged to buy again.
            if (Order::where('user_id', $user->id)->where('status', 0)->where('payment_method', 'mercadopago')->where('created_at', '>=', now()->subDays(3))->exists()) {
                continue;
            }

            $items = $this->recoverable($user, $lines);
            if ($items === []) {
                continue;
            }

            $this->line("{$user->email}: " . count($items) . ' curso(s), recordatorio ' . ($count + 1));
            if ($this->option('dry-run')) {
                continue;
            }

            Mail::to($user->email)->queue(new CartReminderEmail(
                $user, $user->profile?->first_name ?: 'estudiante', $items,
                URL::signedRoute('cart-reminders.off', ['user' => $user->id]), $count + 1
            ));
            CartItem::where('user_id', $user->id)->update(['reminded_at' => now(), 'reminders_count' => $count + 1]);
            $sent++;
        }

        $this->info("Recordatorios enviados: {$sent}");

        return self::SUCCESS;
    }

    /** Only courses that can still be bought: published, not already owned, and (in person) with the chosen date still sellable. */
    private function recoverable(User $user, $lines): array
    {
        $seats = new OnsiteSeatsService();
        $items = [];
        foreach ($lines as $line) {
            $course = Course::with('pricing')->find($line->cartable_id);
            if (!$course || $course->getRawOriginal('status') != Course::STATUSES['active']) {
                continue;
            }
            if (Enrollment::where('student_id', $user->id)->where('course_id', $course->id)->exists()) {
                continue;
            }
            if ($seats->check($course, $line->options['session_id'] ?? null)) {
                continue;   // date cancelled, closed or sold out: nothing to recover
            }
            $items[] = [
                'title' => (string) $course->title,
                'price' => (float) ($course->pricing?->final_price ?? $line->price ?? 0),
                'date' => $line->options['session_label'] ?? null,
                'url' => route('courses.course-detail', ['slug' => $course->slug]),
            ];
        }

        return $items;
    }
}
