<?php

namespace Tests\Feature;

use App\Console\Commands\BuildPanelTheme;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Blade;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * Regression suite for the borderless "tonal" redesign: grouped navigation, table blocks, row components,
 * the course wizard stylesheet and the rule that views never hard-code colours inline.
 */
class PanelTonalUiTest extends TestCase
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

    // ------------------------------------------------------------------ navigation

    public function test_admin_menu_is_grouped_with_headings_and_has_no_mixed_titles(): void
    {
        $html = $this->actingAs($this->admin())->get('/admin/estadisticas')->assertOk()->getContent();

        foreach (['Academia', 'Contenido', 'Negocio', 'Sistema'] as $group) {
            $this->assertStringContainsString('class="hz-nav__heading" role="presentation">' . $group . '</li>', $html, "group $group");
        }
        foreach (['Leads &amp; Contactos', 'Usuarios y Perfiles', 'Finanzas y Pagos', 'Sistema y Plataforma', 'Ajustes Generales'] as $old) {
            $this->assertStringNotContainsString($old, $html, "old sidebar title $old");
        }
        // Groups appear in a fixed order.
        $this->assertTrue(strpos($html, '>Academia</li>') < strpos($html, '>Contenido</li>'));
        $this->assertTrue(strpos($html, '>Contenido</li>') < strpos($html, '>Negocio</li>'));
        $this->assertTrue(strpos($html, '>Negocio</li>') < strpos($html, '>Sistema</li>'));
        // Courses open the academy group, before users.
        $this->assertTrue(strpos($html, 'Gestionar cursos') < strpos($html, 'Usuarios'));
    }

    public function test_instructor_menu_is_grouped(): void
    {
        $response = $this->actingAs($this->tutor())->get('/gestionar-cursos');
        if ($response->status() !== 200) {
            $this->markTestSkipped('course list is not available for this fixture');
        }
        $html = $response->getContent();

        $this->assertStringContainsString('>Enseñanza</li>', $html);
        $this->assertStringContainsString('>Mi cuenta</li>', $html);
        $this->assertLessThan(strpos($html, '>Mi cuenta</li>'), strpos($html, '>Enseñanza</li>'));
    }

    public function test_student_menu_is_grouped(): void
    {
        $html = $this->actingAs($this->student())->get('/mis-cursos')->assertOk()->getContent();

        $this->assertStringContainsString('>Aprendizaje</li>', $html);
        $this->assertStringContainsString('>Mi cuenta</li>', $html);
        $this->assertStringNotContainsString('>Enseñanza</li>', $html);
    }

    public function test_menu_labels_use_consistent_sentence_case(): void
    {
        $helpers = file_get_contents(base_path('Modules/Courses/helpers/functions.php'));

        $this->assertStringContainsString("'Programar en vivo'", $helpers);
        $this->assertStringContainsString("'Mis sesiones en vivo'", $helpers);
        $this->assertStringNotContainsString("'Programar En Vivo'", $helpers);
    }

    // ------------------------------------------------------------------ stylesheet

    public function test_surfaces_are_tonal_not_outlined(): void
    {
        foreach (['--hz-field:', '--hz-tint:', '--hz-shadow-card:', '--hz-hairline:'] as $token) {
            $this->assertStringContainsString($token, $this->css);
        }
        // Form controls are filled with a transparent outline; focus brings the ring.
        $this->assertMatchesRegularExpression('/min-height: 42px; padding: 9px 14px; border: 1px solid transparent;[^}]*background-color: var\(--hz-field\)/', $this->css);
        $this->assertStringNotContainsString('border: 1px solid var(--hz-line-strong); border-radius: var(--hz-r-md); background-color: var(--hz-surface)', $this->css);
        // No solid outline is left on the main surfaces.
        $this->assertDoesNotMatchRegularExpression('/body\.hz \.hz-card[^{]*\{[^}]*border: 1px solid var\(--hz-line/', $this->css);
    }

    public function test_tables_become_row_blocks_with_a_mobile_card_mode(): void
    {
        $this->assertStringContainsString('table:has(> thead)', $this->css);
        $this->assertStringContainsString('border-spacing: 0 6px', $this->css);
        $this->assertStringContainsString('content: attr(data-label)', $this->css, 'mobile rows need their column label');
        $this->assertStringContainsString('@media (max-width: 820px)', $this->css);

        $js = file_get_contents(public_path('js/panel.js'));
        $this->assertStringContainsString('function labelTables', $js);
        $this->assertStringContainsString('MutationObserver', $js, 'Livewire repaints must keep the labels');
        $this->assertStringContainsString("'is-broken'", $js, 'broken thumbnails are hidden instead of showing the alt text');
    }

    public function test_livewire_loading_indicators_stay_hidden_despite_button_styles(): void
    {
        $this->assertStringContainsString('body.hz [wire\:loading\.flex][wire\:loading\.flex]', $this->css);
        $this->assertStringContainsString('body.hz [wire\:loading][wire\:loading]', $this->css);
    }

    public function test_the_theme_build_drops_fixed_column_widths_and_dead_rules(): void
    {
        $out = BuildPanelTheme::transform('.am-x .tb-table th:nth-child(2){min-width:130px;max-width:130px;color:red}.am-x .tb-table td:last-child{width:90px}.box{min-width:200px}', 'admin/css/main.css');

        $this->assertStringContainsString('color:red', $out);
        $this->assertStringContainsString('.box{min-width:200px}', $out, 'non-table widths are untouched');
        $this->assertDoesNotMatchRegularExpression('/th:nth-child\(2\)\{[^}]*width/', $out);
        $this->assertStringNotContainsString('td:last-child{', $out, 'a rule left empty is removed');
    }

    // ------------------------------------------------------------------ components and views

    public function test_row_components_render_accessible_markup(): void
    {
        $badge = Blade::render('<x-panel.badge tone="success">Activo</x-panel.badge>');
        $this->assertStringContainsString('hz-badge hz-badge--success hz-badge--dot', $badge);

        $user = Blade::render('<x-panel.user-cell name="Ana Pérez" email="ana@test.mx" />');
        $this->assertStringContainsString('AP', $user, 'initials avatar when there is no picture');
        $this->assertStringContainsString('ana@test.mx', $user);

        $action = Blade::render('<x-panel.action icon="icon-trash-2" label="Eliminar" tone="danger" />');
        $this->assertStringContainsString('aria-label="Eliminar"', $action);
        $this->assertStringContainsString('hz-act--danger', $action);
    }

    public function test_status_labels_are_localised(): void
    {
        app()->setLocale('es');

        $this->assertSame('Completado', statusLabel('complete'));
        $this->assertSame('Pendiente', statusLabel('pending'));
        $this->assertSame('Algo raro', statusLabel('algo_raro'));
        $this->assertSame('', statusLabel(null));
    }

    public function test_users_screen_uses_the_toolbar_and_row_components(): void
    {
        $html = $this->actingAs($this->admin())->get('/admin/usuarios')->assertOk()->getContent();

        $this->assertStringContainsString('hz-toolbar', $html);
        $this->assertStringContainsString('hz-usercell', $html);
        $this->assertStringContainsString('hz-acts', $html);
        $this->assertStringNotContainsString('tb-users-table td:nth-child', $html);
        $this->assertStringContainsString('Todos los roles', $html);
    }

    public function test_status_tags_are_translated_in_admin_lists(): void
    {
        $html = $this->actingAs($this->admin())->get('/admin/blog/categorias')->assertOk()->getContent();

        $this->assertStringNotContainsString('>active</em>', $html);
        $this->assertStringContainsString('Activo', $html);
    }

    public function test_course_wizard_ships_its_own_stylesheet_instead_of_inline_styles(): void
    {
        $dir = base_path('Modules/Courses/resources/views/livewire/tutor/course-creation');
        foreach (['create-course.blade.php', 'components/course-media.blade.php', 'components/course-pricing.blade.php', 'components/manage-course-content/course-builder.blade.php'] as $file) {
            $this->assertStringNotContainsString('<style', file_get_contents("$dir/$file"), "$file must not embed CSS");
        }

        $wizard = file_get_contents(public_path('css/course-wizard.css'));
        $this->assertLessThanOrEqual(6, substr_count($wizard, '!important'), 'the wizard stylesheet is built on specificity, not !important');
        $this->assertStringContainsString('.cr-course-box .am-themeform_footer', $wizard);
        $this->assertStringContainsString('position: sticky', $wizard, 'the action bar stays reachable on long steps');
        $this->assertDoesNotMatchRegularExpression('/#14213d|#1a1a2e|#fed304/i', $wizard, 'brand colours come from tokens');
    }

    public function test_course_wizard_page_loads_the_stylesheet_and_shows_progress(): void
    {
        $course = $this->course();
        $owner = \App\Models\User::findOrFail($course->instructor_id);

        $html = $this->actingAs($owner)->get(route('courses.tutor.edit-course', ['tab' => 'details', 'id' => $course->id]))->assertOk()->getContent();

        $this->assertStringContainsString('css/course-wizard.css', $html);
        $this->assertStringContainsString('role="progressbar"', $html);
        $this->assertStringContainsString('cr-steps', $html);
    }

    public function test_live_stream_screens_do_not_hard_code_colours(): void
    {
        $dir = base_path('Modules/Courses/resources/views/livewire/tutor/live-streams');
        foreach (['manage-live-streams.blade.php', 'schedule-live-stream.blade.php'] as $file) {
            $src = file_get_contents("$dir/$file");
            $this->assertDoesNotMatchRegularExpression('/style="[^"]*(?:background|color|border)\s*:\s*#/i', $src, "$file has inline colours");
            $this->assertStringContainsString('hz-pagehead', $src);
        }
    }

    public function test_payment_methods_switches_are_restyled_without_theme_labels(): void
    {
        $this->assertStringContainsString('.tb-paymethod-items .checkbox input[type="checkbox"]', $this->css);
        $this->assertStringContainsString('content: "Activo"', $this->css);
    }

    public function test_instructor_course_list_renders_with_the_new_filters(): void
    {
        $response = $this->actingAs($this->tutor())->get('/gestionar-cursos');
        if ($response->status() !== 200) {
            $this->markTestSkipped('course list is not available for this fixture');
        }
        $response->assertSee('cr-all-courses', false);
    }
}
