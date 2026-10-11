<?php

namespace Tests\Feature;

use App\Livewire\Pages\Admin\LearningPaths\LearningPaths;
use App\Models\CourseCertificate;
use App\Models\LearningPath;
use App\Services\CourseCertificateService;
use App\Services\LearningPathService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Models\Section;
use Modules\Courses\Models\Watchtime;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Rutas de aprendizaje: orden recomendado, avance por persona y certificado al terminar toda la ruta. */
class LearningPathsTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.certificates' => true]);
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function course(bool $enroll = true): array
    {
        $c = $this->course2()->replicate();
        $c->title = 'Curso ruta ' . uniqid();
        $c->slug = 'cr-' . uniqid();
        $c->status = 'active';
        $c->delivery = 'online';
        $c->save();
        $c->pricing()->create(['price' => 100, 'discount' => 0, 'final_price' => 100]);
        $section = Section::create(['course_id' => $c->id, 'title' => 'Sec', 'description' => 'Sec', 'sort_order' => 1]);
        $lesson = Curriculum::create(['section_id' => $section->id, 'title' => 'L1', 'description' => 'x', 'type' => 'article', 'article_content' => '<p>x</p>', 'content_length' => 600, 'sort_order' => 1, 'is_preview' => false]);
        $c->update(['content_length' => 600]);
        if ($enroll) {
            Enrollment::updateOrCreate(['student_id' => $this->student()->id, 'course_id' => $c->id], ['tutor_id' => $c->instructor_id, 'status' => 'active']);
        }

        return [$c->fresh(), $lesson];
    }

    private function finish(Curriculum $l): void
    {
        Watchtime::create(['user_id' => $this->student()->id, 'curriculum_id' => $l->id, 'course_id' => $l->section->course_id, 'section_id' => $l->section_id, 'duration' => $l->content_length]);
    }

    /** @return array{0: LearningPath, 1: array<int, array{0: Course, 1: Curriculum}>} */
    private function path(int $n = 3, string $status = 'active', bool $enroll = true): array
    {
        $items = array_map(fn () => $this->course($enroll), range(1, $n));
        $p = LearningPath::create(['title' => 'Ruta ' . uniqid(), 'slug' => 'ruta-' . uniqid(), 'status' => $status]);
        $p->courses()->sync(collect($items)->mapWithKeys(fn ($it, $i) => [$it[0]->id => ['sort' => $i + 1]])->all());

        return [$p->fresh('courses'), $items];
    }

    public function test_progress_and_the_next_step_follow_the_order(): void
    {
        [$p, $items] = $this->path();
        $svc = app(LearningPathService::class);

        $s = $svc->status($p, $this->student());
        $this->assertSame([0, 3, 0], [$s['done'], $s['total'], $s['percent']]);
        $this->assertSame($items[0][0]->id, $s['next']->id);

        $this->finish($items[0][1]);
        $s = $svc->status($p->fresh('courses'), $this->student());
        $this->assertSame(1, $s['done']);
        $this->assertSame(33, $s['percent']);
        $this->assertSame($items[1][0]->id, $s['next']->id);
        $this->assertFalse($s['complete']);
    }

    public function test_finishing_the_last_course_issues_the_path_certificate_once(): void
    {
        [$p, $items] = $this->path(2);
        $certs = app(CourseCertificateService::class);

        $this->finish($items[0][1]);
        $certs->issueIfComplete($this->student(), $items[0][0]);
        $this->assertSame(0, CourseCertificate::where('user_id', $this->student()->id)->where('learning_path_id', $p->id)->count(), 'one course is not the whole path');

        $this->finish($items[1][1]);
        $certs->issueIfComplete($this->student(), $items[1][0]);
        $certs->issueIfComplete($this->student(), $items[1][0]);

        $cert = CourseCertificate::where('user_id', $this->student()->id)->where('learning_path_id', $p->id)->firstOrFail();
        $this->assertSame($p->title, $cert->course_title);
        $this->assertNull($cert->course_id);
        $this->get($cert->url())->assertOk()->assertSee('la ruta de aprendizaje')->assertSee($p->title);
    }

    public function test_the_public_pages_list_active_paths_and_hide_drafts(): void
    {
        [$active] = $this->path(2, 'active');
        [$draft] = $this->path(2, 'draft');

        $this->get(route('paths.index'))->assertOk()->assertSee($active->title)->assertDontSee($draft->title);
        $this->get(route('paths.show', ['slug' => $active->slug]))->assertOk()->assertSee($active->title);
        $this->get(route('paths.show', ['slug' => $draft->slug]))->assertNotFound();
    }

    public function test_a_student_sees_their_progress_and_the_next_step_a_guest_does_not(): void
    {
        [$p, $items] = $this->path(3);
        $this->finish($items[0][1]);

        $this->get(route('paths.show', ['slug' => $p->slug]))->assertOk()->assertDontSee('data-testid="path-progress"', false)->assertSee('Inicia sesión');
        $html = $this->actingAs($this->student())->get(route('paths.show', ['slug' => $p->slug]))->assertOk()->assertSee('1 de 3 cursos terminados')->assertSee('data-testid="path-next"', false)->getContent();
        $this->assertSame(3, substr_count($html, 'data-testid="path-step"'));
    }

    public function test_the_path_page_issues_a_certificate_for_courses_finished_before_the_path_existed(): void
    {
        [$p, $items] = $this->path(2);
        foreach ($items as [$course, $lesson]) {
            $this->finish($lesson);
            app(CourseCertificateService::class)->issueIfComplete($this->student(), $course);   // already had course certificates
        }
        CourseCertificate::where('learning_path_id', $p->id)->delete();

        $this->actingAs($this->student())->get(route('paths.show', ['slug' => $p->slug]))->assertOk()->assertSee('data-testid="path-cert"', false);
        $this->assertSame(1, CourseCertificate::where('learning_path_id', $p->id)->count());
    }

    public function test_the_admin_builds_orders_validates_and_deletes_paths(): void
    {
        [$a] = $this->course();
        [$b] = $this->course();
        $page = Livewire::actingAs($this->admin())->test(LearningPaths::class)->call('create');

        $page->set('title', 'Mi ruta')->set('courseIds', [(string) $a->id])->call('save')->assertHasErrors('courseIds');
        $page->set('courseIds', [(string) $a->id, (string) $b->id])->set('status', 'active')->call('save')->assertHasNoErrors();
        $path = LearningPath::where('title', 'Mi ruta')->firstOrFail();
        $this->assertSame([$a->id, $b->id], $path->courses->pluck('id')->all());

        $page->call('edit', $path->id)->call('move', $a->id, 'down')->call('save');
        $this->assertSame([$b->id, $a->id], $path->fresh('courses')->courses->pluck('id')->all());

        $page->call('toggleStatus', $path->id);
        $this->assertFalse($path->fresh()->isActive());
        $page->call('delete', $path->id);
        $this->assertNull(LearningPath::find($path->id));
    }

    public function test_only_admins_reach_the_paths_screen(): void
    {
        $this->assertNotSame(200, $this->actingAs($this->student())->get(route('admin.learning-paths'))->getStatusCode());
        Livewire::actingAs($this->student())->test(LearningPaths::class)->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.learning-paths'))->assertOk();
    }
}
