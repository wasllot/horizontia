<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

use Illuminate\Support\Facades\Schedule;
Schedule::command('app:send-live-stream-reminders')->everyFifteenMinutes();
Schedule::command('app:send-onsite-reminders')->hourly();
Schedule::command('app:send-cart-reminders')->hourly()->withoutOverlapping();
Schedule::command('app:send-progress-reminders')->hourly()->withoutOverlapping();
Schedule::command('app:publish-live-recordings')->everyTenMinutes()->withoutOverlapping();

// Heartbeats: "Estado del sistema" (and academy:preflight) tell whether the scheduler and a queue worker are really running.
Schedule::call(function () {
    \Illuminate\Support\Facades\Cache::forever('heartbeat:scheduler', now()->toIso8601String());
    // A queued job proves the worker is alive (a closure dispatched here is executed by `queue:work`).
    dispatch(function () {
        \Illuminate\Support\Facades\Cache::forever('heartbeat:queue', now()->toIso8601String());
    });
})->name('heartbeat')->everyMinute();

// Nightly database backup (kept ACADEMY_BACKUP_KEEP_DAYS days; also copied to BACKUP_DISK when set).
Schedule::command('app:backup-database')->dailyAt('03:15')->withoutOverlapping();

// Hosting sin worker permanente (Plesk/compartido): sin esto los correos y recordatorios en cola nunca se envían.
// Procesa la cola unos segundos cada minuto y termina (ACADEMY_QUEUE_VIA_SCHEDULER=true y QUEUE_CONNECTION=database).
if (config('academy.queue_via_scheduler') && config('queue.default') !== 'sync') {
    Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')->everyMinute()->withoutOverlapping(5);
}
