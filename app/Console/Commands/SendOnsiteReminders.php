<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Courses\Models\CourseSession;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Services\OnsiteNotifier;

class SendOnsiteReminders extends Command
{
    protected $signature = 'app:send-onsite-reminders {--hours=24 : Hours before the start}';

    protected $description = 'E-mail students of in-person dates that start within the next 24 hours (once per enrollment)';

    public function handle(): int
    {
        $sent = 0;
        $sessions = CourseSession::where('status', CourseSession::SCHEDULED)
            ->whereBetween('starts_at', [now(), now()->addHours((int) $this->option('hours'))])
            ->with('course')->get();

        foreach ($sessions as $session) {
            if (!$session->course) {
                continue;
            }
            $pending = Enrollment::where('session_id', $session->id)->where('status', Enrollment::STATUSES['active'])
                ->whereNull('reminded_at')->with('student.user.profile')->get();

            foreach ($pending as $enrollment) {
                $user = $enrollment->student?->user;
                if ($user && OnsiteNotifier::mail($user, $session->course, $session, 'reminder')) {
                    app(\App\Services\WhatsAppService::class)->onsiteReminder($user, $session->course, $session);
                    $enrollment->forceFill(['reminded_at' => now()])->save();
                    $sent++;
                }
            }
        }

        $this->info("Recordatorios enviados: {$sent}");

        return self::SUCCESS;
    }
}
