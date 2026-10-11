<?php

namespace Tests\Feature;

use App\Livewire\Pages\Admin\Reports\Reports;
use App\Models\Coupon;
use App\Models\Order;
use App\Services\BusinessReportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Models\Section;
use Modules\Courses\Models\Watchtime;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Reportes de negocio: cifras calculadas del servidor, solo para administradores. */
class BusinessReportsTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        // Aislamos el periodo: los datos sembrados de otros tests no cuentan en lo que medimos.
        $this->from = now()->subDays(400)->toDateTimeString();
        Order::query()->update(['created_at' => '2020-01-01 00:00:00']);
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private string $from;

    private function course(string $title = null): Course
    {
        $c = $this->course2()->replicate();
        $c->title = $title ?? 'Curso reporte ' . uniqid();
        $c->slug = 'rp-' . uniqid();
        $c->status = 'active';
        $c->delivery = 'online';
        $c->save();

        return $c;
    }

    private function order(Course $course, float $price, string $status = 'complete', array $over = []): Order
    {
        $o = Order::create($over + ['user_id' => $this->student()->id, 'unique_payment_id' => (string) Str::uuid(), 'amount' => $price, 'used_wallet_amt' => 0, 'currency' => 'MXN', 'first_name' => 'A', 'last_name' => 'B', 'company' => '', 'country' => 'MX', 'state' => 'X', 'postal_code' => '1', 'city' => 'X', 'phone' => '1', 'email' => 'cliente@test.mx', 'payment_method' => 'mercadopago', 'status' => $status]);
        $o->items()->create(['title' => $course->title, 'quantity' => 1, 'price' => $price, 'total' => $price, 'options' => [], 'orderable_type' => Course::class, 'orderable_id' => $course->id]);

        return $o;
    }

    public function test_the_summary_counts_paid_pending_and_refunded_orders_and_the_conversion(): void
    {
        $c = $this->course();
        $this->order($c, 1000);
        $this->order($c, 500, 'complete', ['coupon_code' => 'X', 'discount_amount' => 100]);
        $this->order($c, 700, 'pending');
        $this->order($c, 300, 'refunded');

        $s = (new BusinessReportService('30d'))->summary();

        $this->assertEquals(1500.0, $s['gross']);
        $this->assertSame(2, $s['orders']);
        $this->assertEquals(750.0, $s['ticket']);
        $this->assertEquals(100.0, $s['discounts']);
        $this->assertSame(4, $s['initiated']);
        $this->assertSame(50.0, $s['conversion']);
        $this->assertSame([1, 300.0], [$s['refunds'], $s['refunded_amount']]);
        $this->assertSame([1, 700.0], [$s['pending'], $s['pending_amount']]);
    }

    public function test_orders_outside_the_period_do_not_count(): void
    {
        $c = $this->course();
        $old = $this->order($c, 900);
        DB::table('orders')->where('id', $old->id)->update(['created_at' => now()->subDays(60)]);
        $this->order($c, 100);

        $this->assertEquals(100.0, (new BusinessReportService('30d'))->summary()['gross']);
        $this->assertEquals(1000.0, (new BusinessReportService('90d'))->summary()['gross']);
    }

    public function test_top_courses_by_revenue_with_their_share_and_the_payment_methods(): void
    {
        [$a, $b] = [$this->course('Curso A'), $this->course('Curso B')];
        $this->order($a, 800);
        $this->order($a, 800, 'complete', ['payment_method' => 'stripe']);
        $this->order($b, 400);

        $svc = new BusinessReportService('30d');
        $top = $svc->topCourses();

        $this->assertSame(['Curso A', 'Curso B'], $top->pluck('title')->all());
        $this->assertSame([2, 1600.0, 80.0], [$top[0]->sales, $top[0]->revenue, $top[0]->share]);
        $this->assertEquals(['mercadopago' => 1200.0, 'stripe' => 800.0], $svc->byMethod()->pluck('amount', 'method')->all());
    }

    public function test_the_series_is_zero_filled_and_switches_to_months_for_long_periods(): void
    {
        $this->order($this->course(), 250);

        $days = (new BusinessReportService('30d'))->series();
        $this->assertCount(30, $days);
        $this->assertEquals(250.0, $days[now()->toDateString()]);

        $months = (new BusinessReportService('year'))->series();
        $this->assertLessThanOrEqual(13, $months->count());
        $this->assertEquals(250.0, $months[now()->format('Y-m')]);
    }

    public function test_coupon_performance(): void
    {
        $c = $this->course();
        Coupon::create(['code' => 'REPORTE20', 'type' => 'percent', 'value' => 20, 'per_user_limit' => 0]);
        $this->order($c, 800, 'complete', ['coupon_code' => 'REPORTE20', 'discount_amount' => 200]);
        $this->order($c, 400, 'complete', ['coupon_code' => 'REPORTE20', 'discount_amount' => 100]);

        $row = (new BusinessReportService('30d'))->coupons()->first();

        $this->assertSame(['REPORTE20', 2, 300.0, 1200.0, '20%'], [$row->code, $row->uses, $row->discount, $row->revenue, $row->label]);
    }

    private function courseWithStudents(): array
    {
        $c = $this->course();
        $s = Section::create(['course_id' => $c->id, 'title' => 'S', 'description' => 'S', 'sort_order' => 1]);
        $l1 = Curriculum::create(['section_id' => $s->id, 'title' => 'Lección 1', 'description' => 'x', 'type' => 'video', 'media_path' => 'x.mp4', 'content_length' => 600, 'sort_order' => 1, 'is_preview' => false]);
        $l2 = Curriculum::create(['section_id' => $s->id, 'title' => 'Lección 2', 'description' => 'x', 'type' => 'video', 'media_path' => 'x.mp4', 'content_length' => 600, 'sort_order' => 2, 'is_preview' => false]);
        $c->update(['content_length' => 1200]);
        $students = [$this->student(), $this->demoStudent(), $this->tutor()];
        foreach ($students as $u) {
            Enrollment::updateOrCreate(['student_id' => $u->id, 'course_id' => $c->id], ['tutor_id' => $c->instructor_id, 'status' => 'active']);
        }
        $w = fn ($u, $l, $d) => Watchtime::create(['user_id' => $u->id, 'course_id' => $c->id, 'section_id' => $s->id, 'curriculum_id' => $l->id, 'duration' => $d]);
        $w($students[0], $l1, 600); $w($students[0], $l2, 600);   // terminó
        $w($students[1], $l1, 600);                              // a medias
        // el tercero no empezó

        return [$c, $l1, $l2];
    }

    public function test_completion_and_the_lesson_dropoff(): void
    {
        [$c] = $this->courseWithStudents();
        $svc = new BusinessReportService('30d');

        $row = $svc->learning()->firstWhere('id', $c->id);
        $this->assertSame([3, 2, 1], [$row->enrolled, $row->started, $row->completed]);
        $this->assertSame(33.3, $row->completion);
        $this->assertSame(50, $row->avg_progress);

        $funnel = $svc->lessonFunnel($c->id);
        $this->assertSame([67, 33], $funnel->pluck('percent')->all());
        $this->assertSame([0, 34], $funnel->pluck('drop')->all());
    }

    public function test_the_screen_shows_the_figures_and_switches_sections_and_periods(): void
    {
        $this->order($this->course('Curso visible'), 1234);
        $page = Livewire::actingAs($this->admin())->test(Reports::class);

        $page->assertSee('Curso visible')->assertSee('data-testid="kpi-gross"', false);
        $page->call('setTab', 'aprendizaje')->assertSet('tab', 'aprendizaje');
        $page->call('setTab', 'cupones')->assertSee('Rendimiento de cupones');
        $page->call('setRange', 'year')->assertSet('range', 'year');
        $page->call('setRange', 'hack')->assertSet('range', '30d');
    }

    public function test_csv_exports_have_the_data_and_neutralize_spreadsheet_formulas(): void
    {
        $c = $this->course('=HYPERLINK("http://x")');
        $this->order($c, 500);

        $res = $this->actingAs($this->admin())->get(route('admin.reports.export', ['type' => 'ventas']))->assertOk();
        $csv = $res->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Pedido,Fecha,Correo', $csv);
        $this->assertStringContainsString('cliente@test.mx', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv, 'a cell starting with = must not run as a formula');
        $this->actingAs($this->admin())->get(route('admin.reports.export', ['type' => 'cursos']))->assertOk();
        $this->actingAs($this->admin())->get(route('admin.reports.export', ['type' => 'aprendizaje']))->assertOk();
    }

    public function test_only_admins_see_or_download_reports(): void
    {
        $this->assertNotSame(200, $this->actingAs($this->student())->get(route('admin.reports'))->getStatusCode());
        $this->assertNotSame(200, $this->actingAs($this->student())->get(route('admin.reports.export', ['type' => 'ventas']))->getStatusCode());
        Livewire::actingAs($this->student())->test(Reports::class)->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.reports'))->assertOk()->assertSee('Reportes');
        $this->actingAs($this->admin())->get('/admin/reportes/exportar/otro')->assertNotFound();
    }
}
