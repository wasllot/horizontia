<?php

namespace Tests\Feature;

use App\Livewire\Frontend\LessonNotes;
use App\Models\LessonNote;
use App\Models\LessonPosition;
use App\Services\LessonProgressService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Models\Section;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Notas, marcadores y «retomar donde te quedaste» dentro de una lección: personales y solo para quien tiene acceso al curso. */
class LessonNotesTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    /** @return array{0: Course, 1: Curriculum} */
    private function lesson(bool $enroll = true, int $length = 600): array
    {
        $c = $this->course2()->replicate();
        $c->title = 'Notas ' . uniqid();
        $c->slug = 'nt-' . uniqid();
        $c->status = 'active';
        $c->delivery = 'online';
        $c->save();
        $section = Section::create(['course_id' => $c->id, 'title' => 'S', 'description' => 'S', 'sort_order' => 1]);
        $l = Curriculum::create(['section_id' => $section->id, 'title' => 'Lección', 'description' => 'x', 'type' => 'video', 'media_path' => 'x.mp4', 'content_length' => $length, 'sort_order' => 1, 'is_preview' => false]);
        $c->update(['content_length' => $length]);
        Enrollment::where(['student_id' => $this->student()->id, 'course_id' => $c->id])->delete();
        if ($enroll) {
            Enrollment::create(['student_id' => $this->student()->id, 'course_id' => $c->id, 'tutor_id' => $c->instructor_id, 'status' => 'active']);
        }

        return [$c->fresh(), $l];
    }

    private function notes(Course $c, Curriculum $l)
    {
        return Livewire::actingAs($this->student())->test(LessonNotes::class, ['courseId' => $c->id, 'curriculumId' => $l->id]);
    }

    public function test_a_student_adds_notes_with_the_video_minute_and_they_come_back_in_order(): void
    {
        [$c, $l] = $this->lesson();
        $page = $this->notes($c, $l);

        $page->set('body', 'Idea clave')->call('add', 125)->assertHasNoErrors();
        $page->set('body', 'Nota general')->call('add');
        $page->set('body', 'Al inicio')->call('add', 5);

        $page->assertSeeInOrder(['0:05', 'Al inicio', '2:05', 'Idea clave', 'Nota general']);
        $this->assertSame(3, LessonNote::where('user_id', $this->student()->id)->where('curriculum_id', $l->id)->count());
    }

    public function test_notes_are_validated_and_html_is_stripped(): void
    {
        [$c, $l] = $this->lesson();
        $page = $this->notes($c, $l);

        $page->set('body', '')->call('add')->assertHasErrors('body');
        $page->set('body', str_repeat('a', 2001))->call('add')->assertHasErrors('body');
        $page->set('body', '<script>alert(1)</script>Hola <b>mundo</b>')->call('add')->assertHasNoErrors();

        $this->assertSame('alert(1)Hola mundo', LessonNote::latest('id')->value('body'));
        $page->assertDontSee('<script>', false);
    }

    public function test_bookmarks_are_deduplicated_and_notes_can_be_deleted(): void
    {
        [$c, $l] = $this->lesson();
        $page = $this->notes($c, $l);

        $page->call('bookmark', 60)->call('bookmark', 60)->call('bookmark', 90);
        $this->assertSame(2, LessonNote::where('kind', 'bookmark')->where('curriculum_id', $l->id)->count());

        $id = LessonNote::where('curriculum_id', $l->id)->first()->id;
        $page->call('remove', $id);
        $this->assertNull(LessonNote::find($id));
    }

    public function test_nobody_reads_or_deletes_someone_elses_notes(): void
    {
        [$c, $l] = $this->lesson();
        $mine = LessonNote::create(['user_id' => $this->student()->id, 'course_id' => $c->id, 'curriculum_id' => $l->id, 'body' => 'Secreto de Ana']);
        $theirs = LessonNote::create(['user_id' => $this->demoStudent()->id, 'course_id' => $c->id, 'curriculum_id' => $l->id, 'body' => 'Secreto ajeno']);

        $this->notes($c, $l)->assertSee('Secreto de Ana')->assertDontSee('Secreto ajeno')->call('remove', $theirs->id);

        $this->assertNotNull(LessonNote::find($theirs->id), 'someone else\'s note is untouched');
        $this->assertNotNull(LessonNote::find($mine->id));
    }

    public function test_it_needs_access_to_the_course_and_the_lesson_must_belong_to_it(): void
    {
        [$c, $l] = $this->lesson(enroll: false);
        Livewire::actingAs($this->student())->test(LessonNotes::class, ['courseId' => $c->id, 'curriculumId' => $l->id])->assertForbidden();

        [$c2, $l2] = $this->lesson();
        Livewire::actingAs($this->student())->test(LessonNotes::class, ['courseId' => $c2->id, 'curriculumId' => $l->id])->assertForbidden();   // lesson from another course
        Livewire::actingAs($this->tutor())->test(LessonNotes::class, ['courseId' => $c2->id, 'curriculumId' => $l2->id])->assertForbidden();   // only students keep notes
    }

    public function test_the_position_endpoint_saves_and_resumes_and_a_finished_lesson_restarts(): void
    {
        [$c, $l] = $this->lesson(length: 600);
        $svc = app(LessonProgressService::class);
        $post = fn (int $s) => $this->actingAs($this->student())->postJson(route('courses.lesson-position'), ['curriculum_id' => $l->id, 'seconds' => $s]);

        $post(245)->assertOk();
        $this->assertSame(245, $svc->resumeAt($this->student(), $l->id));

        $post(3)->assertOk();
        $this->assertSame(0, $svc->resumeAt($this->student(), $l->id), 'too early to be worth resuming');

        $post(598)->assertOk();
        $this->assertSame(0, $svc->resumeAt($this->student(), $l->id), 'finished: starts over');
        $this->assertSame(1, LessonPosition::where('user_id', $this->student()->id)->where('curriculum_id', $l->id)->count(), 'one row per lesson');
    }

    public function test_the_position_endpoint_is_private_and_validated(): void
    {
        [$c, $l] = $this->lesson();
        [$c2, $other] = $this->lesson(enroll: false);

        $this->postJson(route('courses.lesson-position'), ['curriculum_id' => $l->id, 'seconds' => 10])->assertStatus(401);
        $this->actingAs($this->student())->postJson(route('courses.lesson-position'), ['curriculum_id' => $other->id, 'seconds' => 10])->assertForbidden();
        $this->actingAs($this->student())->postJson(route('courses.lesson-position'), ['curriculum_id' => $l->id, 'seconds' => -5])->assertStatus(422);
        $this->actingAs($this->student())->postJson(route('courses.lesson-position'), ['curriculum_id' => $l->id, 'seconds' => 'abc'])->assertStatus(422);
        $this->actingAs($this->tutor())->postJson(route('courses.lesson-position'), ['curriculum_id' => $l->id, 'seconds' => 10])->assertForbidden();
    }

    public function test_the_classroom_offers_the_notes_tab_and_the_resume_point_to_students_only(): void
    {
        [$c, $l] = $this->lesson();
        app(LessonProgressService::class)->save($this->student(), $l, 245);

        $html = $this->actingAs($this->student())->get(route('courses.course-taking', ['slug' => $c->slug]))->assertOk()->getContent();
        $this->assertStringContainsString('data-testid="notes-tab"', $html);
        $this->assertStringContainsString('crLessonVideo.track', $html);
        $this->assertMatchesRegularExpression('/crLessonVideo\.track\([^)]*, 245\)/', $html);
    }
}
