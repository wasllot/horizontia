<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Modules\Courses\Http\Requests\CourseMediaRequest;
use Modules\Courses\Livewire\Pages\Student\CourseTaking\CourseTaking;
use Modules\Courses\Livewire\Pages\Tutor\CourseCreation\Components\CoursePublish;
use Modules\Courses\Livewire\Pages\Tutor\CourseCreation\Components\ManageCourseContent\CourseBuilder;
use Modules\Courses\Models\AssignmentSubmission;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\CourseLiveStream;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Section;
use Modules\Courses\Services\CourseReadiness;
use Modules\Courses\Services\Lessons\LessonSaver;
use Modules\Courses\Services\Lessons\QuizService;
use Modules\Courses\Services\LiveSessionService;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * The course content builder (the heart of the wizard): who may touch a course, every lesson type end to end
 * (author saves it → the student gets only what they may see → the server grades / gates it), and the publish step.
 */
class CourseBuilderTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    public static string $slug = '';

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

    private function builder(User $user, ?Course $course = null)
    {
        return Livewire::actingAs($user)->test(CourseBuilder::class, ['id' => ($course ?? $this->course())->id]);
    }

    private function newLesson(string $type = 'video', ?Course $course = null, string $title = 'Lección de prueba'): Curriculum
    {
        $section = Section::firstOrCreate(['course_id' => ($course ?? $this->course())->id, 'title' => 'Sección builder'], ['description' => 'x']);

        return Curriculum::create(['section_id' => $section->id, 'title' => $title, 'type' => $type, 'sort_order' => 50 + Curriculum::count()]);
    }

    /** A course that belongs to another instructor than the QA tutor. */
    private function foreignCourse(): Course
    {
        $course = $this->course()->replicate();
        $course->slug = 'otro-curso-' . uniqid();
        $course->instructor_id = $this->demoTutor()->id;
        $course->save();

        return $course;
    }

    private function stepUrl(string $tab, int $id): string
    {
        return route('courses.tutor.edit-course', ['tab' => $tab, 'id' => $id]);
    }

    private function openLesson($component, Curriculum $lesson, string $group)
    {
        return $component->call('selectLesson', $lesson->id)->call('setGroup', $group);
    }

    // ───────────────────────────── authorization ─────────────────────────────

    public function test_an_instructor_cannot_open_another_instructors_course_in_the_builder(): void
    {
        $foreign = $this->foreignCourse();

        Livewire::actingAs($this->tutor())->test(CourseBuilder::class, ['id' => $foreign->id])->assertForbidden();
    }

    public function test_an_admin_can_open_any_course_in_the_builder(): void
    {
        $this->builder($this->admin())->assertOk();
    }

    public function test_the_builder_refuses_lessons_that_belong_to_another_course(): void
    {
        $foreign = $this->foreignCourse();
        $lesson = $this->newLesson('article', $foreign);

        $this->builder($this->tutor())->call('selectLesson', $lesson->id)->assertForbidden();
        $this->builder($this->tutor())->call('deleteLesson', $lesson->id)->assertForbidden();
        $this->builder($this->tutor())->call('duplicateLesson', $lesson->id)->assertForbidden();
        $this->assertTrue(Curriculum::whereKey($lesson->id)->exists());
    }

    public function test_the_course_id_cannot_be_tampered_with_from_the_browser(): void
    {
        $foreign = $this->foreignCourse();

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);
        $this->builder($this->tutor())->set('courseId', $foreign->id);
    }

    public function test_reordering_cannot_move_lessons_into_another_instructors_section(): void
    {
        $foreign = $this->foreignCourse();
        $theirSection = Section::create(['course_id' => $foreign->id, 'title' => 'Ajena']);
        $mine = $this->newLesson('article');

        $this->builder($this->tutor())->call('reorderLessons', $theirSection->id, [$mine->id])->assertForbidden();
        $this->assertNotEquals($theirSection->id, $mine->fresh()->section_id);
    }

    public function test_wizard_steps_of_a_foreign_course_are_forbidden(): void
    {
        $foreign = $this->foreignCourse();
        $tutor = $this->tutor();

        foreach (['details', 'media', 'pricing', 'content', 'faqs', 'noticeboard', 'discussion-forum', 'promotions', 'publish'] as $tab) {
            $status = $this->actingAs($tutor)->get($this->stepUrl($tab, $foreign->id))->getStatusCode();
            $this->assertContains($status, [403, 404, 302], "tab $tab leaked a foreign course (HTTP $status)");
        }
    }

    public function test_back_links_use_the_admin_routes_for_admins(): void
    {
        $this->actingAs($this->admin());
        $this->assertStringContainsString('/admin/', courseEditUrl('media', 20));
        $this->assertStringContainsString('/admin/', courseListUrl());

        $this->actingAs($this->tutor());
        $this->assertStringNotContainsString('/admin/', courseEditUrl('media', 20));
    }

    public function test_the_promotional_video_is_optional(): void
    {
        $request = new CourseMediaRequest();
        $validator = validator(['thumbnail' => 'data:image/png;base64,AAAA', 'promotionalVideo' => null], $request->rules());

        $this->assertFalse($validator->errors()->has('promotionalVideo'));
    }

    // ───────────────────────────── structure ─────────────────────────────

    public function test_sections_and_lessons_can_be_created_renamed_duplicated_reordered_and_deleted(): void
    {
        $course = $this->course();
        $component = $this->builder($this->tutor())
            ->set('newSectionTitle', 'Módulo nuevo')->call('addSection')->assertHasNoErrors();
        $section = Section::where('course_id', $course->id)->where('title', 'Módulo nuevo')->firstOrFail();

        $component->call('startAddLesson', $section->id)->set('newLessonTitle', 'Primera')->call('addLesson');
        $first = Curriculum::where('section_id', $section->id)->where('title', 'Primera')->firstOrFail();
        $component->call('startAddLesson', $section->id)->set('newLessonTitle', 'Segunda')->call('addLesson');
        $second = Curriculum::where('section_id', $section->id)->where('title', 'Segunda')->firstOrFail();

        $component->call('reorderLessons', $section->id, [$second->id, $first->id]);
        $this->assertSame([$second->id, $first->id], $section->fresh()->curriculums->pluck('id')->all());

        $component->call('duplicateLesson', $first->id);
        $this->assertSame(3, $section->fresh()->curriculums()->count());

        $component->call('startRenameSection', $section->id)->set('sectionTitle', 'Renombrado')->call('saveSectionTitle');
        $this->assertSame('Renombrado', $section->fresh()->title);

        $component->call('deleteLesson', $first->id);
        $this->assertFalse(Curriculum::whereKey($first->id)->exists());

        $component->call('deleteSection', $section->id);
        $this->assertFalse(Section::whereKey($section->id)->exists());
    }

    public function test_sections_keep_a_stable_user_defined_order(): void
    {
        $course = $this->course();
        $a = Section::create(['course_id' => $course->id, 'title' => 'A', 'sort_order' => 90]);
        $b = Section::create(['course_id' => $course->id, 'title' => 'B', 'sort_order' => 91]);

        $this->builder($this->tutor())->call('reorderSections', [$b->id, $a->id]);

        $ordered = Section::where('course_id', $course->id)->whereIn('id', [$a->id, $b->id])->orderBy('sort_order')->pluck('id')->all();
        $this->assertSame([$b->id, $a->id], $ordered);
        $this->assertSame([$b->id, $a->id], $course->fresh()->sections->whereIn('id', [$a->id, $b->id])->pluck('id')->values()->all());
    }

    // ───────────────────────────── lesson types ─────────────────────────────

    public function test_youtube_lessons_normalise_the_link_and_reject_other_hosts(): void
    {
        $lesson = $this->newLesson('video');
        $c = $this->openLesson($this->builder($this->tutor()), $lesson, 'video')->call('setSource', 'yt_link');

        $c->set('content.yt_link', 'https://evil.example.com/watch?v=dQw4w9WgXcQ')->call('saveLesson')->assertHasErrors('content.yt_link');

        $c->set('content.yt_link', 'https://youtu.be/dQw4w9WgXcQ?t=5')->call('saveLesson')->assertHasNoErrors();
        $lesson->refresh();
        $this->assertSame('yt_link', $lesson->type);
        $this->assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ', $lesson->media_path);
    }

    public function test_vimeo_lessons_store_a_canonical_link(): void
    {
        $lesson = $this->newLesson('video');
        $c = $this->openLesson($this->builder($this->tutor()), $lesson, 'video')->call('setSource', 'vm_link');

        $c->set('content.vm_link', 'https://example.com/123456')->call('saveLesson')->assertHasErrors('content.vm_link');
        $c->set('content.vm_link', 'https://vimeo.com/123456789')->call('saveLesson')->assertHasNoErrors();

        $this->assertSame('vm_link', $lesson->fresh()->type);
        $this->assertStringStartsWith('https://vimeo.com/123456789', $lesson->fresh()->media_path);
    }

    public function test_genially_lessons_only_accept_genially_https_links(): void
    {
        $lesson = $this->newLesson('video');
        $c = $this->openLesson($this->builder($this->tutor()), $lesson, 'video')->call('setSource', 'genially_link');

        $c->set('content.genially_link', 'http://view.genial.ly/abc123')->call('saveLesson')->assertHasErrors('content.genially_link');
        $c->set('content.genially_link', 'https://evil.example.com/abc')->call('saveLesson')->assertHasErrors('content.genially_link');
        $c->set('content.genially_link', 'https://view.genially.com/65a1b2c3d4e5f6a7b8c9d0e1')->call('saveLesson')->assertHasNoErrors();

        $this->assertSame('genially_link', $lesson->fresh()->type);
    }

    public function test_article_lessons_are_sanitised_and_get_a_reading_time(): void
    {
        $lesson = $this->newLesson('article');
        $html = '<p onclick="steal()">Hola <strong>mundo</strong></p><script>alert(1)</script><a href="javascript:alert(2)">x</a>'
            . '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>';

        $this->openLesson($this->builder($this->tutor()), $lesson, 'article')->set('content.article', $html)->call('saveLesson')->assertHasNoErrors();

        $saved = $lesson->fresh()->article_content;
        $this->assertStringContainsString('<strong>mundo</strong>', $saved);
        $this->assertStringNotContainsString('<script', $saved);
        $this->assertStringNotContainsString('onclick', $saved);
        $this->assertStringNotContainsString('javascript:', $saved);
        $this->assertStringContainsString('youtube.com/embed', $saved);
        $this->assertGreaterThan(0, (int) $lesson->fresh()->content_length);
    }

    public function test_an_empty_article_is_rejected(): void
    {
        $lesson = $this->newLesson('article');

        $this->openLesson($this->builder($this->tutor()), $lesson, 'article')->set('content.article', '<p> </p>')->call('saveLesson')->assertHasErrors();
    }

    private function quizPayload(): array
    {
        return [
            'pass_score' => 60, 'max_attempts' => 2, 'show_answers' => true, 'shuffle' => false,
            'questions' => [
                ['id' => 1, 'type' => 'single', 'text' => '¿2+2?', 'options' => ['3', '4', '5'], 'correct' => 1, 'explanation' => 'Aritmética'],
                ['id' => 2, 'type' => 'multiple', 'text' => 'Pares', 'options' => ['1', '2', '4'], 'correct' => [1, 2]],
                ['id' => 3, 'type' => 'true_false', 'text' => 'El cielo es azul', 'correct' => 0],
            ],
        ];
    }

    public function test_quiz_lessons_are_normalised_and_report_per_question_errors(): void
    {
        $lesson = $this->newLesson('assignment');
        $c = $this->openLesson($this->builder($this->tutor()), $lesson, 'quiz');

        $c->set('content.quiz', ['questions' => [['text' => 'Sin opciones', 'options' => ['solo una'], 'correct' => 0]]])->call('saveLesson')->assertHasErrors('content.quiz.0');
        $c->set('content.quiz', ['questions' => []])->call('saveLesson')->assertHasErrors('content.quiz');

        $c->set('content.quiz', $this->quizPayload())->call('saveLesson')->assertHasNoErrors();
        $stored = json_decode($lesson->fresh()->article_content, true);
        $this->assertCount(3, $stored['questions']);
        $this->assertSame(['Verdadero', 'Falso'], $stored['questions'][2]['options']);
        $this->assertSame('assignment', $lesson->fresh()->type);
    }

    public function test_quiz_service_never_exposes_answers_to_students_and_grades_on_the_server(): void
    {
        $service = new QuizService();
        $quiz = $service->normalize($this->quizPayload());

        $visible = json_encode($service->forStudent($quiz));
        $this->assertStringNotContainsString('correct', $visible);
        $this->assertStringNotContainsString('Aritmética', $visible);

        $perfect = $service->grade($quiz, [1 => 1, 2 => [1, 2], 3 => 0]);
        $this->assertSame(100, $perfect['score']);
        $this->assertTrue($perfect['passed']);

        $partial = $service->grade($quiz, [1 => 1, 2 => [1], 3 => 1]);   // only the first one right
        $this->assertSame(33, $partial['score']);
        $this->assertFalse($partial['passed']);

        $this->assertSame(0, $service->grade($quiz, [])['score']);
    }

    public function test_live_lessons_create_a_linked_session_and_announce_it(): void
    {
        $lesson = $this->newLesson('live', title: 'Clase en vivo');
        $when = now()->addDays(3)->format('Y-m-d\TH:i');
        $c = $this->openLesson($this->builder($this->tutor()), $lesson, 'live');

        $c->set('content.live', ['date_time' => now()->subDay()->format('Y-m-d\TH:i'), 'duration_minutes' => 60, 'meeting_link' => 'https://meet.google.com/abc-defg-hij', 'notify_hours_before' => 24])
            ->call('saveLesson')->assertHasErrors('content.live.date_time');
        $c->set('content.live', ['date_time' => $when, 'duration_minutes' => 60, 'meeting_link' => 'http://insecure.example.com', 'notify_hours_before' => 24])
            ->call('saveLesson')->assertHasErrors('content.live.meeting_link');

        $c->set('content.live', ['date_time' => $when, 'duration_minutes' => 90, 'meeting_link' => 'https://meet.google.com/abc-defg-hij', 'notify_hours_before' => 24, 'description' => '', 'recording_url' => ''])
            ->call('saveLesson')->assertHasNoErrors();

        $stream = CourseLiveStream::where('curriculum_id', $lesson->id)->firstOrFail();
        $this->assertSame($this->course()->id, $stream->course_id);
        $this->assertSame('meet', $stream->provider);
        $this->assertSame(90, (int) $stream->duration_minutes);
        $this->assertTrue($lesson->fresh()->liveStream->is($stream));

        // Saving again updates the same session instead of creating a second one.
        $c->set('content.live.duration_minutes', 45)->call('saveLesson')->assertHasNoErrors();
        $this->assertSame(1, CourseLiveStream::where('curriculum_id', $lesson->id)->count());
        $this->assertSame(45, (int) $stream->fresh()->duration_minutes);

        // Deleting the lesson removes its session.
        $c->call('deleteLesson', $lesson->id);
        $this->assertFalse(CourseLiveStream::whereKey($stream->id)->exists());
    }

    public function test_changing_a_lesson_type_discards_the_previous_content(): void
    {
        $lesson = $this->newLesson('live');
        $this->openLesson($this->builder($this->tutor()), $lesson, 'live')
            ->set('content.live', ['date_time' => now()->addDays(2)->format('Y-m-d\TH:i'), 'duration_minutes' => 60, 'meeting_link' => 'https://zoom.us/j/123', 'notify_hours_before' => 1])
            ->call('saveLesson')->assertHasNoErrors();
        $this->assertSame(1, CourseLiveStream::where('curriculum_id', $lesson->id)->count());

        $this->openLesson($this->builder($this->tutor()), $lesson->fresh(), 'article')->set('content.article', '<p>Ahora es un artículo</p>')->call('saveLesson')->assertHasNoErrors();

        $this->assertSame('article', $lesson->fresh()->type);
        $this->assertSame(0, CourseLiveStream::where('curriculum_id', $lesson->id)->count());
    }

    public function test_the_saver_knows_when_a_lesson_is_complete(): void
    {
        $saver = new LessonSaver();
        $empty = $this->newLesson('article');
        $this->assertFalse($saver->isComplete($empty));

        $empty->update(['article_content' => '<p>Texto</p>']);
        $this->assertTrue($saver->isComplete($empty->fresh()));
    }

    public function test_scorm_extraction_rejects_executables_and_path_traversal(): void
    {
        $service = new \Modules\Courses\Services\ScormService();
        $zipPath = tempnam(sys_get_temp_dir(), 'evil_') . '.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('imsmanifest.xml', '<manifest/>');
        $zip->addFromString('index.html', '<html></html>');
        $zip->addFromString('shell.php', '<?php system($_GET["c"]);');
        $zip->addFromString('.htaccess', 'AddType application/x-httpd-php .html');
        $zip->addFromString('../escape.html', 'nope');
        $zip->close();

        $target = sys_get_temp_dir() . '/scorm-test-' . uniqid();
        $za = new \ZipArchive();
        $za->open($zipPath);
        try {
            $service->safeExtract($za, $target);
        } catch (\Throwable $e) {
            // rejecting the whole package is also an acceptable, safe outcome
        }
        $za->close();

        $this->assertFileDoesNotExist($target . '/shell.php');
        $this->assertFileDoesNotExist($target . '/.htaccess');
        $this->assertFileDoesNotExist(dirname($target) . '/escape.html');

        @unlink($zipPath);
        \Illuminate\Support\Facades\File::deleteDirectory($target);
    }

    // ───────────────────────────── student side ─────────────────────────────

    private function player(User $student, Course $course)
    {
        self::$slug = $course->slug;

        return Livewire::actingAs($student)->test(new class extends CourseTaking {
            public function mount()
            {
                $route = new \Illuminate\Routing\Route('GET', 'aula/{slug}', []);
                $route->bind(request());
                $route->setParameter('slug', CourseBuilderTest::$slug);
                request()->setRouteResolver(fn () => $route);
                parent::mount();
            }

            public function render()
            {
                return '<div></div>';
            }

            public function setActiveCurriculum($curriculum)
            {
                parent::setActiveCurriculum($curriculum);
            }

            public function submitQuiz(array $answers = []): array
            {
                return parent::submitQuiz($answers);
            }

            public function refreshLive(): void
            {
                parent::refreshLive();
            }
        });
    }

    public function test_the_student_player_hides_quiz_answers_and_grades_on_the_server(): void
    {
        $lesson = $this->newLesson('assignment', title: 'Test final');
        $lesson->update(['article_content' => json_encode((new QuizService())->normalize($this->quizPayload()))]);
        $student = $this->student();

        $player = $this->player($student, $this->course())->call('setActiveCurriculum', $lesson->id);
        $payload = json_encode($player->get('activeCurriculum'));
        $this->assertStringNotContainsString('Aritmética', $payload);
        $this->assertStringNotContainsString('"correct"', $payload);

        $fail = $player->instance()->submitQuiz([1 => 0, 2 => [0], 3 => 1]);
        $this->assertFalse($fail['passed']);
        $this->assertSame(0, $fail['score']);
        $this->assertSame(1, $fail['attempts']);

        $ok = $player->instance()->submitQuiz([1 => 1, 2 => [1, 2], 3 => 0]);
        $this->assertTrue($ok['passed']);
        $this->assertSame(100, $ok['score']);

        // max_attempts = 2 → a third try is refused and does not alter the stored result.
        $third = $player->instance()->submitQuiz([1 => 0]);
        $this->assertSame('attempts', $third['error']);
        $submission = AssignmentSubmission::where(['curriculum_id' => $lesson->id, 'user_id' => $student->id])->firstOrFail();
        $this->assertSame(2, (int) $submission->attempts);
        $this->assertSame(100, (int) $submission->score);
        $this->assertNotNull($submission->passed_at);
    }

    public function test_quiz_review_is_withheld_when_the_author_hides_the_answers(): void
    {
        $lesson = $this->newLesson('assignment', title: 'Test sin revisión');
        $payload = $this->quizPayload();
        $payload['show_answers'] = false;
        $payload['max_attempts'] = 0;
        $lesson->update(['article_content' => json_encode((new QuizService())->normalize($payload))]);

        $result = $this->player($this->student(), $this->course())->call('setActiveCurriculum', $lesson->id)->instance()->submitQuiz([1 => 0]);

        foreach ($result['results'] as $r) {
            $this->assertArrayNotHasKey('correct_answer', $r);
            $this->assertArrayNotHasKey('explanation', $r);
        }
    }

    public function test_the_live_join_link_is_only_given_inside_the_join_window(): void
    {
        $lesson = $this->newLesson('live', title: 'En vivo');
        $stream = CourseLiveStream::create([
            'course_id' => $this->course()->id, 'curriculum_id' => $lesson->id, 'title' => 'En vivo', 'meeting_link' => 'https://meet.google.com/aaa-bbbb-ccc',
            'provider' => 'meet', 'date_time' => now()->addDays(2), 'duration_minutes' => 60, 'notify_hours_before' => 24, 'status' => CourseLiveStream::STATUS_SCHEDULED,
        ]);
        $player = $this->player($this->student(), $this->course())->call('setActiveCurriculum', $lesson->id);

        $live = $player->get('activeCurriculum')['live'];
        $this->assertSame('upcoming', $live['phase']);
        $this->assertNull($live['join_url']);
        $this->assertStringNotContainsString('meet.google.com', json_encode($player->get('activeCurriculum')));

        $stream->update(['date_time' => now()->addMinutes(10)]);
        $player->instance()->refreshLive();
        $this->assertSame('soon', $player->get('activeCurriculum')['live']['phase']);
        $this->assertSame('https://meet.google.com/aaa-bbbb-ccc', $player->get('activeCurriculum')['live']['join_url']);

        $stream->update(['date_time' => now()->subHours(3), 'recording_url' => 'https://example.com/rec']);
        $player->instance()->refreshLive();
        $ended = $player->get('activeCurriculum')['live'];
        $this->assertSame('ended', $ended['phase']);
        $this->assertNull($ended['join_url']);
        $this->assertSame('https://example.com/rec', $ended['recording_url']);
    }

    public function test_deleted_courses_disappear_from_the_site_and_models_accept_attributes(): void
    {
        $course = $this->foreignCourse();
        $course->title = 'Curso para eliminar ' . uniqid();
        $course->status = 'active';
        $course->save();

        $this->assertTrue(Course::whereKey($course->id)->exists());
        $course->delete();

        $this->assertFalse(Course::whereKey($course->id)->exists(), 'a soft-deleted course must not be queryable');
        $this->assertTrue(Course::withTrashed()->whereKey($course->id)->exists());
        $this->get(route('courses.course-detail', ['slug' => $course->slug]))->assertNotFound();

        $this->assertSame('Hola', (new Section(['title' => 'Hola']))->title, 'constructors must pass attributes to the model');
    }

    public function test_the_meeting_link_is_not_printed_anywhere_before_the_join_window(): void
    {
        $link = 'https://meet.google.com/zzz-yyyy-xxx';
        $stream = CourseLiveStream::create([
            'course_id' => $this->course()->id, 'title' => 'Sesión futura', 'meeting_link' => $link,
            'date_time' => now()->addDays(2), 'duration_minutes' => 60, 'status' => CourseLiveStream::STATUS_SCHEDULED,
        ]);

        $this->actingAs($this->student())->get(route('courses.course-taking', ['slug' => $this->course()->slug]))->assertOk()->assertDontSee($link, false);
        $this->actingAs($this->student())->get(route('courses.course-detail', ['slug' => $this->course()->slug]))->assertOk()->assertDontSee($link, false);

        $stream->update(['date_time' => now()->addMinutes(5)]);
        $this->actingAs($this->student())->get(route('courses.course-taking', ['slug' => $this->course()->slug]))->assertOk()->assertSee($link, false);
    }

    public function test_live_phases_and_calendar_file(): void
    {
        $stream = new CourseLiveStream(['date_time' => now()->addHour(), 'duration_minutes' => 60, 'title' => 'Sesión', 'status' => CourseLiveStream::STATUS_SCHEDULED]);
        $this->assertSame('upcoming', LiveSessionService::phase($stream));
        $stream->date_time = now()->addMinutes(5);
        $this->assertSame('soon', LiveSessionService::phase($stream));
        $stream->date_time = now()->subMinutes(10);
        $this->assertSame('live', LiveSessionService::phase($stream));
        $stream->date_time = now()->subHours(2);
        $this->assertSame('ended', LiveSessionService::phase($stream));

        $ics = LiveSessionService::ics($stream);
        $this->assertStringContainsString('BEGIN:VCALENDAR', $ics);
        $this->assertStringContainsString('SUMMARY:Sesión', $ics);
    }

    public function test_the_calendar_download_is_limited_to_people_with_access(): void
    {
        $stream = CourseLiveStream::create([
            'course_id' => $this->course()->id, 'title' => 'Sesión ICS', 'meeting_link' => 'https://zoom.us/j/1',
            'date_time' => now()->addDays(1), 'duration_minutes' => 60, 'status' => CourseLiveStream::STATUS_SCHEDULED,
        ]);
        $url = route('courses.live.ics', ['stream' => $stream->id]);

        $this->get($url)->assertRedirect();   // guests go to login
        $this->actingAs($this->student())->get($url)->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8');

        $outsider = User::where('email', 'student@amentotech.com')->firstOrFail();
        $this->actingAs($outsider)->get($url)->assertForbidden();
    }

    // ───────────────────────────── publish step ─────────────────────────────

    private function draftCourse(): Course
    {
        $course = $this->foreignCourse();
        $course->instructor_id = $this->tutor()->id;
        $course->status = 'draft';
        $course->save();

        return $course;
    }

    private function publisher(User $user, Course $course)
    {
        return Livewire::actingAs($user)->test(new class extends CoursePublish {
            public function mount(): void
            {
                parent::mount();
            }
        });
    }

    public function test_readiness_blocks_publishing_while_a_lesson_is_empty(): void
    {
        $course = $this->draftCourse();
        $lesson = $this->newLesson('article', $course, 'Vacía');
        $readiness = new CourseReadiness();

        $this->assertFalse($readiness->ready($course->fresh()));
        $failing = collect($readiness->checks($course->fresh()))->where('ok', false)->where('required', true)->pluck('key')->all();
        $this->assertContains('complete', $failing);

        $lesson->update(['article_content' => '<p>Listo</p>']);
        $complete = collect($readiness->checks($course->fresh()))->firstWhere('key', 'complete');
        $this->assertTrue($complete['ok']);
    }

    public function test_publish_screen_renders_for_the_owner_and_not_for_others(): void
    {
        $course = $this->draftCourse();

        $this->actingAs($this->tutor())->get($this->stepUrl('publish', $course->id))->assertOk()->assertSee('data-testid="publish-btn"', false);
        $this->actingAs($this->demoTutor())->get($this->stepUrl('publish', $course->id))->assertForbidden();
    }

    public function test_cleaning_lesson_html_keeps_safe_markup_only(): void
    {
        $clean = cleanLessonHtml('<h2 onmouseover="x()">Título</h2><script>1</script><a href="javascript:alert(1)">l</a><iframe src="https://evil.example.com/x"></iframe>');

        $this->assertStringContainsString('Título', $clean);
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('onmouseover', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('evil.example.com', $clean);
    }
}
