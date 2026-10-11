<?php

namespace Tests\Feature;

use App\Livewire\Frontend\Checkout;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Larabuild\Optionbuilder\Facades\Settings;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Services\CourseAccessService;
use Symfony\Component\Process\Process;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * Regression suite for everything that protects PAID content and money:
 * lesson-file access, enrollment gating, checkout tampering, Stripe secret
 * exposure and the public maintenance scripts. Each test documents a real hole
 * found in the audit; if one fails, a vulnerability is back.
 */
class PaidContentSecurityTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
    }

    private function videoCurriculum(string $path = 'curriculum_videos/qa-lesson.mp4', bool $preview = false): Curriculum
    {
        return Curriculum::create([
            'section_id' => $this->section()->id,
            'title' => 'QA Video ' . uniqid(),
            'type' => 'video',
            'media_path' => $path,
            'content_length' => 60,
            'sort_order' => 99,
            'is_preview' => $preview,
        ]);
    }

    private function outsider(): User
    {
        return $this->demoStudent(); // not enrolled in qa-test-course
    }

    // ------------------------------------------------------------------
    // Public maintenance scripts
    // ------------------------------------------------------------------

    public function test_public_maintenance_scripts_fail_closed_without_a_valid_token(): void
    {
        $scripts = ['run_deploy.php', 'readlog.php', 'fix_course_view.php', 'check_thumbnails.php', 'clear_views.php'];

        foreach ($scripts as $script) {
            foreach ([[], ['key' => 'horizontia2026'], ['key' => str_repeat('a', 40)]] as $query) {
                $code = '$_GET = ' . var_export($query, true) . ';'
                    . 'ob_start(); require ' . var_export(public_path($script), true) . '; echo "REACHED";';
                $process = new Process([PHP_BINARY, '-r', $code]);
                $process->run();

                $this->assertStringNotContainsString('REACHED', $process->getOutput(), "$script ran without a valid DEPLOY_TOKEN");
            }
        }
    }

    // ------------------------------------------------------------------
    // Signed lesson media (local disk)
    // ------------------------------------------------------------------

    public function test_lesson_video_route_rejects_unsigned_expired_and_foreign_paths(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('curriculum_videos/qa-lesson.mp4', str_repeat('x', 2048));
        Storage::disk('public')->put('identity_photo/id-card.jpg', 'secret');

        $this->get(route('courses.secure.video', ['path' => 'curriculum_videos/qa-lesson.mp4']))->assertForbidden();

        $expired = URL::temporarySignedRoute('courses.secure.video', now()->subMinute(), ['path' => 'curriculum_videos/qa-lesson.mp4']);
        $this->get($expired)->assertForbidden();

        // A valid signature must not become a way to read arbitrary private files.
        $foreign = URL::temporarySignedRoute('courses.secure.video', now()->addMinutes(5), ['path' => 'identity_photo/id-card.jpg']);
        $this->get($foreign)->assertNotFound();

        $tampered = URL::temporarySignedRoute('courses.secure.video', now()->addMinutes(5), ['path' => 'curriculum_videos/qa-lesson.mp4']);
        $this->get(str_replace('qa-lesson', 'other', $tampered))->assertForbidden();
    }

    public function test_lesson_video_route_streams_with_range_support_when_signed(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('curriculum_videos/qa-lesson.mp4', str_repeat('x', 2048));

        $url = URL::temporarySignedRoute('courses.secure.video', now()->addMinutes(5), ['path' => 'curriculum_videos/qa-lesson.mp4']);

        $this->get($url)->assertOk();
        $this->get($url, ['Range' => 'bytes=0-9'])->assertStatus(206);
    }

    public function test_lesson_media_url_is_always_short_lived(): void
    {
        $url = lessonMediaUrl('curriculum_videos/qa-lesson.mp4');

        $this->assertStringContainsString('signature=', $url);
        $this->assertStringContainsString('expires=', $url);
        $this->assertStringNotContainsString('/storage/curriculum_videos', $url);
    }

    // ------------------------------------------------------------------
    // Enrollment gating
    // ------------------------------------------------------------------

    public function test_course_access_service_matrix(): void
    {
        $service = new CourseAccessService();
        $course = $this->course();
        $locked = $this->videoCurriculum();
        $preview = $this->videoCurriculum('curriculum_videos/preview.mp4', true);

        $this->assertTrue($service->canAccessCourse($this->student(), $course), 'enrolled student');
        $this->assertTrue($service->canAccessCourse($this->tutor(), $course), 'owner instructor');
        $this->assertTrue($service->canAccessCourse($this->admin(), $course), 'admin');
        $this->assertFalse($service->canAccessCourse($this->outsider(), $course), 'student without enrollment');
        $this->assertFalse($service->canAccessCourse(null, $course), 'guest');

        $this->assertTrue($service->canViewCurriculum($this->student(), $locked));
        $this->assertFalse($service->canViewCurriculum($this->outsider(), $locked));
        $this->assertFalse($service->canViewCurriculum(null, $locked));
        $this->assertTrue($service->canViewCurriculum(null, $preview), 'free preview lessons are public');
    }

    public function test_revoked_enrollment_loses_access(): void
    {
        $service = new CourseAccessService();
        $student = $this->student();

        Enrollment::where('student_id', $student->id)->where('course_id', $this->course()->id)->delete();

        $this->assertFalse($service->canAccessCourse($student, $this->course()));
    }

    public function test_course_taking_page_is_closed_to_students_without_enrollment(): void
    {
        $slug = $this->course()->slug;

        $this->actingAs($this->student())->get("/aula/{$slug}")->assertOk();
        $this->actingAs($this->outsider())->get("/aula/{$slug}")->assertRedirect();
        $this->get("/aula/{$slug}")->assertRedirect(); // guest -> login
    }

    public function test_api_course_taking_blocks_outsiders_and_returns_signed_media_to_students(): void
    {
        $this->videoCurriculum();
        $slug = $this->course()->slug;

        $this->actingAs($this->outsider(), 'sanctum')->getJson("/api/course-taking/{$slug}")->assertForbidden();

        $response = $this->actingAs($this->student(), 'sanctum')->getJson("/api/course-taking/{$slug}");
        $response->assertOk();
        $body = $response->getContent();
        $this->assertStringContainsString('video-seguro', $body);
        $this->assertStringContainsString('signature=', $body);
        $this->assertStringNotContainsString('\/storage\/curriculum_videos', $body, 'permanent public lesson URL leaked');
    }

    public function test_progress_api_requires_an_active_enrollment(): void
    {
        $lesson = $this->curriculum();
        $payload = ['course_id' => $this->course()->id, 'curriculum_id' => $lesson->id];

        $this->actingAs($this->outsider(), 'sanctum')->postJson('/api/update-progress', $payload)->assertForbidden();
        $this->assertDatabaseMissing('courses_watchtimes', ['user_id' => $this->outsider()->id, 'curriculum_id' => $lesson->id]);

        $this->assertLessThan(500, $this->actingAs($this->student(), 'sanctum')->postJson('/api/update-progress', $payload)->getStatusCode());
    }

    public function test_public_course_api_never_exposes_media_of_locked_lessons(): void
    {
        $this->videoCurriculum('curriculum_videos/locked-secret.mp4');

        $body = $this->getJson('/api/course-detail/' . $this->course()->slug)->assertOk()->getContent();

        $this->assertStringNotContainsString('locked-secret', $body);
    }

    // ------------------------------------------------------------------
    // SCORM proxy (remote disks) access control
    // ------------------------------------------------------------------

    public function test_scorm_asset_proxy_requires_enrollment(): void
    {
        Storage::fake('public');
        $scorm = $this->scormCurriculum();
        Storage::disk('public')->put("scorm/{$scorm->id}/index.html", '<html>scorm</html>');

        $url = route('courses.scorm.asset', ['curriculumId' => $scorm->id, 'path' => 'index.html']);

        $this->get($url)->assertRedirect();                                  // guest
        $this->actingAs($this->outsider())->get($url)->assertForbidden();   // not enrolled
        $ok = $this->actingAs($this->student())->get($url);
        $ok->assertOk();
        $this->assertStringStartsWith('text/html', $ok->headers->get('Content-Type'));

        $this->actingAs($this->student())
            ->get(route('courses.scorm.asset', ['curriculumId' => $scorm->id, 'path' => '../../.env']))
            ->assertNotFound();
    }

    // ------------------------------------------------------------------
    // Checkout / payment tampering
    // ------------------------------------------------------------------

    private function enableStripe(string $secret = 'sk_test_SUPERSECRET123'): void
    {
        $methods = setting('admin_settings.payment_method') ?: [];
        $methods['stripe'] = ['status' => 'on', 'currency' => 'USD', 'exchange_rate' => '', 'stripe_key' => 'pk_test_public', 'stripe_secret' => $secret];
        Settings::set('admin_settings', 'payment_method', $methods);
    }

    private function cartWithCourse2(User $student): void
    {
        CartItem::where('user_id', $student->id)->delete();
        $this->actingAs($student, 'sanctum')->postJson('/api/course-cart', ['slug' => $this->course2()->slug])->assertOk();
    }

    public function test_api_checkout_charges_the_cart_total_not_the_client_amount(): void
    {
        $this->enableStripe();
        $student = $this->student();
        $this->cartWithCourse2($student);

        $this->actingAs($student, 'sanctum')->postJson('/api/checkout', [
            'firstName' => 'QA', 'lastName' => 'Student', 'paymentMethod' => 'stripe',
            'email' => $student->email, 'country' => 'United States', 'state' => 'California',
            'zipcode' => '90001', 'city' => 'Los Angeles',
            'amount' => 0.01, // tampered
        ])->assertOk();

        $order = Order::where('user_id', $student->id)->latest('id')->firstOrFail();
        $this->assertEquals(19.99, (float) $order->amount);
        $this->assertSame('pending', $order->status);
    }

    public function test_checkout_money_properties_cannot_be_edited_from_the_browser(): void
    {
        $this->enableStripe();
        $student = $this->student();
        $this->cartWithCourse2($student);

        foreach (['payAmount', 'totalAmount', 'walletBalance'] as $property) {
            try {
                Livewire::actingAs($student)->test(Checkout::class)->set($property, 0);
                $this->fail("Checkout::\${$property} must be locked against client updates");
            } catch (CannotUpdateLockedPropertyException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_livewire_checkout_ignores_tampered_form_total(): void
    {
        $this->enableStripe();
        $student = $this->student();
        $this->cartWithCourse2($student);

        Livewire::actingAs($student)->test(Checkout::class)
            ->set('form.firstName', 'QA')
            ->set('form.lastName', 'Student')
            ->set('form.email', $student->email)
            ->set('form.country', 'United States')
            ->set('form.state', 'California')
            ->set('form.city', 'Los Angeles')
            ->set('form.zipcode', '90001')
            ->set('form.totalAmount', 0.01) // tampered
            ->set('form.paymentMethod', 'stripe')
            ->call('updateInfo')
            ->assertHasNoErrors();

        $order = Order::where('user_id', $student->id)->latest('id')->first();
        $this->assertNotNull($order, 'order should have been created');
        $this->assertEquals(19.99, (float) $order->amount);
    }

    public function test_stripe_secret_key_never_reaches_the_browser(): void
    {
        $this->enableStripe('sk_test_SUPERSECRET123');
        $student = $this->student();

        $page = $this->actingAs($student)->withSession(['payment_data' => [
            'amount' => 19.99, 'title' => 'x', 'description' => 'x', 'ipn_url' => url('/'),
            'order_id' => 1, 'track' => 't', 'cancel_url' => url('/finalizar-compra'), 'success_url' => url('/'),
            'email' => $student->email, 'name' => 'QA', 'payment_type' => 'stripe',
        ]])->get('/stripe/procesar-pago');

        $page->assertOk();
        $this->assertStringNotContainsString('SUPERSECRET123', $page->getContent());
        $this->assertStringNotContainsString(base64_encode('sk_test_SUPERSECRET123'), $page->getContent());
        $this->assertStringNotContainsString('stripe_secret', $page->getContent());
    }

    public function test_stripe_prepare_charge_ignores_client_supplied_amounts_and_orders(): void
    {
        $this->enableStripe();
        $student = $this->student();

        // No server-side payment_data: nothing to charge, whatever the client sends.
        $this->actingAs($student)->postJson('/payease/stripe', [
            'order_id' => 1, 'amount' => 0.01, 'charge_amount' => 1, 'stripe_secret' => 'sk_live_attacker', 'currency' => 'usd',
        ])->assertStatus(422);

        // Session payment_data for another order than the one requested.
        $this->withSession(['payment_data' => ['order_id' => 5, 'amount' => 10]])
            ->actingAs($student)->postJson('/payease/stripe', ['order_id' => 6])
            ->assertStatus(422);
    }

    public function test_payment_process_route_requires_login(): void
    {
        $this->get('/stripe/procesar-pago')->assertRedirect();
        $this->get('/mercadopago/procesar-pago')->assertRedirect();
    }
}
