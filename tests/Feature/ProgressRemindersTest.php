<?php

namespace Tests\Feature;

use App\Mail\ProgressReminderEmail;
use App\Models\ProgressReminder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Models\Section;
use Modules\Courses\Models\Watchtime;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Recordatorios «retoma tu curso»: tras días de silencio, con tope, sin molestar a quien terminó o se dio de baja. */
class ProgressRemindersTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.timezone' => 'America/Mexico_City', 'academy.progress_reminders' => true]);
        Mail::fake();
        Enrollment::where('student_id', $this->student()->id)->delete();
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    /** @return array{0: Course, 1: array<int, Curriculum>} */
    private function enrolled(int $lessons = 2, int $enrolledDaysAgo = 10): array
    {
        $c = $this->course2()->replicate();
        $c->title = 'Retoma ' . uniqid();
        $c->slug = 'rt-' . uniqid();
        $c->status = 'active';
        $c->delivery = 'online';
        $c->save();
        $section = Section::create(['course_id' => $c->id, 'title' => 'S', 'description' => 'S', 'sort_order' => 1]);
        $ls = [];
        for ($i = 1; $i <= $lessons; $i++) {
            $ls[] = Curriculum::create(['section_id' => $section->id, 'title' => "Lección $i", 'description' => 'x', 'type' => 'article', 'article_content' => '<p>x</p>', 'content_length' => 600, 'sort_order' => $i, 'is_preview' => false]);
        }
        $c->update(['content_length' => 600 * $lessons]);
        $e = Enrollment::create(['student_id' => $this->student()->id, 'course_id' => $c->id, 'tutor_id' => $c->instructor_id, 'status' => 'active']);
        DB::table($e->getTable())->where('id', $e->id)->update(['created_at' => now()->subDays($enrolledDaysAgo)]);

        return [$c->fresh(), $ls];
    }

    private function watched(Curriculum $l, int $daysAgo): void
    {
        $w = Watchtime::create(['user_id' => $this->student()->id, 'curriculum_id' => $l->id, 'course_id' => $l->section->course_id, 'section_id' => $l->section_id, 'duration' => $l->content_length]);
        DB::table($w->getTable())->where('id', $w->id)->update(['updated_at' => now()->subDays($daysAgo)]);
    }

    private function run_(): void
    {
        $this->artisan('app:send-progress-reminders', ['--force-hours' => true])->assertSuccessful();
    }

    public function test_a_student_who_stopped_for_five_days_gets_the_next_lesson_in_the_email(): void
    {
        [$course, $lessons] = $this->enrolled();
        $this->watched($lessons[0], 6);

        $this->run_();

        Mail::assertQueued(ProgressReminderEmail::class, function ($m) use ($course, $lessons) {
            return $m->hasTo($this->student()->email) && $m->courses[0]['progress'] === 50 && $m->courses[0]['lesson'] === 'Lección 2'
                && str_contains($m->courses[0]['url'], '/aula/' . $course->slug) && str_contains($m->courses[0]['url'], 'leccion=' . $lessons[1]->id);
        });
    }

    public function test_recent_activity_finished_courses_and_new_enrollments_are_left_alone(): void
    {
        [$active, $a] = $this->enrolled(2, 30);
        $this->watched($a[0], 1);                         // estudió ayer
        [$done, $d] = $this->enrolled(1, 30);
        $this->watched($d[0], 20);                        // terminó hace tiempo
        $this->enrolled(2, 2);                            // se inscribió hace 2 días

        $this->run_();

        Mail::assertNothingQueued();
    }

    public function test_someone_who_never_started_is_nudged_after_days(): void
    {
        $this->enrolled(2, 8);

        $this->run_();

        Mail::assertQueued(ProgressReminderEmail::class, fn ($m) => $m->courses[0]['started'] === false && $m->courses[0]['progress'] === 0);
    }

    public function test_reminders_are_spaced_5_then_7_then_14_days_and_stop_after_three(): void
    {
        [$course, $lessons] = $this->enrolled(2, 40);
        $this->watched($lessons[0], 40);
        $set = fn (int $count, int $daysAgo) => ProgressReminder::updateOrCreate(['user_id' => $this->student()->id, 'course_id' => $course->id], ['count' => $count, 'last_sent_at' => now()->subDays($daysAgo)]);

        $this->run_();
        Mail::assertQueuedCount(1);                       // 1.º aviso

        $set(1, 3);  $this->run_();  Mail::assertQueuedCount(1);   // muy pronto para el 2.º
        $set(1, 8);  $this->run_();  Mail::assertQueuedCount(2);   // 2.º tras 7+ días
        $set(2, 10); $this->run_();  Mail::assertQueuedCount(2);   // el 3.º espera 14
        $set(2, 15); $this->run_();  Mail::assertQueuedCount(3);
        $set(3, 30); $this->run_();  Mail::assertQueuedCount(3);   // tope alcanzado
    }

    public function test_coming_back_resets_the_counter(): void
    {
        [$course, $lessons] = $this->enrolled(3, 40);
        ProgressReminder::create(['user_id' => $this->student()->id, 'course_id' => $course->id, 'count' => 3, 'last_sent_at' => now()->subDays(20)]);
        $this->watched($lessons[0], 6);                    // volvió después del último aviso y se detuvo de nuevo

        $this->run_();

        Mail::assertQueuedCount(1);
        $this->assertSame(1, ProgressReminder::where('user_id', $this->student()->id)->where('course_id', $course->id)->value('count'));
    }

    public function test_unsubscribing_stops_them_and_the_link_is_signed(): void
    {
        $this->enrolled(2, 10);
        $url = URL::signedRoute('progress-reminders.off', ['user' => $this->student()->id]);

        $this->get(route('progress-reminders.off', ['user' => $this->student()->id]))->assertForbidden();
        $this->get($url)->assertOk()->assertSee('no te enviaremos más recordatorios de avance');
        $this->assertNotNull($this->student()->fresh()->progress_reminders_off_at);

        $this->run_();
        Mail::assertNothingQueued();
    }

    public function test_it_respects_the_switch_the_sending_window_and_dry_run(): void
    {
        $this->enrolled(2, 10);

        config(['academy.progress_reminders' => false]);
        $this->run_();
        Mail::assertNothingQueued();

        config(['academy.progress_reminders' => true]);
        $this->artisan('app:send-progress-reminders', ['--dry-run' => true, '--force-hours' => true])->assertSuccessful();
        Mail::assertNothingQueued();
        $this->assertSame(0, ProgressReminder::count());
    }

    public function test_one_email_lists_up_to_three_courses_most_advanced_first(): void
    {
        $made = [];
        foreach ([1, 2, 3, 4] as $n) {
            [$c, $l] = $this->enrolled(4, 20);
            for ($i = 0; $i < $n - 1 && $i < 3; $i++) {
                $this->watched($l[$i], 12);
            }
            $made[$n] = $c;
        }

        $this->run_();

        Mail::assertQueuedCount(1);
        Mail::assertQueued(ProgressReminderEmail::class, fn ($m) => count($m->courses) === 3 && $m->courses[0]['progress'] >= $m->courses[1]['progress'] && $m->courses[1]['progress'] >= $m->courses[2]['progress']);
    }
}
