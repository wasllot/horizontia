<?php

namespace Tests\Feature;

use App\Models\CourseCertificate;
use App\Services\StudentOnboardingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Modules\Courses\Livewire\Pages\Student\CourseList\CourseList;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Models\Watchtime;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** «Primeros pasos» del estudiante: se calculan con sus datos reales y se pueden ocultar. */
class StudentOnboardingTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    private StudentOnboardingService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.certificates' => true]);
        $this->svc = new StudentOnboardingService();
        $u = $this->student();
        Enrollment::where('student_id', $u->id)->delete();
        Watchtime::where('user_id', $u->id)->delete();
        CourseCertificate::where('user_id', $u->id)->delete();
        $u->profile()->update(['image' => null]);
        $u->forceFill(['onboarding_dismissed_at' => null])->save();
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function enroll(): Course
    {
        $course = $this->course();
        Enrollment::updateOrCreate(['student_id' => $this->student()->id, 'course_id' => $course->id], ['tutor_id' => $course->instructor_id, 'status' => 'active']);

        return $course;
    }

    private function keys(?array $o, string $flag): array
    {
        return collect($o['steps'])->where('done', $flag === 'done')->pluck('key')->values()->all();
    }

    public function test_a_brand_new_student_has_everything_pending_and_starts_with_the_profile(): void
    {
        $o = $this->svc->forStudent($this->student()->fresh());

        $this->assertSame(0, $o['done']);
        $this->assertSame(4, $o['total']);
        $this->assertSame('profile', $o['next']);
        $this->assertSame(['profile', 'course', 'lesson', 'certificate'], $this->keys($o, 'pending'));
    }

    public function test_each_step_completes_with_real_activity_and_next_moves_on(): void
    {
        $u = $this->student();
        $u->profile()->update(['image' => 'profile_images/yo.png']);
        $o = $this->svc->forStudent($u->fresh());
        $this->assertSame(['profile'], $this->keys($o, 'done'));
        $this->assertSame('course', $o['next']);

        $course = $this->enroll();
        $o = $this->svc->forStudent($u->fresh());
        $this->assertSame(['profile', 'course'], $this->keys($o, 'done'));
        $this->assertSame('lesson', $o['next']);
        $this->assertStringContainsString($course->slug, collect($o['steps'])->firstWhere('key', 'lesson')['url'], 'it sends you to your own course');

        $lesson = Curriculum::whereHas('section', fn ($q) => $q->where('course_id', $course->id))->first();
        Watchtime::create(['user_id' => $u->id, 'curriculum_id' => $lesson->id, 'course_id' => $course->id, 'section_id' => $lesson->section_id, 'duration' => 30]);
        $o = $this->svc->forStudent($u->fresh());
        $this->assertSame(3, $o['done']);
        $this->assertSame('certificate', $o['next']);
    }

    public function test_it_disappears_when_everything_is_done_or_the_student_hides_it(): void
    {
        $u = $this->student();
        $u->profile()->update(['image' => 'profile_images/yo.png']);
        $course = $this->enroll();
        $lesson = Curriculum::whereHas('section', fn ($q) => $q->where('course_id', $course->id))->first();
        Watchtime::create(['user_id' => $u->id, 'curriculum_id' => $lesson->id, 'course_id' => $course->id, 'section_id' => $lesson->section_id, 'duration' => 30]);
        CourseCertificate::create(['user_id' => $u->id, 'course_id' => $course->id, 'code' => 'HZ-TEST-' . strtoupper(substr(uniqid(), -6)), 'student_name' => 'Ana', 'course_title' => 'X', 'issued_at' => now()]);
        $this->assertNull($this->svc->forStudent($u->fresh()), 'all done');

        CourseCertificate::where('user_id', $u->id)->delete();
        $this->assertNotNull($this->svc->forStudent($u->fresh()));
        $this->svc->dismiss($u);
        $this->assertNull($this->svc->forStudent($u->fresh()), 'hidden for good');
        $this->assertNotNull($u->fresh()->onboarding_dismissed_at);
    }

    public function test_the_certificate_step_only_exists_when_certificates_are_on_and_other_roles_get_nothing(): void
    {
        config(['academy.certificates' => false]);
        $o = $this->svc->forStudent($this->student()->fresh());
        $this->assertSame(3, $o['total']);
        $this->assertNotContains('certificate', array_column($o['steps'], 'key'));

        $this->assertNull($this->svc->forStudent($this->tutor()));
        $this->assertNull($this->svc->forStudent($this->admin()));
    }

    public function test_my_courses_shows_the_card_and_the_dismiss_button_hides_it(): void
    {
        $page = Livewire::actingAs($this->student())->test(CourseList::class)->assertSee('Tus primeros pasos')->assertSee('Completa tu perfil')->assertSee('Ir a mi perfil');

        $page->call('dismissOnboarding')->assertDontSee('Tus primeros pasos');
        $this->assertNotNull($this->student()->fresh()->onboarding_dismissed_at);

        // al buscar dentro de «Mis cursos» no estorba
        $this->student()->forceFill(['onboarding_dismissed_at' => null])->save();
        Livewire::actingAs($this->student())->test(CourseList::class)->set('keyword', 'excel')->assertDontSee('Tus primeros pasos');
    }
}
