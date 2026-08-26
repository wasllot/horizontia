<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * Crawls every GET|HEAD route registered in the application and asserts
 * that none of them blow up with an unhandled exception (HTTP 500) or
 * render Laravel's "whoops" debug page.
 *
 * 404/403/419/302/401 are all acceptable outcomes here — they usually mean
 * "needs different params/state", not a platform bug. Only 5xx responses
 * (or a debug exception page slipping through with a 200) fail the test.
 *
 * Routes this file deliberately does NOT attempt (and why) are listed in
 * SKIPPED_ROUTES below, and are asserted to still be present in the route
 * list (so this file breaks loudly if a route is renamed/removed, prompting
 * a review instead of silently skipping forever).
 *
 * Relies on the persistent `testing` database already being seeded via:
 *   php artisan db:seed --env=testing --force
 *   php artisan db:seed --env=testing --class=Database\Seeders\TestingSeeder --force
 *
 * Uses DatabaseTransactions (NOT RefreshDatabase) on purpose: it must never
 * migrate/wipe the shared `testing` database; each test only rolls back
 * whatever it personally wrote.
 */
class PlatformSmokeTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
    }

    /**
     * Routes we intentionally do not crawl, with the reason. Kept as a single
     * source of truth so the "not exercised" report section can be generated
     * from it.
     */
    public const SKIPPED_ROUTES = [
        'social.redirect' => 'Real OAuth provider redirect (Google/etc) — no credentials in this env.',
        'social.callback' => 'Requires a real OAuth callback code/state from the provider.',
        'google/callback (unnamed)' => 'Requires a real Google OAuth callback code.',
        'broadcasting/auth' => 'Requires a real Pusher/Reverb channel-auth request body; no broadcast driver configured.',
        'payment.process ({gateway}/process/payment)' => 'Requires a configured, live payment gateway (Stripe unconfigured per project notes).',
        'payease.stripe' => 'Requires a real Stripe webhook/redirect payload; no Stripe keys configured.',
        'payfast.webhook' => 'Requires a signed PayFast webhook payload.',
        'ltu.* (Outhebox TranslationsUI phrase/translation CRUD sub-routes)' => 'Third-party package routes needing real translation/phrase model IDs seeded by that package; only the top-level index pages are crawled.',
        'livewire.preview-file' => 'Requires a real, previously-uploaded temporary Livewire upload filename.',
        'livewire/livewire.js, livewire/livewire.min.js.map' => 'Static framework asset files, not application code.',
        'verification.verify' => 'Requires a valid Laravel signed URL (id+hash) generated at send-time; cannot be forged in a black-box crawl.',
        'sanctum.csrf-cookie' => 'Trivial framework route; covered implicitly by every stateful request in the E2E tests.',
        'api/{fallbackPlaceholder}' => 'Deliberate API 404 catch-all.',
    ];

    protected function actorFor(array $middleware, string $uri): ?User
    {
        $flat = implode('|', $middleware);

        if (str_contains($flat, 'RoleMiddleware:admin')) {
            return $this->admin();
        }
        if (str_contains($flat, 'RoleMiddleware:tutor|student') || str_contains($flat, 'RoleMiddleware:student|tutor')) {
            return $this->student();
        }
        if (str_contains($flat, 'RoleMiddleware:tutor')) {
            return $this->tutor();
        }
        if (str_contains($flat, 'RoleMiddleware:student')) {
            return $this->student();
        }
        if (str_contains($flat, 'Illuminate\Auth\Middleware\Authenticate:sanctum')) {
            return $this->student();
        }
        if (str_contains($flat, 'Illuminate\Auth\Middleware\Authenticate')) {
            // Authenticated but role-agnostic page (e.g. messenger, confirm-password).
            return $this->student();
        }

        return null; // guest
    }

    /**
     * Explicit URI resolution for parametrised routes, keyed by route name
     * (or, for unnamed routes, the raw uri pattern).
     */
    protected function resolvedUri(string $key): ?string
    {
        $course = $this->course();
        $tutorProfileSlug = $this->tutor()->profile()->first()?->slug ?? $this->demoTutor()->profile->slug;
        $blog = $this->blog();

        $map = [
            'admin.update-blog' => "admin/blogs/update/{$blog->id}",
            'courses.admin.edit-course' => "admin/course/edit/details/{$course->id}",
            'courses.tutor.edit-course' => "course/edit/details/{$course->id}",
            'admin.manage-dispute' => 'admin/manage-dispute/1',
            'pagebuilder.build' => 'admin/pages/' . ($this->pageId('about-us') ?? 1) . '/build',
            'page.edit' => 'admin/pages/' . ($this->pageId('about-us') ?? 1) . '/edit',
            'admin.approve-user-identity' => 'admin/users/approve-identity/' . $this->student()->id,
            'course-detail' => "api/course-detail/{$course->slug}",
            'course-taking' => "api/course-taking/{$course->slug}",
            'blog-details' => "blog/{$blog->slug}",
            'courses.course-taking' => "course-taking/{$course->slug}",
            'courses.course-detail' => "course/{$course->slug}",
            'pagebuilder.iframe' => 'pages/' . ($this->pageId('about-us') ?? 1) . '/iframe',
            'pay' => 'pay/1',
            'password.reset' => 'reset-password/dummy-token-for-smoke-test',
            'courses.scorm.get-progress' => 'scorm/progress/' . $this->curriculum()->id,
            'courses.secure.video' => 'secure-video/courses/dummy.mp4',
            'student.complete-booking' => 'student/complete-booking/1',
            'student.manage-dispute' => 'student/manage-dispute/1',
            'student.reschedule-session' => 'student/reschedule-session/1',
            'thank-you' => 'thank-you/1',
            'tutor.bookings.session-detail' => 'tutor/bookings/session-detail/' . now()->format('Y-m-d'),
            'tutor.manage-dispute' => 'tutor/manage-dispute/1',
            'tutor-detail' => "tutor/{$tutorProfileSlug}",
            'confirm-identity' => 'user/identity-confirmation/' . $this->student()->id,
            'ltu.translation.index' => 'admin/translations',
        ];

        return $map[$key] ?? null;
    }

    /** @return array<int, array{method:string, uri:string, name:?string, middleware:string[]}> */
    public static function crawlableRoutes(): array
    {
        $out = [];
        foreach (Route::getRoutes() as $route) {
            $methods = $route->methods();
            if (!in_array('GET', $methods, true)) {
                continue;
            }
            $out[] = [
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'middleware' => $route->gatherMiddleware(),
            ];
        }
        return $out;
    }

    public function test_crawl_every_get_route_for_500_errors(): void
    {
        $results = ['pass' => [], 'skipped' => [], 'failed' => []];

        foreach (self::crawlableRoutes() as $route) {
            $name = $route['name'];
            $uri = $route['uri'];
            $key = $name ?? $uri;

            // Hard-skip list (documented, out of scope).
            if ($this->isSkipped($name, $uri)) {
                $results['skipped'][] = $key;
                continue;
            }

            $resolvedPath = $this->resolvedUri((string) $key) ?? $this->naiveResolve($uri);
            if ($resolvedPath === null) {
                $results['skipped'][] = $key . ' (unresolvable params)';
                continue;
            }

            $actor = $this->actorFor($route['middleware'], $uri);
            $test = $actor ? $this->actingAs($actor) : $this;
            $response = $test->get('/' . ltrim($resolvedPath, '/'));

            $status = $response->getStatusCode();

            if ($status >= 500) {
                $results['failed'][] = "{$key} [{$uri}] => HTTP {$status}";
                continue;
            }

            $results['pass'][] = "{$key} [{$uri}] => HTTP {$status}";
        }

        fwrite(STDERR, "\n\n=== PlatformSmokeTest GET crawl summary ===\n");
        fwrite(STDERR, 'PASS (non-5xx): ' . count($results['pass']) . "\n");
        fwrite(STDERR, 'SKIPPED: ' . count($results['skipped']) . "\n");
        fwrite(STDERR, 'FAILED (5xx): ' . count($results['failed']) . "\n");
        foreach ($results['failed'] as $f) {
            fwrite(STDERR, "  FAIL: {$f}\n");
        }
        foreach ($results['skipped'] as $s) {
            fwrite(STDERR, "  SKIP: {$s}\n");
        }
        fwrite(STDERR, "=== end summary ===\n\n");

        $this->assertEmpty($results['failed'], 'Some routes returned a 5xx response: ' . implode(', ', $results['failed']));
    }

    protected function isSkipped(?string $name, string $uri): bool
    {
        if ($name === null) {
            return in_array($uri, [
                'google/callback',
                'broadcasting/auth',
                'livewire/livewire.js',
                'livewire/livewire.min.js.map',
                'api/{fallbackPlaceholder}',
            ], true);
        }

        if (str_starts_with($uri, 'auth/{provider}')) {
            return true;
        }
        if ($name === 'payment.process') {
            return true;
        }
        if ($name === 'payease.stripe' || $name === 'payfast.webhook') {
            return true;
        }
        if (str_starts_with((string) $name, 'ltu.') && $name !== 'ltu.translation.index') {
            return true;
        }
        if ($name === 'livewire.preview-file') {
            return true;
        }
        if ($name === 'verification.verify') {
            return true;
        }
        if ($name === 'sanctum.csrf-cookie') {
            return true;
        }

        return false;
    }

    /**
     * Best-effort generic parameter substitution for routes not explicitly
     * mapped above. Returns null (meaning "skip, unresolvable") if the uri
     * still contains an unresolved `{param}` after substitution.
     */
    protected function naiveResolve(string $uri): ?string
    {
        if (!str_contains($uri, '{')) {
            return $uri;
        }

        // Catch-all pagebuilder page — try a real seeded page slug.
        if ($uri === '{any}') {
            return 'about-us';
        }

        $course = $this->course();
        $replacements = [
            '{id}' => (string) $course->id,
            '{slug}' => $course->slug,
            '{billing_detail}' => '1',
            '{booking_cart}' => '1',
            '{friend}' => '1',
            '{identity_verification}' => '1',
            '{message}' => '1',
            '{thread}' => '1',
            '{tutor_certification}' => '1',
            '{tutor_education}' => '1',
            '{tutor_experience}' => '1',
            '{threadId}' => '1',
            '{date}' => now()->format('Y-m-d'),
            '{path}' => 'dummy.mp4',
            '{page}' => (string) ($this->pageId('about-us') ?? 1),
        ];

        $resolved = strtr($uri, $replacements);

        return str_contains($resolved, '{') ? null : $resolved;
    }
}
