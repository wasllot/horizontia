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
            ->whereNotNull('notify_hours_before')->where('notify_hours_before', '>', 0)
            ->whereNull('reminded_at')
            ->where('date_time', '>', $now)
            ->with('course')
            ->get();

        foreach ($streams as $stream) {
            $notificationTime = $stream->date_time->copy()->subHours($stream->notify_hours_before);

            // Toca avisar cuando ya llegó la hora del recordatorio (sin ventana: si el planificador se atrasó, sale en la siguiente vuelta)
            // y la sesión se programó ANTES de esa hora: si se creó después, el correo de «sesión programada» ya les avisó.
            if ($now->lessThan($notificationTime) || $stream->created_at?->greaterThan($notificationTime)) {
                continue;
            }

            // Se marca primero y de forma atómica: dos corridas a la vez no mandan el aviso dos veces.
            if (CourseLiveStream::whereKey($stream->id)->whereNull('reminded_at')->update(['reminded_at' => $now]) !== 1) {
                continue;
            }

            if ($stream->course) {
                foreach ((new \Modules\Courses\Services\LiveSessionService())->recipients($stream->course) as $email => $studentName) {
                    Mail::to($email)->queue(new LiveStreamReminderEmail($stream, $studentName));
                }
                app(\App\Services\WhatsAppService::class)->liveReminder($stream);
            }
            $this->info("Reminders queued for stream ID: {$stream->id}");
        }
    }
}
