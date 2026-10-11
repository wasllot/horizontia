<?php

namespace Tests\Feature;

use App\Jobs\CompletePurchaseJob;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\DemoAcademySeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Larabuild\Optionbuilder\Facades\Settings;
use Livewire\Livewire;
use Modules\Courses\Jobs\ClearCourseFundsJob;
use Modules\Courses\Livewire\Pages\Search\SearchCourses;
use Modules\Courses\Livewire\Pages\Student\CourseList\CourseList;
use Modules\Courses\Livewire\Pages\Student\CourseTaking\CourseTaking;
use Modules\Courses\Models\AssignmentSubmission;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Enrollment;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * Seeds the demo academy (DemoAcademySeeder) inside a transaction and walks the real user
 * journeys over it: catalog -> purchase -> access -> quiz -> certificate-ready progress.
 * Doubles as the check that the seeder itself works and stays idempotent.
 */
class DemoAcademyFlowsTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    private const SECRET = 'demo-flow-secret';

    public static string $slug = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        Storage::fake('public');
        Bus::fake([ClearCourseFundsJob::class]);
        (new DemoAcademySeeder())->run();
    }

    protected function tearDown(): void
    {
        foreach (Curriculum::where('type', 'scorm')->pluck('id') as $id) {
            File::deleteDirectory(public_path('storage/scorm/' . $id));
        }
        parent::tearDown();
    }

    /**
     * Livewire::test() picks the wrong root for this full-page component (its layout embeds another
     * Livewire component), so test a copy that renders without the layout. All logic is inherited.
     */
    private function courseTaking(User $student, Course $course): \Livewire\Features\SupportTesting\Testable
    {
        self::$slug = $course->slug;

        return Livewire::actingAs($student)->test(new class extends CourseTaking {
            public function mount()
            {
                // Livewire's test request is not routed, so bind the {slug} parameter the real route provides.
                $route = new \Illuminate\Routing\Route('GET', 'course-taking/{slug}', []);
                $route->bind(request());
                $route->setParameter('slug', DemoAcademyFlowsTest::$slug);
                request()->setRouteResolver(fn () => $route);

                parent::mount();
            }

            public function render()
            {
                return '<div></div>';
            }

            // Livewire only exposes methods declared on the instantiated class, so re-declare the ones under test.
            public function setActiveCurriculum($curriculum)
            {
                parent::setActiveCurriculum($curriculum);
            }

            public function nextCurriculum($id)
            {
                parent::nextCurriculum($id);
            }

            public function submitQuiz(array $answers = []): array
            {
                return parent::submitQuiz($answers);
            }
        });
    }

    private function u(string $name): User
    {
        return User::where('email', "alumno.$name@horizontia.test")->firstOrFail();
    }

    private function demo(string $slug): Course
    {
        return Course::where('slug', $slug)->firstOrFail();
    }

    public function test_seeder_is_idempotent_and_builds_a_complete_catalog(): void
    {
        $courses = Course::where('slug', 'like', 'demo-%')->count();
        $lessons = Curriculum::whereHas('section.course', fn ($q) => $q->where('slug', 'like', 'demo-%'))->count();
        $orders = Order::where('email', 'like', '%@horizontia.test')->count();

        (new DemoAcademySeeder())->run();

        $this->assertSame(9, $courses);   // 5 online + 4 in-person demo events
        $this->assertSame($courses, Course::where('slug', 'like', 'demo-%')->count());
        $this->assertSame($lessons, Curriculum::whereHas('section.course', fn ($q) => $q->where('slug', 'like', 'demo-%'))->count());
        $this->assertSame($orders, Order::where('email', 'like', '%@horizontia.test')->count());

        $types = Curriculum::whereHas('section.course', fn ($q) => $q->where('slug', 'like', 'demo-%'))->pluck('type')->unique()->all();
        foreach (['article', 'video', 'yt_link', 'vm_link', 'assignment', 'scorm'] as $type) {
            $this->assertContains($type, $types, "demo data has no '$type' lesson");
        }
        $this->assertNotNull($this->demo('demo-seguridad-scorm')->sections->first()->curriculums->firstWhere('type', 'scorm')->media_path, 'SCORM package was not processed');
    }

    public function test_public_catalog_lists_active_courses_and_hides_the_draft(): void
    {
        // The catalog fills in after `wire:init`, so exercise the component like the browser does.
        Livewire::test(SearchCourses::class)->call('loadData')
            ->assertSee('Marketing Digital desde Cero')
            ->assertSee('Inglés Conversacional A1')
            ->assertDontSee('Liderazgo y Comunicación (borrador)');

        foreach (['demo-marketing-digital', 'demo-excel-gratis', 'demo-seguridad-scorm', 'demo-ingles-a1'] as $slug) {
            $this->get('/curso/' . $slug)->assertOk();
        }
    }

    public function test_purchased_free_and_unpaid_students_get_the_right_access(): void
    {
        $marketing = $this->demo('demo-marketing-digital')->slug;
        $excel = $this->demo('demo-excel-gratis')->slug;

        $this->actingAs($this->u('pagado'))->get("/aula/$marketing")->assertOk();
        $this->actingAs($this->u('gratis'))->get("/aula/$excel")->assertOk();
        $this->actingAs($this->u('gratis'))->get("/aula/$marketing")->assertRedirect();
        $this->actingAs($this->u('nuevo'))->get("/aula/$marketing")->assertRedirect();
        $this->actingAs($this->u('nuevo'))->get("/aula/$excel")->assertRedirect();
    }

    public function test_student_dashboard_lists_only_own_courses(): void
    {
        Livewire::actingAs($this->u('pagado'))->test(CourseList::class)->call('loadCoursesData')->assertSee('Marketing Digital desde Cero');
        // Quien no tiene cursos no ve tarjetas de «mis cursos» (puede ver el curso solo como recomendación, que no tiene botón de continuar).
        Livewire::actingAs($this->u('nuevo'))->test(CourseList::class)->call('loadCoursesData')->assertDontSee('data-testid="resume-btn"', false);
    }

    public function test_pending_order_becomes_access_after_an_approved_mercado_pago_payment(): void
    {
        $methods = setting('admin_settings.payment_method') ?: [];
        $methods['mercadopago'] = [
            'status' => 'on', 'currency' => 'MXN', 'exchange_rate' => '', 'enable_test_mode' => '', 'binary_mode' => '',
            'mp_access_token' => 'TEST-token', 'mp_public_key' => 'TEST-key', 'mp_webhook_secret' => self::SECRET,
        ];
        Settings::set('admin_settings', 'payment_method', $methods);

        $student = $this->u('nuevo');
        $english = $this->demo('demo-ingles-a1');
        $order = Order::where('user_id', $student->id)->where('status', 'pending')->firstOrFail();

        $this->actingAs($student)->get("/aula/{$english->slug}")->assertRedirect();

        Http::swap(new HttpFactory());
        Http::fake(['api.mercadopago.com/v1/payments/*' => Http::response([
            'id' => 5550001, 'status' => 'approved', 'external_reference' => (string) $order->id,
            'transaction_amount' => (float) $order->amount, 'currency_id' => 'MXN',
        ])]);

        $ts = (string) time();
        $rid = 'req-' . Str::random(6);
        $signature = hash_hmac('sha256', "id:5550001;request-id:{$rid};ts:{$ts};", self::SECRET);

        $this->withHeaders(['x-signature' => "ts={$ts},v1={$signature}", 'x-request-id' => $rid])
            ->postJson('/mercadopago/webhook?type=payment&data.id=5550001', ['type' => 'payment', 'data' => ['id' => '5550001']])
            ->assertSuccessful();

        $this->assertDatabaseHas('courses_enrollments', ['student_id' => $student->id, 'course_id' => $english->id]);
        $this->actingAs($student)->get("/aula/{$english->slug}")->assertOk();
    }

    public function test_quiz_grade_is_computed_by_the_server_not_the_browser(): void
    {
        $student = $this->u('pagado');
        $course = $this->demo('demo-marketing-digital');
        $quiz = Curriculum::whereHas('section', fn ($q) => $q->where('course_id', $course->id))->where('type', 'assignment')->firstOrFail();

        $component = $this->courseTaking($student, $course);
        $component->call('setActiveCurriculum', ['id' => $quiz->id])
            ->call('submitQuiz', [1, 0, 0], 100); // all wrong, but the browser claims 100%

        $submission = AssignmentSubmission::where('curriculum_id', $quiz->id)->where('user_id', $student->id)->firstOrFail();
        $this->assertSame(0, (int) $submission->score);

        $component->call('submitQuiz', [0, 1, 1], 0); // all right, browser claims 0
        $this->assertSame(100, (int) $submission->refresh()->score);
    }

    public function test_a_student_cannot_open_lessons_of_a_course_he_did_not_buy(): void
    {
        $student = $this->u('pagado');
        $mine = $this->demo('demo-marketing-digital');
        $foreignLesson = Curriculum::whereHas('section.course', fn ($q) => $q->where('slug', 'demo-seguridad-scorm'))->where('type', 'scorm')->firstOrFail();

        $component = $this->courseTaking($student, $mine);
        $before = $component->get('activeCurriculum.id');

        $component->call('nextCurriculum', $foreignLesson->id)
            ->call('setActiveCurriculum', ['id' => $foreignLesson->id, 'media_path' => 'identity_photo/secret.jpg', 'type' => 'video'])
            ->assertSet('activeCurriculum.id', $before);
    }

    public function test_progress_and_review_are_recorded_for_the_paying_student(): void
    {
        $student = $this->u('pagado');
        $course = $this->demo('demo-marketing-digital');

        $this->assertGreaterThan(0, $course->ratings()->where('student_id', $student->id)->count());
        $this->assertGreaterThan(0, \Modules\Courses\Models\Watchtime::where('user_id', $student->id)->where('course_id', $course->id)->count());
        $this->assertTrue(Enrollment::where('student_id', $student->id)->where('course_id', $course->id)->exists());
    }
}
