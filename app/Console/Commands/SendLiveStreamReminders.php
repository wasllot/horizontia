<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Courses\Models\CourseLiveStream;
use App\Mail\LiveStreamReminderEmail;
use Illuminate\Support\Facades\Mail;

class SendLiveStreamReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-live-stream-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue the reminder e-mail of live sessions whose reminder window just opened';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();
        $streams = CourseLiveStream::where('status', CourseLiveStream::STATUS_SCHEDULED)
            ->whereNotNull('notify_hours_before')
            ->where('notify_hours_before', '>', 0)
            ->where('date_time', '>', $now)
            ->with('course')
            ->get();

        foreach ($streams as $stream) {
            $notificationTime = $stream->date_time->copy()->subHours($stream->notify_hours_before);
            
            // Check if it's currently the hour to notify
            if ($now->greaterThanOrEqualTo($notificationTime) && $now->lessThan($notificationTime->copy()->addHour())) {
                
                $cacheKey = 'livestream_reminded_'.$stream->id;

                if (!\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                    if ($stream->course) {
                        foreach ((new \Modules\Courses\Services\LiveSessionService())->recipients($stream->course) as $email => $studentName) {
                            Mail::to($email)->queue(new LiveStreamReminderEmail($stream, $studentName));
                        }
                    }
                    $stream->course && app(\App\Services\WhatsAppService::class)->liveReminder($stream);
                    $this->info("Reminders queued for stream ID: {$stream->id}");

                    // Mark as sent in cache for 7 days
                    \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addDays(7));
                }
            }
        }
    }
}
