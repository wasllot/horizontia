<?php

namespace Tests\Feature;

use App\Console\Commands\BuildPanelTheme;
use App\Support\PanelLive;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * Regression suite for the panel design system (public/css/panel.css), its shell markup for every role,
 * the generated theme copies and the async/real-time plumbing. Browser behaviour is covered by tests/e2e-panel.
 */
class PanelDesignSystemTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    private string $css;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.private_mode' => true]);
        $this->css = file_get_contents(public_path('css/panel.css'));
    }

    // ------------------------------------------------------------------ stylesheet quality

    public function test_stylesheet_is_balanced_and_self_contained(): void
    {
        $this->assertSame(substr_count($this->css, '{'), substr_count($this->css, '}'), 'unbalanced braces');
        $this->assertStringNotContainsString('@import', $this->css, 'imports add a render-blocking waterfall');
        $this->assertStringNotContainsString('http://', $this->css);
        $this->assertStringNotContainsString('https://', $this->css, 'the panel must not call third parties');
        $this->assertDoesNotMatchRegularExpression('/\bexpression\s*\(|(?<![-\w])behavior\s*:/i', $this->css);
    }

    public function test_every_custom_property_used_is_defined(): void
    {
        preg_match_all('/--([a-z0-9-]+)\s*:/i', $this->css, $defined);
        preg_match_all('/var\(\s*--([a-z0-9-]+)/i', $this->css, $used);
        $defined = array_unique($defined[1]);
        // `--_bg` style names are component-local and set right where they are consumed.
        $missing = array_filter(array_unique($used[1]), fn ($v) => !in_array($v, $defined, true));

        $this->assertSame([], array_values($missing), 'var() without definition: ' . implode(', ', $missing));
    }

    public function test_tokens_are_defined_once_and_colours_live_in_tokens(): void
    {
        $root = substr($this->css, strpos($this->css, ':root {'), 6000);
        preg_match_all('/(--hz-[a-z0-9-]+)\s*:/', $root, $m);
        $this->assertSame(count($m[1]), count(array_unique($m[1])), 'duplicated token in :root');

        // Outside :root, raw hex colours are limited to the on-dark whites; everything else must use a token.
        $outside = preg_replace('/:root\s*\{.*?\n\}/s', '', $this->css, 1);
        $outside = preg_replace('/\/\*.*?\*\//s', '', $outside);
        preg_match_all('/#(?:[0-9a-f]{3}|[0-9a-f]{6})\b/i', $outside, $hex);
        $loose = array_filter($hex[0], fn ($c) => !in_array(strtolower($c), ['#fff', '#ffffff'], true));
        $this->assertLessThanOrEqual(8, count($loose), 'too many raw colours outside tokens: ' . implode(' ', array_unique($loose)));
    }

    public function test_important_is_rare_and_the_stylesheet_stays_small(): void
    {
        $this->assertLessThanOrEqual(12, substr_count($this->css, '!important'), '!important is only for hiding (incl. loaders), select2 widths and reduced motion');
        $this->assertLessThan(160 * 1024, strlen($this->css), 'panel.css should stay under 160 KB');
    }

    public function test_reduced_motion_and_focus_visibility_are_supported(): void
    {
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $this->css);
        $this->assertStringContainsString(':focus-visible', $this->css);
        $this->assertMatchesRegularExpression('/font-display:\s*swap/', $this->css);
    }

    public function test_token_pairs_meet_wcag_aa_contrast(): void
    {
        preg_match_all('/--hz-([a-z0-9-]+):\s*(#[0-9a-f]{6})/i', $this->css, $m);
        $t = array_combine($m[1], $m[2]);

        $pairs = [
            ['text', 'surface'], ['text', 'bg'], ['text-2', 'surface'], ['muted', 'surface'], ['muted', 'bg'],
            ['navy-800', 'surface'], ['navy-900', 'gold-400'], ['side-text', 'navy-800'], ['side-text-hi', 'navy-800'], ['gold-400', 'navy-800'],
            ['success', 'success-bg'], ['danger', 'danger-bg'], ['warning', 'warning-bg'], ['info', 'info-bg'], ['blue-600', 'surface'],
        ];
        foreach ($pairs as [$fg, $bg]) {
            $ratio = $this->contrast($t[$fg] ?? $t['white'] ?? '#ffffff', $t[$bg]);
            $this->assertGreaterThanOrEqual(4.5, $ratio, "--hz-$fg on --hz-$bg is " . round($ratio, 2) . ':1');
        }
        // On a navy sidebar the placeholder-ish heading colour only needs the large-text threshold.
        $this->assertGreaterThanOrEqual(3.0, $this->contrast('#b9c3d9', $t['navy-800']));
    }

    public function test_the_self_hosted_font_exists_and_is_a_variable_woff2(): void
    {
        $font = public_path('fonts/inter/inter-latin-wght-normal.woff2');
        $this->assertFileExists($font);
        $this->assertSame('wOF2', substr(file_get_contents($font), 0, 4));
        $this->assertFileExists(public_path('fonts/inter/LICENSE.txt'));
        $this->assertStringContainsString('inter-latin-wght-normal.woff2', $this->css);
    }

    // ------------------------------------------------------------------ generated theme copies

    public function test_generated_theme_files_are_in_sync_and_have_no_visual_important(): void
    {
        foreach (BuildPanelTheme::FILES as $source => $target) {
            $expected = BuildPanelTheme::transform(file_get_contents(public_path($source)), $source);
            $this->assertSame($expected, file_get_contents(public_path($target)), "$target is stale: run php artisan panel:build-theme");
        }
        $admin = file_get_contents(public_path('css/vendor/theme-admin.css'));
        $this->assertDoesNotMatchRegularExpression('/(?:^|[;{\s])(?:color|background(?:-color)?|border(?:-color)?|padding|margin|font-[a-z]+|border-radius|box-shadow)\s*:[^;}]*!important/i', $admin);
        $this->assertStringNotContainsString('fonts.googleapis.com', $admin);
        $this->assertSame(0, preg_match('/url\(["\']?\.\.\/\.\.\/(?!admin|images|fonts|css)/', $admin) ?: 0);
    }

    public function test_transform_keeps_structural_important_and_rebases_urls(): void
    {
        $css = ".a{color:red !important;display:none !important;background:url(../images/x.png)}";
        $out = BuildPanelTheme::transform($css, 'admin/css/main.css');

        $this->assertStringContainsString('color: red', $out);
        $this->assertStringNotContainsString('color: red !important', $out);
        $this->assertStringContainsString('display:none !important', $out);
        $this->assertStringContainsString('url(../../admin/images/x.png)', $out);
    }

    // ------------------------------------------------------------------ shell markup for every role

    /** @return array<string, array{0:string,1:string}> */
    public static function areas(): array
    {
        return [
            'admin' => ['admin', '/admin/estadisticas'],
            'tutor' => ['tutor', '/gestionar-cursos'],
            'student' => ['student', '/mis-cursos'],
        ];
    }

    /** @dataProvider areas */
    public function test_each_role_gets_the_same_accessible_shell(string $role, string $uri): void
    {
        $html = $this->actingAs($this->{$role}())->get($uri)->assertOk()->getContent();

        $missing = [];
        foreach ([
            'class="hz hz--', '<a class="hz-skip" href="#hz-main">', '~<aside[^>]*class="hz-sidebar"[^>]*id="hz-sidebar"[^>]*aria-label=~', '<nav class="hz-nav" aria-label=',
            '~<header[^>]*class="hz-topbar"~', '<main class="hz-content" id="hz-main"', 'data-hz-collapse', 'data-hz-menu', 'data-hz-scrim', 'css/panel.css', 'js/panel.js', 'js/panel-compat.js',
        ] as $needle) {
            $found = str_starts_with($needle, '~') ? (bool) preg_match($needle, $html) : str_contains($html, $needle);
            if (!$found) {
                $missing[] = $needle;
            }
        }
        $this->assertSame([], $missing, "$role shell lacks: " . implode(' | ', $missing));
        $this->assertStringNotContainsString('tb-sidebarwrapper', $html, 'legacy admin sidebar markup is back');
        $this->assertStringNotContainsString('am-sidebar"', $html, 'legacy user sidebar markup is back');
        $this->assertSame(1, substr_count($html, 'id="hz-main"'));
        $this->assertStringNotContainsString('fonts.googleapis.com', $html, 'the panel must not hot-link Google Fonts');
        // exactly one current page marker group in the nav
        $this->assertGreaterThanOrEqual(1, substr_count($html, 'aria-current="page"'));
        // the icon-only controls of the topbar are labelled
        foreach (['aria-label="Abrir menú"'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        // no inline <style> blocks inside the shell (it used to ship its own CSS patches)
        $from = strpos($html, 'class="hz-sidebar"');
        $shell = substr($html, $from, strpos($html, '</header>') - $from);
        $this->assertStringNotContainsString('<style', $shell);
        $this->assertStringNotContainsString('style="background:linear-gradient', $shell, 'ad-hoc gradients in the shell');
    }

    public function test_theme_libraries_and_layout_scripts_run_once_across_async_navigation(): void
    {
        foreach (['admin' => '/admin/estadisticas', 'student' => '/mis-cursos'] as $role => $uri) {
            $html = $this->actingAs($this->{$role}())->get($uri)->getContent();
            foreach (['js/jquery.min.js', 'js/panel-compat.js', 'js/panel.js', 'js/main.js'] as $lib) {
                $this->assertMatchesRegularExpression('~<script[^>]*data-navigate-once[^>]*' . preg_quote($lib, '~') . '|<script[^>]*' . preg_quote($lib, '~') . '[^>]*data-navigate-once~', $html, "$lib must be data-navigate-once ($role)");
            }
            // The compat shim must load right after jQuery and before the shell script.
            $this->assertLessThan(strpos($html, 'js/panel.js'), strpos($html, 'js/panel-compat.js'));
            $this->assertLessThan(strpos($html, 'js/panel-compat.js'), strpos($html, 'js/jquery.min.js'));
        }
    }

    public function test_sidebar_state_is_applied_before_first_paint(): void
    {
        $html = $this->actingAs($this->admin())->get('/admin/estadisticas')->getContent();

        $this->assertStringContainsString("localStorage.getItem('hz.sidebar.collapsed')", $html);
        $this->assertLessThan(strpos($html, '</head>'), strpos($html, "hz.sidebar.collapsed"), 'the collapsed state must be read in <head> to avoid a flash');
    }

    public function test_admin_pages_of_every_section_render_inside_the_new_shell(): void
    {
        // /admin/ajustes (option builder) is covered by the E2E suite: its PHP config files declare functions without a
        // guard, so rendering it twice in one PHP process (i.e. in two tests) is a fatal error.
        $admin = $this->admin();
        foreach (['/admin/estadisticas', '/admin/usuarios', '/admin/cursos', '/admin/inscripciones', '/admin/metodos-de-pago', '/admin/blog', '/admin/blog/crear', '/admin/contactos', '/admin/categorias', '/admin/menus', '/admin/seo', '/admin/paginas-de-curso', '/admin/facturas', '/admin/perfil', '/admin/ajustes-de-correo'] as $uri) {
            $response = $this->actingAs($admin)->get($uri);
            $response->assertOk();
            $this->assertStringContainsString('hz-sidebar', $response->getContent(), $uri);
            $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $response->getContent(), "$uri must stay noindex");
        }
    }

    public function test_instructor_and_student_screens_render_inside_the_new_shell(): void
    {
        foreach ([['tutor', ['/tutor/panel', '/gestionar-cursos', '/crear-curso', '/tutor/facturas', '/tutor/perfil/datos-personales', '/tutor/tareas']],
                  ['student', ['/mis-cursos', '/estudiante/facturas', '/estudiante/perfil/datos-personales', '/estudiante/certificados', '/estudiante/datos-de-facturacion']]] as [$role, $uris]) {
            foreach ($uris as $uri) {
                $r = $this->actingAs($this->{$role}())->get($uri);
                if ($r->status() === 302) { // academy mode hides a few marketplace screens
                    continue;
                }
                $r->assertOk();
                $this->assertStringContainsString('hz-sidebar', $r->getContent(), "$role $uri");
            }
        }
    }

    public function test_marketplace_entries_stay_out_of_the_academy_sidebar(): void
    {
        $html = $this->actingAs($this->student())->get('/mis-cursos')->getContent();

        $this->assertStringNotContainsString('hz-wallet', $html);
        $this->assertStringNotContainsString('Retirar', $html);
    }

    // ------------------------------------------------------------------ async / real time

    public function test_pulse_endpoint_requires_login_and_never_caches(): void
    {
        $this->getJson(route('panel.pulse'))->assertUnauthorized();

        $res = $this->actingAs($this->student())->getJson(route('panel.pulse'))->assertOk()
            ->assertJsonStructure(['notifications', 'messages', 'cart', 'at']);
        $this->assertStringContainsString('no-store', $res->headers->get('Cache-Control'));
    }

    public function test_pulse_counts_unread_notifications_live(): void
    {
        $student = $this->student();
        $before = $this->actingAs($student)->getJson(route('panel.pulse'))->json('notifications');

        \Illuminate\Support\Facades\DB::table('notifications')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(), 'type' => 'Tests\\Pulse', 'notifiable_type' => \App\Models\User::class, 'notifiable_id' => $student->id,
            'data' => json_encode(['subject' => 'x']), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertSame($before + 1, $this->actingAs($student)->getJson(route('panel.pulse'))->json('notifications'));
        $this->assertSame(0, $this->actingAs($this->admin())->getJson(route('panel.pulse'))->json('notifications'), 'admins have no inbox badge');
    }

    public function test_pulse_url_is_published_to_the_browser_and_is_private(): void
    {
        $html = $this->actingAs($this->student())->get('/mis-cursos')->getContent();
        $this->assertStringContainsString('<meta name="hz-pulse" content="' . route('panel.pulse') . '">', $html);
        $this->assertStringContainsString('data-hz-badge="notifications"', $html);

        $robots = $this->get('/robots.txt')->getContent();
        $this->assertStringContainsString('Disallow: /panel/', $robots);
    }

    public function test_live_screens_reference_real_routes_and_only_read_mostly_ones(): void
    {
        foreach (PanelLive::SCREENS as $name => $seconds) {
            $this->assertTrue(Route::has($name), "PanelLive references unknown route $name");
            $this->assertGreaterThanOrEqual(15, $seconds, 'polling faster than 15 s would hammer the server');
        }
        foreach (['admin.create-blog', 'admin.update-blog', 'courses.tutor.create-course', 'admin.option-builder', 'student.profile.personal-details', 'admin.manage-menus', 'admin.seo'] as $form) {
            $this->assertArrayNotHasKey($form, PanelLive::SCREENS, "$form is a form: it must never auto-refresh");
        }
    }

    public function test_live_interval_is_published_only_on_live_screens(): void
    {
        $this->actingAs($this->admin())->get('/admin/usuarios')->assertSee('data-hz-live="' . PanelLive::SCREENS['admin.users'] . '"', false);
        $this->actingAs($this->admin())->get('/admin/seo')->assertDontSee('data-hz-live', false);
        $this->actingAs($this->admin())->get('/admin/blog/crear')->assertDontSee('data-hz-live', false);
    }

    public function test_livewire_actions_of_new_admin_screens_keep_checking_the_role(): void
    {
        $persistent = \Livewire\Livewire::getPersistentMiddleware();
        $this->assertContains(\Spatie\Permission\Middleware\RoleMiddleware::class, $persistent);
    }

    private function contrast(string $a, string $b): float
    {
        $lum = function (string $hex): float {
            [$r, $g, $bl] = array_map(fn ($c) => hexdec($c) / 255, str_split(ltrim($hex, '#'), 2));
            $f = fn ($v) => $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;

            return 0.2126 * $f($r) + 0.7152 * $f($g) + 0.0722 * $f($bl);
        };
        [$l1, $l2] = [$lum($a), $lum($b)];

        return (max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05);
    }
}
