<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Modules\Courses\Livewire\Pages\Student\CourseList\CourseList;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Models\Section;
use Modules\Courses\Models\Watchtime;
use Modules\Courses\Services\ResumeService;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** "Continuar donde lo dejaste": Mis cursos y el aula retoman en la primera lección sin completar. */
class ResumeLearningTest extends TestCase
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

    /** @return array{0: Course, 1: array<int, Curriculum>} */
    private function courseWithLessons(int $n = 3): array
    {
        $c = $this->course2()->replicate();
        $c->title = 'Retomar ' . uniqid();
        $c->slug = 'retomar-' . uniqid();
        $c->status = 'active';
        $c->save();
        $c->pricing()->create(['price' => 100, 'discount' => 0, 'final_price' => 100]);
        $section = Section::create(['course_id' => $c->id, 'title' => 'Sec', 'description' => 'Sec', 'sort_order' => 1]);
        $lessons = [];
        for ($i = 1; $i <= $n; $i++) {
            $lessons[] = Curriculum::create(['section_id' => $section->id, 'title' => "Lección $i", 'description' => 'x', 'type' => 'article', 'article_content' => '<p>x</p>', 'content_length' => 60, 'sort_order' => $i, 'is_preview' => false]);
        }
        Enrollment::updateOrCreate(['student_id' => $this->student()->id, 'course_id' => $c->id], ['tutor_id' => $c->instructor_id, 'status' => 'active']);
        $c->update(['content_length' => 60 * $n]);

        return [$c->fresh(), $lessons];
    }

    private function complete(Curriculum $l): void
    {
        Watchtime::create(['user_id' => $this->student()->id, 'curriculum_id' => $l->id, 'course_id' => $l->section->course_id, 'section_id' => $l->section_id, 'duration' => $l->content_length]);
    }

    public function test_the_resume_point_is_the_first_lesson_not_completed(): void
    {
        [$course, $lessons] = $this->courseWithLessons();
        $svc = new ResumeService();

        $this->assertSame($lessons[0]->id, $svc->resume($this->student(), $course)['lesson']->id);

        $this->complete($lessons[0]);
        $r = $svc->resume($this->student(), $course);
        $this->assertSame($lessons[1]->id, $r['lesson']->id);
        $this->assertSame([1, 3], [$r['done'], $r['total']]);

        $this->complete($lessons[1]);
        $this->complete($lessons[2]);
        $this->assertNull($svc->resume($this->student(), $course), 'nothing left to resume');
    }

    public function test_my_courses_says_continue_with_the_lesson_and_links_to_it(): void
    {
        [$course, $lessons] = $this->courseWithLessons();
        $this->complete($lessons[0]);

        $html = Livewire::actingAs($this->student())->test(CourseList::class)->call('loadCoursesData')->html();

        $this->assertStringContainsString('Continuar: Lección 2', $html);
        $this->assertStringContainsString('leccion=' . $lessons[1]->id, $html);
    }

    public function test_a_course_without_progress_says_start_and_a_finished_one_says_review(): void
    {
        [$course, $lessons] = $this->courseWithLessons(1);

        $this->assertStringContainsString('Comenzar curso', Livewire::actingAs($this->student())->test(CourseList::class)->call('loadCoursesData')->html());

        $this->complete($lessons[0]);
        $this->assertStringContainsString('Repasar curso', Livewire::actingAs($this->student())->test(CourseList::class)->call('loadCoursesData')->html());
    }

    public function test_the_classroom_opens_at_the_resume_lesson_unless_another_is_requested(): void
    {
        [$course, $lessons] = $this->courseWithLessons();
        $this->complete($lessons[0]);

        $this->actingAs($this->student())->get(route('courses.course-taking', ['slug' => $course->slug]))->assertOk()->assertSee('Lección 2');
        $res = $this->actingAs($this->student())->get(route('courses.course-taking', ['slug' => $course->slug, 'leccion' => $lessons[2]->id]))->assertOk();
        $this->assertStringContainsString('wire:snapshot', $res->getContent());
        // A lesson id of another course is ignored (no exposure).
        $other = Curriculum::first();
        $this->actingAs($this->student())->get(route('courses.course-taking', ['slug' => $course->slug, 'leccion' => $other->id]))->assertOk();
    }

    public function test_the_empty_state_invites_to_explore(): void
    {
        Enrollment::where('student_id', $this->student()->id)->delete();

        Livewire::actingAs($this->student())->test(CourseList::class)->call('loadCoursesData')->assertSee('Aún no tienes cursos')->assertSee('Explorar cursos');
    }
}
