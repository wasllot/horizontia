<?php

namespace Tests\Feature;

use App\Livewire\Pages\Admin\Certificates\Certificates;
use App\Livewire\Pages\Student\CertificateList;
use App\Models\CourseCertificate;
use App\Services\CourseCertificateService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Models\Section;
use Modules\Courses\Models\Watchtime;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Certificados verificables: se emiten solos al terminar el curso, se verifican con un código público y el admin puede revocarlos. */
class CertificatesTest extends TestCase
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

    /** @return array{0: Course, 1: array<int, Curriculum>} */
    private function courseWithLessons(int $n = 2): array
    {
        $c = $this->course2()->replicate();
        $c->title = 'Curso certificado ' . uniqid();
        $c->slug = 'cert-' . uniqid();
        $c->status = 'active';
        $c->delivery = 'online';
        $c->save();
        $c->pricing()->create(['price' => 100, 'discount' => 0, 'final_price' => 100]);
        $section = Section::create(['course_id' => $c->id, 'title' => 'Sec', 'description' => 'Sec', 'sort_order' => 1]);
        $lessons = [];
        for ($i = 1; $i <= $n; $i++) {
            $lessons[] = Curriculum::create(['section_id' => $section->id, 'title' => "Lección $i", 'description' => 'x', 'type' => 'article', 'article_content' => '<p>x</p>', 'content_length' => 600, 'sort_order' => $i, 'is_preview' => false]);
        }
        Enrollment::updateOrCreate(['student_id' => $this->student()->id, 'course_id' => $c->id], ['tutor_id' => $c->instructor_id, 'status' => 'active']);
        $c->update(['content_length' => 600 * $n]);

        return [$c->fresh(), $lessons];
    }

    private function watch(Curriculum $l, ?int $seconds = null): void
    {
        Watchtime::create(['user_id' => $this->student()->id, 'curriculum_id' => $l->id, 'course_id' => $l->section->course_id, 'section_id' => $l->section_id, 'duration' => $seconds ?? $l->content_length]);
    }

    public function test_no_certificate_until_the_course_is_finished_then_one_only_once(): void
    {
        [$course, $lessons] = $this->courseWithLessons();
        $svc = app(CourseCertificateService::class);

        $this->watch($lessons[0]);
        $this->assertNull($svc->issueIfComplete($this->student(), $course));

        $this->watch($lessons[1]);
        $cert = $svc->issueIfComplete($this->student(), $course);
        $again = $svc->issueIfComplete($this->student(), $course);

        $this->assertNotNull($cert);
        $this->assertSame($cert->id, $again->id);
        $this->assertSame(1, CourseCertificate::where('user_id', $this->student()->id)->where('course_id', $course->id)->count());
        $this->assertMatchesRegularExpression('/^HZ-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $cert->code);
        $this->assertSame(1200, $cert->content_seconds);
    }

    public function test_it_is_off_for_in_person_courses_and_when_the_switch_is_off(): void
    {
        [$course, $lessons] = $this->courseWithLessons(1);
        $this->watch($lessons[0]);
        $svc = app(CourseCertificateService::class);

        config(['academy.certificates' => false]);
        $this->assertNull($svc->issueIfComplete($this->student(), $course));

        config(['academy.certificates' => true]);
        $course->update(['delivery' => 'in_person']);
        $this->assertNull($svc->issueIfComplete($this->student(), $course->fresh()));
    }

    public function test_the_certificate_keeps_the_name_and_course_of_the_day_it_was_issued(): void
    {
        [$course, $lessons] = $this->courseWithLessons(1);
        $this->watch($lessons[0]);
        $cert = app(CourseCertificateService::class)->issueIfComplete($this->student(), $course);
        $title = $cert->course_title;

        $course->update(['title' => 'Otro nombre']);

        $this->assertSame($title, $cert->fresh()->course_title);
    }

    public function test_finishing_the_last_lesson_in_the_classroom_issues_it_and_shows_the_link(): void
    {
        [$course, $lessons] = $this->courseWithLessons();
        $this->watch($lessons[0]);
        $this->watch($lessons[1]);

        $html = $this->actingAs($this->student())->get(route('courses.course-taking', ['slug' => $course->slug]))->assertOk()->getContent();

        $cert = CourseCertificate::where('user_id', $this->student()->id)->where('course_id', $course->id)->firstOrFail();
        $this->assertStringContainsString('data-testid="cert-link"', $html);
        $this->assertStringContainsString(str_replace('/', '\\/', $cert->url()), $html);
    }

    public function test_the_public_page_shows_the_certificate_and_works_without_login(): void
    {
        $cert = $this->makeCert();

        $this->get(route('certificates.show', ['code' => $cert->code]))->assertOk()
            ->assertSee($cert->student_name)->assertSee($cert->course_title)->assertSee($cert->code)->assertSee('Certificado auténtico')->assertHeader('X-Robots-Tag', 'noindex');
        $this->get('/certificado/' . strtolower($cert->code))->assertOk()->assertSee($cert->course_title);
    }

    public function test_an_unknown_or_revoked_code_is_clearly_not_valid(): void
    {
        $this->get('/certificado/HZ-AAAA-BBBB-CCCC')->assertNotFound()->assertSee('No encontramos ese certificado');

        $cert = $this->makeCert();
        $cert->update(['revoked_at' => now(), 'revoked_reason' => 'Emitido por error']);
        $this->get($cert->url())->assertOk()->assertSee('Certificado revocado')->assertSee('Emitido por error')->assertSee('REVOCADO');
    }

    public function test_the_verify_form_redirects_to_the_certificate(): void
    {
        $cert = $this->makeCert();

        $this->get('/verificar-certificado')->assertOk()->assertSee('Verificar un certificado');
        $this->get('/verificar-certificado?codigo=' . urlencode(' ' . strtolower($cert->code) . ' '))->assertRedirect(route('certificates.show', ['code' => $cert->code]));
    }

    public function test_the_student_lists_only_their_own_certificates(): void
    {
        $mine = $this->makeCert();
        $other = CourseCertificate::create(['code' => 'HZ-ZZZZ-ZZZZ-ZZZZ', 'user_id' => $this->tutor()->id, 'course_id' => $mine->course_id, 'student_name' => 'Otra Persona', 'course_title' => 'Curso ajeno', 'issued_at' => now()]);

        Livewire::actingAs($this->student())->test(CertificateList::class)->assertSee($mine->course_title)->assertDontSee('Curso ajeno');
        $this->actingAs($this->student())->get(route('student.certificate-list'))->assertOk()->assertSee('Mis certificados');
    }

    public function test_the_admin_searches_revokes_and_restores(): void
    {
        $cert = $this->makeCert();
        $page = Livewire::actingAs($this->admin())->test(Certificates::class);

        $page->set('search', $cert->code)->assertSee($cert->student_name);
        $page->set('search', 'no-existe-xyz')->assertDontSee($cert->student_name);

        $page->set('search', '')->call('askRevoke', $cert->id)->call('revoke')->assertHasErrors('reason');
        $page->set('reason', 'Emitido por error')->call('revoke')->assertHasNoErrors();
        $this->assertFalse($cert->fresh()->isValid());
        $page->call('restore', $cert->id);
        $this->assertTrue($cert->fresh()->isValid());
    }

    public function test_only_admins_reach_the_certificates_screen(): void
    {
        $this->assertNotSame(200, $this->actingAs($this->student())->get(route('admin.certificates'))->getStatusCode());
        Livewire::actingAs($this->student())->test(Certificates::class)->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.certificates'))->assertOk()->assertSee('Certificados');
    }

    public function test_the_backfill_issues_old_completions_once(): void
    {
        [$course, $lessons] = $this->courseWithLessons(1);
        $this->watch($lessons[0]);

        $this->assertGreaterThanOrEqual(1, app(CourseCertificateService::class)->backfill());
        $this->assertSame(0, app(CourseCertificateService::class)->backfill());
        $this->assertSame(1, CourseCertificate::where('course_id', $course->id)->count());
    }

    private function makeCert(): CourseCertificate
    {
        [$course, $lessons] = $this->courseWithLessons(1);
        $this->watch($lessons[0]);

        return app(CourseCertificateService::class)->issueIfComplete($this->student(), $course);
    }
}
