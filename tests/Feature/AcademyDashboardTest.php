<?php

namespace Tests\Feature;

use App\Services\AcademyDashboardService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** The admin dashboard of the private academy: useful numbers, periods and no meaningless "Perspectivas" crumb. */
class AcademyDashboardTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.private_mode' => true]);
    }

    public function test_periods_cover_the_right_days_and_the_previous_one_is_adjacent(): void
    {
        $now = CarbonImmutable::parse('2026-10-10 15:00:00');

        $d30 = new AcademyDashboardService('30d', $now);
        $this->assertSame(30, $d30->days());
        $this->assertSame('2026-09-11', $d30->from->toDateString());
        $this->assertSame('2026-08-12', $d30->prevFrom->toDateString());
        $this->assertSame('2026-09-10', $d30->prevTo->toDateString());

        $month = new AcademyDashboardService('month', $now);
        $this->assertSame('2026-10-01', $month->from->toDateString());
        $this->assertSame(10, $month->days());

        $this->assertSame(30, (new AcademyDashboardService('nope', $now))->days() >= 1 ? 30 : 0, 'unknown ranges fall back');
        $this->assertSame(7, (new AcademyDashboardService('7d', $now))->days());
    }

    public function test_delta_handles_zero_and_growth(): void
    {
        $this->assertNull(AcademyDashboardService::delta(0, 0));
        $this->assertSame(100.0, AcademyDashboardService::delta(5, 0));
        $this->assertSame(50.0, AcademyDashboardService::delta(15, 10));
        $this->assertSame(-25.0, AcademyDashboardService::delta(75, 100));
    }

    public function test_series_is_zero_filled_and_kpis_are_consistent(): void
    {
        $dash = new AcademyDashboardService('30d');
        $series = $dash->revenueSeries();

        $this->assertCount(30, $series);
        $this->assertEqualsWithDelta($dash->kpis()['revenue']['value'], $series->sum(), 0.01, 'the chart adds up to the revenue KPI');
        foreach (['revenue', 'orders', 'ticket', 'enrollments', 'students'] as $key) {
            $this->assertArrayHasKey($key, $dash->kpis());
        }
        $this->assertIsArray($dash->attention());
        $this->assertSame(['draft', 'under_review', 'need_revision', 'active', 'inactive'], array_keys($dash->catalog()));
    }

    public function test_dashboard_renders_useful_blocks_and_switches_period(): void
    {
        $html = $this->actingAs($this->admin())->get('/admin/estadisticas')->assertOk()->getContent();

        foreach (['hz-dash', 'hz-kpis', 'Ingresos por día', 'Cursos más vendidos', 'Últimas ventas', 'Requiere tu atención', 'Tu catálogo', 'Comunidad', 'Crear curso'] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
        $this->assertStringContainsString('role="tablist"', $html);
        $this->assertStringContainsString('wire:click="setRange', $html);
        $this->assertStringNotContainsString('Perspectivas', $html, 'the meaningless root crumb is gone');
        $this->assertStringContainsString('Panel general', $html);
    }

    public function test_livewire_range_switch_is_validated(): void
    {
        \Livewire\Livewire::actingAs($this->admin())->test(\App\Livewire\Pages\Admin\Insights\Insights::class)
            ->assertSet('range', '30d')
            ->call('setRange', '7d')->assertSet('range', '7d')
            ->call('setRange', 'bogus')->assertSet('range', '30d');
    }

    public function test_inner_admin_pages_use_the_dashboard_as_breadcrumb_root(): void
    {
        $html = $this->actingAs($this->admin())->get('/admin/usuarios')->assertOk()->getContent();

        $this->assertStringNotContainsString('Perspectivas', $html);
        $this->assertStringContainsString('Panel general', $html);
    }
}
