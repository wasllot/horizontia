<?php

namespace Tests\Feature;

use App\Livewire\Pages\Admin\Reports\Reports;
use App\Models\AccessRequest;
use App\Models\CourseCertificate;
use App\Services\BusinessReportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Enrollment;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Embudo de acceso: de la solicitud al certificado, con porcentajes por paso y tiempos medianos. */
class AccessFunnelReportTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        AccessRequest::query()->delete();
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function request(array $o = []): AccessRequest
    {
        return AccessRequest::create($o + ['type' => 'student', 'name' => 'Ana', 'email' => uniqid() . '@ejemplo.test', 'status' => 'pending', 'created_at' => now()->subDay(), 'updated_at' => now()]);
    }

    public function test_it_counts_each_step_and_the_conversion_between_them(): void
    {
        $course = Course::first();
        $student = $this->student();
        $this->request(['status' => 'approved', 'user_id' => $student->id, 'reviewed_at' => now()->subDay()->addHours(2), 'activated_at' => now()->subDay()->addHours(5)]);
        $this->request(['status' => 'approved', 'user_id' => null, 'reviewed_at' => now()->subHours(20)]);   // aprobada, aún sin activar
        $this->request(['status' => 'rejected', 'reviewed_at' => now()->subHours(20)]);
        $this->request();                                                                                       // pendiente
        Enrollment::updateOrCreate(['student_id' => $student->id, 'course_id' => $course->id], ['tutor_id' => $course->instructor_id, 'status' => 'active']);
        DB::table('order_items')->whereIn('order_id', DB::table('orders')->where('user_id', $student->id)->pluck('id'))->delete();
        DB::table('orders')->where('user_id', $student->id)->delete();
        CourseCertificate::where('user_id', $student->id)->delete();

        $f = (new BusinessReportService('30d'))->accessFunnel();
        $by = collect($f['students'])->keyBy('key');

        $this->assertSame(4, $by['requested']['count']);
        $this->assertSame(2, $by['approved']['count']);
        $this->assertSame(1, $by['activated']['count']);
        $this->assertSame(1, $by['enrolled']['count']);
        $this->assertSame(0, $by['paid']['count']);
        $this->assertSame(50.0, $by['approved']['of_previous']);
        $this->assertSame(25.0, $by['activated']['of_total']);
        $this->assertSame(1, $f['pending']);
        $this->assertSame(1, $f['rejected']);
        $this->assertNotNull($f['hours_to_review']);
        $this->assertSame(3.0, $f['hours_to_activate']);
    }

    public function test_instructors_are_counted_apart_and_other_periods_do_not_leak_in(): void
    {
        $this->request(['type' => 'tutor', 'status' => 'approved', 'user_id' => $this->tutor()->id, 'activated_at' => now()]);
        $this->request(['created_at' => now()->subDays(200)]);   // fuera de los 30 días

        $f = (new BusinessReportService('30d'))->accessFunnel();

        $this->assertSame(0, $f['students'][0]['count']);
        $this->assertSame(1, $f['tutors']['requested']);
        $this->assertSame(1, $f['tutors']['activated']);
        $this->assertGreaterThanOrEqual(1, $f['tutors']['with_course']);
        $this->assertSame(1, (new BusinessReportService('all'))->accessFunnel()['students'][0]['count']);
    }

    public function test_an_empty_period_shows_a_clear_message_and_only_admins_see_the_tab(): void
    {
        Livewire::actingAs($this->admin())->test(Reports::class)->call('setTab', 'embudo')->assertSee('Aún no hay solicitudes de estudiantes')->assertSee('Instructores');

        $this->request(['status' => 'approved', 'user_id' => $this->student()->id, 'activated_at' => now()]);
        Livewire::actingAs($this->admin())->test(Reports::class)->call('setTab', 'embudo')->assertSee('Del acceso al certificado')->assertSee('Se inscribieron');

        Livewire::actingAs($this->student())->test(Reports::class)->assertForbidden();
    }
}
