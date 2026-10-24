<?php

namespace Tests\Feature;

use App\Mail\LiveStreamReminderEmail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Courses\Models\CourseLiveStream;
use Modules\Courses\Models\Enrollment;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Recordatorio de las sesiones en vivo: sale cuando toca, una sola vez, y a quien está inscrito. */
class LiveStreamRemindersTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        Mail::fake();
        Cache::flush();
        CourseLiveStream::query()->delete();
        $course = $this->course();
        Enrollment::updateOrCreate(['student_id' => $this->student()->id, 'course_id' => $course->id], ['tutor_id' => $course->instructor_id, 'status' => 'active']);
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function stream(string $startsIn, int $notifyHours = 2, ?string $createdAgo = '3 days'): CourseLiveStream
    {
        $s = CourseLiveStream::create([
            'course_id' => $this->course()->id, 'provider' => 'zoom', 'title' => 'Bienvenida en vivo', 'meeting_link' => 'https://zoom.us/j/123', 'description' => 'x',
            'date_time' => now()->add(\Carbon\CarbonInterval::make($startsIn)), 'duration_minutes' => 60, 'status' => CourseLiveStream::STATUS_SCHEDULED, 'notify_hours_before' => $notifyHours,
        ]);
        if ($createdAgo) {
            DB::table($s->getTable())->where('id', $s->id)->update(['created_at' => now()->sub(\Carbon\CarbonInterval::make($createdAgo))]);
        }

        return $s->fresh();
    }

    private function remind(): void
    {
        $this->artisan('app:send-live-stream-reminders')->assertSuccessful();
    }

    public function test_it_reminds_enrolled_students_once_when_the_reminder_time_arrives(): void
    {
        $stream = $this->stream('90 minutes');   // empieza en 1 h 30 con aviso de 2 h: ya toca

        $this->remind();
        Mail::assertQueued(LiveStreamReminderEmail::class, fn ($m) => $m->hasTo($this->student()->email) && $m->liveStream->is($stream));
        $this->assertNotNull($stream->fresh()->reminded_at);

        $this->remind();
        $this->remind();
        Mail::assertQueued(LiveStreamReminderEmail::class, 1);
    }

    public function test_it_waits_until_the_reminder_time_and_survives_a_late_scheduler(): void
    {
        $this->stream('5 hours');   // aviso de 2 h antes: todavía no
        $this->remind();
        Mail::assertNothingQueued();

        // el planificador estuvo caído: ya pasó más de una hora desde la hora del aviso y el aviso igual sale (antes se perdía)
        $late = $this->stream('20 minutes', 2);
        $this->remind();
        Mail::assertQueued(LiveStreamReminderEmail::class, fn ($m) => $m->liveStream->is($late));
    }

    public function test_clearing_the_cache_does_not_repeat_a_reminder(): void
    {
        $this->stream('90 minutes');
        $this->remind();

        Cache::flush();
        $this->remind();

        Mail::assertQueued(LiveStreamReminderEmail::class, 1);
    }

    public function test_sessions_created_after_the_reminder_time_do_not_get_a_second_email(): void
    {
        $this->stream('30 minutes', 2, null);   // se programó hace un instante: el correo de «sesión programada» ya les avisó

        $this->remind();

        Mail::assertNothingQueued();
    }

    public function test_cancelled_finished_or_unconfigured_sessions_are_ignored(): void
    {
        $cancelled = $this->stream('90 minutes');
        $cancelled->update(['status' => CourseLiveStream::STATUS_CANCELLED]);
        $this->stream('90 minutes', 0);                                       // sin recordatorio configurado
        $past = $this->stream('90 minutes');
        $past->update(['date_time' => now()->subHour()]);                     // ya empezó

        $this->remind();

        Mail::assertNothingQueued();
    }
}
