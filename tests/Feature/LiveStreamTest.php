<?php

namespace Tests\Feature;

use App\Mail\LiveStreamScheduledEmail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Modules\Courses\Livewire\Pages\Tutor\LiveStreams\ManageLiveStreams;
use Modules\Courses\Livewire\Pages\Tutor\LiveStreams\ScheduleLiveStream;
use Modules\Courses\Models\CourseLiveStream;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * Live-stream scheduling (course_live_streams / Modules/Courses's own
 * ScheduleLiveStream + ManageLiveStreams Livewire components).
 *
 * Confirmed by reading the source: this is a pure-DB CRUD flow. There is no
 * real Zoom/Meet API call anywhere in it — `meeting_link` is a manually
 * typed-in URL field, and Modules/MeetFusion's Zoom driver
 * (Modules/MeetFusion/Drivers/Zoom.php, which *would* make a real HTTP call
 * to zoom.us) is never invoked from this feature. The only side effect is
 * a `Mail::queue()` notification to enrolled students, which is safe here
 * since .env.testing sets QUEUE_CONNECTION=sync (so it runs inline, letting
 * us catch mail-building crashes) and MAIL_MAILER=array (so nothing ever
 * leaves the process) — `Mail::fake()` is used anyway as a hard guard.
 */
class LiveStreamTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
    }

    public function test_tutor_can_schedule_list_edit_and_delete_a_live_stream(): void
    {
        Mail::fake();

        $tutor = $this->tutor();
        $course = $this->course(); // qa-tutor is the instructor; qa-student is enrolled.

        // 1) Schedule.
        Livewire::actingAs($tutor)
            ->test(ScheduleLiveStream::class)
            ->set('course_id', $course->id)
            ->set('title', 'QA Live Session')
            ->set('description', 'A live session created by an automated test.')
            ->set('meeting_link', 'https://example.com/meet/qa')
            ->set('date_time', now()->addDay()->format('Y-m-d H:i:s'))
            ->set('duration_minutes', 45)
            ->set('notify_hours_before', 12)
            ->call('save')
            ->assertHasNoErrors();

        $stream = CourseLiveStream::where('title', 'QA Live Session')->first();
        $this->assertNotNull($stream);
        $this->assertSame($course->id, $stream->course_id);
        $this->assertSame(CourseLiveStream::STATUS_SCHEDULED, $stream->status);

        Mail::assertQueued(LiveStreamScheduledEmail::class);

        // 2) List (tutor's manage screen).
        $manage = Livewire::actingAs($tutor)->test(ManageLiveStreams::class);
        $manage->assertSee('QA Live Session');
        $this->assertTrue($manage->get('liveStreams')->pluck('id')->contains($stream->id));

        // 3) Edit.
        $manage->call('startEdit', $stream->id)
            ->set('edit_title', 'QA Live Session (Updated)')
            ->set('edit_status', CourseLiveStream::STATUS_COMPLETED)
            ->call('saveEdit')
            ->assertHasNoErrors();

        $stream->refresh();
        $this->assertSame('QA Live Session (Updated)', $stream->title);
        $this->assertSame(CourseLiveStream::STATUS_COMPLETED, $stream->status);

        // 4) Cancel/delete.
        Livewire::actingAs($tutor)
            ->test(ManageLiveStreams::class)
            ->call('delete', $stream->id);

        $this->assertDatabaseMissing(
            (config('courses.db_prefix') ?? 'courses_') . 'course_live_streams',
            ['id' => $stream->id]
        );
    }

    public function test_scheduling_a_live_stream_actually_renders_the_notification_email(): void
    {
        // Deliberately does NOT fake Mail: MAIL_MAILER=array in
        // .env.testing means the mailable is genuinely built and rendered
        // (Blade markdown template + Enrollment->student->user chain) but
        // never leaves the process — this is what actually caught the
        // Enrollment::student()-returns-a-Profile bug fixed alongside this
        // test (see report), since Mail::fake() alone would have hidden it.
        $tutor = $this->tutor();
        $course = $this->course();

        Livewire::actingAs($tutor)
            ->test(ScheduleLiveStream::class)
            ->set('course_id', $course->id)
            ->set('title', 'QA Live Session Rendered')
            ->set('date_time', now()->addDay()->format('Y-m-d H:i:s'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(
            (config('courses.db_prefix') ?? 'courses_') . 'course_live_streams',
            ['title' => 'QA Live Session Rendered']
        );
    }

    public function test_schedule_live_stream_validates_input_without_crashing(): void
    {
        $tutor = $this->tutor();
        $course = $this->course();

        // Missing title, and a date_time in the past (fails 'after:now').
        Livewire::actingAs($tutor)
            ->test(ScheduleLiveStream::class)
            ->set('course_id', $course->id)
            ->set('title', '')
            ->set('date_time', now()->subDay()->format('Y-m-d H:i:s'))
            ->call('save')
            ->assertHasErrors(['title' => 'required', 'date_time' => 'after']);

        $this->assertDatabaseMissing(
            (config('courses.db_prefix') ?? 'courses_') . 'course_live_streams',
            ['course_id' => $course->id, 'title' => '']
        );
    }

    public function test_live_stream_pages_are_not_reachable_by_a_student(): void
    {
        $student = $this->student();

        $schedule = $this->actingAs($student)->get('/tutor/schedule-live-stream');
        $this->assertLessThan(500, $schedule->getStatusCode());
        $this->assertNotEquals(200, $schedule->getStatusCode(), 'A student should not be able to load the tutor-only schedule-live-stream page.');

        $manage = $this->actingAs($student)->get('/tutor/manage-live-streams');
        $this->assertLessThan(500, $manage->getStatusCode());
        $this->assertNotEquals(200, $manage->getStatusCode(), 'A student should not be able to load the tutor-only manage-live-streams page.');
    }
}
