<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\CourseLiveStream;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Models\Section;
use Modules\Courses\Models\Watchtime;
use Modules\Courses\Services\LiveRecordingService;
use Modules\Courses\Services\LiveSessionService;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Sesiones en vivo: la grabación queda como lección del curso y cumple la sesión para quien no asistió. */
class LiveRecordingsTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        Mail::fake();
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    /** @return array{0: Course, 1: Curriculum, 2: CourseLiveStream} */
    private function liveLesson(string $when = '-2 hours', ?string $recording = null, string $link = 'https://zoom.us/j/81234567890?pwd=abc'): array
    {
        $c = $this->course2()->replicate();
        $c->title = 'Curso en vivo ' . uniqid();
        $c->slug = 'ev-' . uniqid();
        $c->status = 'active';
        $c->delivery = 'online';
        $c->save();
        $section = Section::create(['course_id' => $c->id, 'title' => 'S', 'description' => 'S', 'sort_order' => 1]);
        $live = Curriculum::create(['section_id' => $section->id, 'title' => 'Sesión en vivo', 'description' => 'x', 'type' => 'live', 'content_length' => 3600, 'sort_order' => 1, 'is_preview' => false]);
        $c->update(['content_length' => 3600]);
        $stream = app(LiveSessionService::class)->create($c, ['title' => 'Sesión en vivo', 'meeting_link' => $link, 'date_time' => now()->modify($when), 'duration_minutes' => 60], $live, announce: false);
        if ($recording) {
            $stream->update(['recording_url' => $recording]);
        }
        Enrollment::updateOrCreate(['student_id' => $this->student()->id, 'course_id' => $c->id], ['tutor_id' => $c->instructor_id, 'status' => 'active']);

        return [$c->fresh(), $live, $stream->fresh()];
    }

    public function test_an_ended_session_with_a_recording_becomes_a_lesson_once(): void
    {
        [$c, $live, $stream] = $this->liveLesson('-3 hours', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');
        $svc = app(LiveRecordingService::class);

        $lesson = $svc->publishIfReady($stream);
        $this->assertNotNull($lesson);
        $this->assertNull($svc->publishIfReady($stream->fresh()), 'only once');

        $this->assertSame('yt_link', $lesson->type);
        $this->assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ', $lesson->media_path);
        $this->assertSame('Grabación: Sesión en vivo', $lesson->title);
        $this->assertSame($live->section_id, $lesson->section_id);
        $this->assertGreaterThan($live->sort_order, $lesson->sort_order);
        $this->assertSame(0, (int) $lesson->content_length, 'the course time already counts the live session');
        $this->assertSame($lesson->id, $stream->fresh()->recording_curriculum_id);
        $this->assertSame(1, Curriculum::where('section_id', $live->section_id)->where('title', 'like', 'Grabación:%')->count());
    }

    public function test_vimeo_and_unknown_hosts_are_handled_and_escaped(): void
    {
        $svc = app(LiveRecordingService::class);

        $this->assertSame(['type' => 'vm_link', 'media_path' => 'https://vimeo.com/123456789', 'article_content' => null], $svc->lessonFor('https://vimeo.com/123456789'));

        $other = $svc->lessonFor('https://zoom.us/rec/share/abc"><script>alert(1)</script>', 'p4ss');
        $this->assertSame('article', $other['type']);
        $this->assertStringNotContainsString('<script>', $other['article_content']);
        $this->assertStringContainsString('Ver la grabación', $other['article_content']);
        $this->assertStringContainsString('p4ss', $other['article_content']);
    }

    public function test_nothing_is_published_before_the_session_ends_without_a_recording_or_when_cancelled(): void
    {
        $svc = app(LiveRecordingService::class);

        [, , $future] = $this->liveLesson('+1 day', 'https://youtu.be/dQw4w9WgXcQ');
        $this->assertNull($svc->publishIfReady($future), 'not over yet');

        [, , $none] = $this->liveLesson('-3 hours');
        $this->assertNull($svc->publishIfReady($none), 'no recording yet');

        [, , $cancelled] = $this->liveLesson('-3 hours', 'https://youtu.be/dQw4w9WgXcQ');
        $cancelled->update(['status' => CourseLiveStream::STATUS_CANCELLED]);
        $this->assertNull($svc->publishIfReady($cancelled->fresh()));
    }

    public function test_the_command_publishes_pending_recordings(): void
    {
        [, , $stream] = $this->liveLesson('-3 hours', 'https://youtu.be/dQw4w9WgXcQ');

        $this->artisan('app:publish-live-recordings')->assertSuccessful();

        $this->assertNotNull($stream->fresh()->recording_curriculum_id);
    }

    public function test_finishing_the_recording_credits_the_live_lesson_for_someone_who_missed_it(): void
    {
        [$c, $live, $stream] = $this->liveLesson('-3 hours', 'https://youtu.be/dQw4w9WgXcQ');
        $lesson = app(LiveRecordingService::class)->publish($stream);

        app(LiveRecordingService::class)->creditLiveLesson($this->student(), $lesson->id);
        app(LiveRecordingService::class)->creditLiveLesson($this->student(), $lesson->id);   // idempotent

        $rows = Watchtime::where('user_id', $this->student()->id)->where('curriculum_id', $live->id)->get();
        $this->assertCount(1, $rows);
        $this->assertSame(3600, (int) $rows[0]->duration);
        $this->assertSame(100, app(\App\Services\CourseCertificateService::class)->progress($this->student(), $c));
    }

    public function test_the_classroom_shows_the_recording_lesson_to_enrolled_students(): void
    {
        [$c, , $stream] = $this->liveLesson('-3 hours', 'https://youtu.be/dQw4w9WgXcQ');
        app(LiveRecordingService::class)->publish($stream);

        $this->actingAs($this->student())->get(route('courses.course-taking', ['slug' => $c->slug]))->assertOk()->assertSee('Grabación: Sesión en vivo');
    }

    // ------------------------------------------------------------ Zoom

    private function zoom(array $payload, string $secret = 'zoom-secret', ?string $sig = null, ?int $ts = null)
    {
        config(['services.zoom.webhook_secret' => $secret]);
        $body = json_encode($payload);
        $ts ??= time();
        $sig ??= 'v0=' . hash_hmac('sha256', "v0:{$ts}:{$body}", $secret);

        return $this->call('POST', '/zoom/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_ZM_REQUEST_TIMESTAMP' => (string) $ts, 'HTTP_X_ZM_SIGNATURE' => $sig], $body);
    }

    private function recordingEvent(string $meetingId = '81234567890'): array
    {
        return ['event' => 'recording.completed', 'payload' => ['object' => ['id' => $meetingId, 'share_url' => 'https://us06web.zoom.us/rec/share/xyz', 'password' => 'abc123']]];
    }

    public function test_zoom_validates_the_endpoint_with_the_encrypted_token(): void
    {
        $res = $this->zoom(['event' => 'endpoint.url_validation', 'payload' => ['plainToken' => 'tok123']]);

        $res->assertOk()->assertJson(['plainToken' => 'tok123', 'encryptedToken' => hash_hmac('sha256', 'tok123', 'zoom-secret')]);
    }

    public function test_a_signed_recording_event_publishes_the_lesson_with_the_passcode(): void
    {
        [, $live, $stream] = $this->liveLesson('-3 hours');

        $this->zoom($this->recordingEvent())->assertOk()->assertJson(['matched' => true]);

        $stream = $stream->fresh();
        $this->assertSame('https://us06web.zoom.us/rec/share/xyz', $stream->recording_url);
        $lesson = Curriculum::findOrFail($stream->recording_curriculum_id);
        $this->assertSame('article', $lesson->type);
        $this->assertStringContainsString('abc123', $lesson->article_content);
    }

    public function test_zoom_events_with_a_bad_signature_old_timestamp_or_no_secret_are_rejected(): void
    {
        [, , $stream] = $this->liveLesson('-3 hours');

        $this->zoom($this->recordingEvent(), sig: 'v0=falsa')->assertStatus(401);
        $this->zoom($this->recordingEvent(), ts: time() - 1000)->assertStatus(401);
        $this->assertNull($stream->fresh()->recording_url);

        $this->zoom($this->recordingEvent(), secret: '')->assertStatus(404);
    }

    public function test_an_event_for_an_unknown_meeting_or_another_event_type_is_ignored(): void
    {
        [, , $stream] = $this->liveLesson('-3 hours');

        $this->zoom($this->recordingEvent('99999999999'))->assertOk()->assertJson(['matched' => false]);
        $this->zoom(['event' => 'meeting.started', 'payload' => ['object' => ['id' => '81234567890']]])->assertOk()->assertJson(['ignored' => true]);
        $this->assertNull($stream->fresh()->recording_url);
    }
}
