<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Courses\Livewire\Pages\Admin\Categories as AdminCategories;
use Modules\Courses\Models\Category;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Section;
use Modules\Courses\Services\CourseService;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * End-to-end feature tests for the platform's most important flows, beyond
 * the plain GET crawl in PlatformSmokeTest: registration/login, browsing &
 * search, cart & checkout, tutor course authoring, student lesson viewing,
 * admin CRUD (via Livewire, which is how these admin screens actually
 * submit forms in this app), and a Sanctum API smoke pass.
 *
 * Uses DatabaseTransactions against the already-seeded `testing` database
 * (see PlatformSmokeTest for the rationale — never RefreshDatabase here).
 */
class PlatformEndToEndTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
    }

    public function test_api_registration_and_login_flow(): void
    {
        $email = 'e2e-' . strtolower(Str::random(8)) . '@horizontia.test';

        $registerResponse = $this->postJson('/api/register', [
            'first_name' => 'E2E',
            'last_name' => 'Tester',
            'email' => $email,
            'phone_number' => '+15551234567',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'user_role' => 'student',
            'terms' => '1',
        ]);

        $registerResponse->assertStatus(200);
        $this->assertDatabaseHas('users', ['email' => $email]);

        // Registration does not auto-verify the email, and login correctly
        // refuses unverified accounts ("Not verified") — simulate the user
        // having clicked the verification link before attempting to log in.
        User::where('email', $email)->update(['email_verified_at' => now()]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'Password123!',
        ]);
        $loginResponse->assertStatus(200);
        $token = data_get($loginResponse->json(), 'data.token');
        $this->assertNotEmpty($token, 'Login response did not include a token: ' . $loginResponse->getContent());

        $logoutResponse = $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/logout');
        $this->assertLessThan(500, $logoutResponse->getStatusCode());
    }

    public function test_guest_can_browse_and_search_courses(): void
    {
        $course = $this->course();

        $response = $this->get('/search-courses');
        $response->assertOk();

        $response = $this->get('/search-courses?keyword=' . urlencode('QA'));
        $this->assertLessThan(500, $response->getStatusCode());

        $response = $this->get('/course/' . $course->slug);
        $response->assertOk();
        $response->assertSee($course->title);
    }

    public function test_public_course_api_endpoints_work(): void
    {
        $course = $this->course();

        $this->getJson('/api/courses')->assertOk();
        $this->getJson('/api/categories')->assertOk();
        $this->getJson('/api/course-detail/' . $course->slug)->assertOk();
    }

    public function test_student_can_add_course_to_cart_via_api(): void
    {
        $student = $this->student();
        $course2 = $this->course2();
        CartItem::where('user_id', $student->id)->delete();

        $response = $this->actingAs($student, 'sanctum')
            ->postJson('/api/course-cart', ['slug' => $course2->slug]);

        $this->assertLessThan(500, $response->getStatusCode(), $response->getContent());
        $response->assertOk();
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $student->id,
            'cartable_id' => $course2->id,
        ]);
    }

    public function test_student_checkout_attempt_does_not_crash(): void
    {
        Http::fake(); // guard against any real outbound calls (e.g. Stripe) during checkout.

        $student = $this->student();
        $course2 = $this->course2();

        CartItem::where('user_id', $student->id)->delete();
        // Add to cart through the real API path (as a genuine user flow would),
        // so the cart item's `options` payload matches what the checkout view expects.
        $this->actingAs($student, 'sanctum')
            ->postJson('/api/course-cart', ['slug' => $course2->slug])
            ->assertOk();

        // 1) The web checkout page itself must render for an authenticated student.
        $page = $this->actingAs($student)->get('/checkout');
        $this->assertLessThan(500, $page->getStatusCode());

        // 2) Submitting checkout details via the API must not blow up even
        //    though no payment gateway is configured — it should fail
        //    gracefully (validation/business error) rather than 500.
        $response = $this->actingAs($student, 'sanctum')->postJson('/api/checkout', [
            'firstName' => 'QA',
            'lastName' => 'Student',
            'paymentMethod' => 'stripe',
            'email' => $student->email,
            'country' => 'United States',
            'state' => 'California',
            'zipcode' => '90001',
            'city' => 'Los Angeles',
            'amount' => 19.99,
        ]);

        $this->assertLessThan(500, $response->getStatusCode(), 'Checkout crashed: ' . $response->getContent());
    }

    public function test_api_enroll_free_course_does_not_crash(): void
    {
        $student = $this->student();
        $course = $this->course();

        $response = $this->actingAs($student, 'sanctum')
            ->postJson('/api/enroll-course', ['slug' => $course->slug]);

        // Business outcome varies (already enrolled / paid-system-disabled/etc)
        // — what matters for this smoke pass is that it never 500s.
        $this->assertLessThan(500, $response->getStatusCode(), $response->getContent());
    }

    public function test_tutor_can_author_a_course_with_curriculum_via_course_service(): void
    {
        $tutor = $this->tutor();
        $category = $this->category();
        $service = new CourseService();

        $course = $service->updateOrCreateCourse(null, [
            'instructor_id' => $tutor->id,
            'title' => 'E2E Authored Course ' . Str::random(6),
            'description' => 'Created directly through CourseService, exactly as the tutor course-creation wizard does.',
            'category_id' => $category->id,
            'sub_category_id' => $category->id,
            'type' => 'article',
            'level' => 'beginner',
            'language_id' => 1,
            'status' => 'draft',
        ]);

        $this->assertInstanceOf(Course::class, $course);
        $this->assertNotEmpty($course->slug);

        $section = $service->createSection(['course_id' => $course->id, 'title' => 'Section 1']);
        $this->assertInstanceOf(Section::class, $section);

        $curriculum = Curriculum::create([
            'section_id' => $section->id,
            'title' => 'Lesson 1',
            'type' => 'article',
            'article_content' => '<p>Authored via test</p>',
            'sort_order' => 1,
            'is_preview' => true,
        ]);
        $this->assertInstanceOf(Curriculum::class, $curriculum);

        // The tutor's own course-edit wizard page must render for this brand-new course.
        $response = $this->actingAs($tutor)->get("/course/edit/details/{$course->id}");
        $this->assertLessThan(500, $response->getStatusCode());
    }

    public function test_student_can_view_enrolled_course_lesson(): void
    {
        $student = $this->student();
        $course = $this->course(); // qa-student is enrolled in this course via TestingSeeder.

        $response = $this->actingAs($student)->get("/course-taking/{$course->slug}");
        $response->assertOk();
    }

    public function test_admin_can_create_a_category_via_livewire(): void
    {
        $admin = $this->admin();
        $name = 'E2E Category ' . Str::random(6);

        Livewire::actingAs($admin)
            ->test(AdminCategories::class)
            ->set('name', $name)
            ->call('update')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('courses_categories', ['name' => $name]);
    }

    public function test_admin_web_pages_for_users_courses_blog_settings_load(): void
    {
        $admin = $this->admin();

        foreach ([
            '/admin/users',
            '/admin/courses',
            '/admin/blogs',
            '/admin/blog-categories',
            '/admin/email-settings',
            '/admin/payment-methods',
        ] as $uri) {
            $response = $this->actingAs($admin)->get($uri);
            $this->assertLessThan(500, $response->getStatusCode(), "{$uri} returned {$response->getStatusCode()}");
        }
    }

    public function test_sanctum_api_smoke_pass_public_and_authenticated_endpoints(): void
    {
        $student = $this->student();
        $token = $student->createToken('e2e-smoke')->plainTextToken;

        $publicEndpoints = [
            '/api/categories',
            '/api/countries',
            '/api/languages',
            '/api/levels',
            '/api/prices',
            '/api/settings',
            '/api/find-tutors',
            '/api/duration-counts',
            '/api/ratings',
            '/api/recommended-tutors',
        ];
        foreach ($publicEndpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $this->assertLessThan(500, $response->getStatusCode(), "{$endpoint} returned {$response->getStatusCode()}: " . $response->getContent());
        }

        $authedEndpoints = [
            '/api/enrolled-courses',
            '/api/invoices',
            '/api/favourite-tutors',
            '/api/earning-detail',
        ];
        foreach ($authedEndpoints as $endpoint) {
            $response = $this->withHeader('Authorization', "Bearer {$token}")->getJson($endpoint);
            $this->assertLessThan(500, $response->getStatusCode(), "{$endpoint} returned {$response->getStatusCode()}: " . $response->getContent());
        }
    }
}
