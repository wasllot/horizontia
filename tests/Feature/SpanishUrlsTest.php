<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * Public/user-facing URLs are in Spanish. Old English URLs keep working through 301 redirects
 * (bookmarks, old e-mails, search engines, page-builder content).
 */
class SpanishUrlsTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    /** Technical endpoints kept as-is on purpose: external registrations, vendor packages, JS that hard-codes them. */
    private const TECHNICAL_PREFIXES = [
        'api/', 'livewire/', 'sanctum/', 'broadcasting/', 'telescope', 'storage/', 'up', '_ignition', 'auth/', 'google/callback',
        'admin/pages', 'admin/get-section-settings', 'admin/set-', 'admin/translations', 'admin/option-builder', 'admin/update-',
        'admin/paquetes/subir', 'get-pb-section', 'pages/', 'messenger', 'scorm/progress', 'editor/', 'remove-cart', 'switch-',
        'openai/', 'favourite-tutor', 'payfast/', 'payease/', 'mercadopago/webhook', 'robots.txt', 'sitemap.xml', '{any}',
        'pago/exito', 'social-profile',
    ];

    /** English words that must not appear as a URL segment of a user-facing route. */
    private const ENGLISH_SEGMENTS = [
        'login', 'logout', 'register', 'forgot-password', 'reset-password', 'verify-email', 'confirm-password', 'search-courses', 'course',
        'courses', 'course-list', 'course-taking', 'create-course', 'find-tutors', 'checkout', 'thank-you', 'blogs', 'student', 'profile',
        'bookings', 'invoices', 'disputes', 'favourites', 'certificates', 'dashboard', 'payouts', 'users', 'settings', 'payment-methods',
        'insights', 'leads', 'upgrade', 'packages', 'taxonomies', 'subjects', 'languages', 'categories', 'pay',
    ];

    public function test_no_user_facing_route_keeps_an_english_segment(): void
    {
        $offenders = [];

        foreach (Route::getRoutes() as $route) {
            if (!in_array('GET', $route->methods(), true)) {
                continue;
            }
            $uri = ltrim($route->uri(), '/');
            if ($uri === '' || $this->isTechnical($uri) || $this->isLegacy($uri)) {
                continue;
            }
            foreach (explode('/', $uri) as $segment) {
                if (in_array($segment, self::ENGLISH_SEGMENTS, true)) {
                    $offenders[] = $uri;
                    break;
                }
            }
        }

        $this->assertSame([], $offenders, "English URL segments found:\n" . implode("\n", $offenders));
    }

    private function isTechnical(string $uri): bool
    {
        foreach (self::TECHNICAL_PREFIXES as $prefix) {
            if ($uri === rtrim($prefix, '/') || str_starts_with($uri, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function isLegacy(string $uri): bool
    {
        return array_key_exists($uri, require base_path('routes/legacy.php'));
    }

    public function test_every_old_url_redirects_permanently_to_its_spanish_equivalent(): void
    {
        $samples = ['{id}' => '7', '{slug}' => 'demo-curso', '{token}' => 'tok', '{hash}' => 'abc', '{date}' => '2026-10-10', '{tab}' => 'details',
            '{path}' => 'a/b.mp4', '{curriculumId}' => '12', '{gateway}' => 'stripe'];
        $checked = 0;

        foreach (require base_path('routes/legacy.php') as $old => $name) {
            $path = strtr($old, $samples);
            $params = [];
            foreach ($samples as $placeholder => $value) {
                if (str_contains($old, $placeholder)) {
                    $params[trim($placeholder, '{}')] = $value;
                }
            }
            $response = $this->get('/' . $path . '?utm_source=viejo');

            $response->assertStatus(301);
            $this->assertSame(route($name, $params) . '?utm_source=viejo', $response->headers->get('Location'), "legacy /$old");
            $checked++;
        }

        $this->assertGreaterThan(90, $checked);
    }

    public function test_the_main_journeys_live_at_spanish_urls(): void
    {
        $slug = $this->course()->slug;
        $student = $this->student();

        $this->get('/cursos')->assertOk();
        $this->get("/curso/{$slug}")->assertOk();
        $this->get('/iniciar-sesion')->assertOk();
        $this->get('/blog')->assertOk();
        $this->get('/finalizar-compra')->assertRedirect();   // guest -> login
        $this->actingAs($student)->get("/aula/{$slug}")->assertOk();
        $this->actingAs($student)->get('/mis-cursos')->assertOk();
        $this->actingAs($student)->get('/estudiante/facturas')->assertOk();
        $this->actingAs($student)->get('/estudiante/perfil/datos-personales')->assertOk();
        $this->actingAs($this->tutor())->get('/gestionar-cursos')->assertOk();
        $this->actingAs($this->tutor())->get('/crear-curso')->assertOk();
        $this->actingAs($this->admin())->get('/admin/usuarios')->assertOk();
        $this->actingAs($this->admin())->get('/admin/cursos')->assertOk();
    }

    public function test_old_url_of_a_course_keeps_working_for_a_visitor_end_to_end(): void
    {
        $slug = $this->course()->slug;

        $this->followingRedirects()->get("/course/{$slug}")->assertOk()->assertSee($this->course()->title);
        $this->followingRedirects()->get('/search-courses')->assertOk();
    }

    public function test_generated_links_use_the_spanish_urls(): void
    {
        $this->assertStringEndsWith('/iniciar-sesion', route('login'));
        $this->assertStringEndsWith('/cursos', route('courses.search-courses'));
        $this->assertStringEndsWith('/curso/x', route('courses.course-detail', 'x'));
        $this->assertStringEndsWith('/aula/x', route('courses.course-taking', 'x'));
        $this->assertStringEndsWith('/finalizar-compra', route('checkout'));
        $this->assertStringContainsString('/video-seguro/', route('courses.secure.video', 'curriculum_videos/a.mp4'));
        $this->assertStringContainsString('/contenido-scorm/3/', route('courses.scorm.asset', [3, 'index.html']));

        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('/search-courses', $home);
        $this->assertStringNotContainsString('href="' . url('/login'), $home);
    }
}
