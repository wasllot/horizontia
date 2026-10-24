<?php

namespace Tests\Feature;

use App\Livewire\Pages\Admin\Refunds\Refunds;
use App\Livewire\Pages\Student\RefundRequests;
use App\Mail\RefundDecisionEmail;
use App\Mail\RefundRequestedEmail;
use App\Models\CourseCertificate;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Services\RefundRequestService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Models\Watchtime;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Garantía de reembolso: la persona lo pide con las reglas de la política y el equipo lo aprueba (devuelve y retira el acceso) o rechaza con motivo. */
class RefundRequestsTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    private RefundRequestService $svc;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.refund_days' => 7, 'academy.contact_email' => 'equipo@horizontia.test', 'academy.certificates' => true]);
        Mail::fake();
        $this->svc = new RefundRequestService();
        $this->course = $this->course();
        $this->course->update(['content_length' => 1000]);
        $s = $this->student();
        Watchtime::where('user_id', $s->id)->delete();
        CourseCertificate::where('user_id', $s->id)->delete();
        RefundRequest::query()->delete();
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function paidOrder(float $amount = 899.1, ?string $status = 'complete', $user = null): Order
    {
        $user ??= $this->student();
        $order = Order::create([
            'user_id' => $user->id, 'amount' => $amount, 'currency' => 'MXN', 'first_name' => 'Ana', 'last_name' => 'Pagado', 'email' => $user->email, 'company' => '', 'country' => '', 'state' => '',
            'postal_code' => '', 'city' => '', 'phone' => '', 'description' => '', 'payment_method' => 'manual', 'status' => $status, 'unique_payment_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);
        DB::table('order_items')->insert(['order_id' => $order->id, 'title' => $this->course->title, 'quantity' => 1, 'price' => $amount, 'total' => $amount, 'platform_fee' => 0,
            'orderable_type' => Course::class, 'orderable_id' => $this->course->id, 'created_at' => now(), 'updated_at' => now()]);
        Enrollment::updateOrCreate(['student_id' => $user->id, 'course_id' => $this->course->id], ['tutor_id' => $this->course->instructor_id, 'status' => 'active']);
        // el alta del alumno es «la fecha de pago»: la de los datos de prueba es vieja, así que la ponemos en el momento de la compra
        DB::table((new Enrollment())->getTable())->where('student_id', $user->id)->where('course_id', $this->course->id)->update(['created_at' => now()]);

        return $order;
    }

    private function watch(int $seconds): void
    {
        $l = Curriculum::whereHas('section', fn ($q) => $q->where('course_id', $this->course->id))->first();
        Watchtime::create(['user_id' => $this->student()->id, 'curriculum_id' => $l->id, 'course_id' => $this->course->id, 'section_id' => $l->section_id, 'duration' => $seconds]);
    }

    public function test_a_recent_paid_order_with_little_progress_is_eligible(): void
    {
        $check = $this->svc->eligibility($this->student(), $this->paidOrder());

        $this->assertTrue($check['ok'], (string) $check['reason']);
        $this->assertTrue($check['deadline']->isFuture());
        $this->assertSame(0, $check['progress']);
    }

    public function test_the_policy_rules_are_enforced(): void
    {
        $s = $this->student();
        $this->assertStringContainsString('no es tuyo', $this->svc->eligibility($this->demoStudent(), $this->paidOrder())['reason']);
        $this->assertStringContainsString('gratuitos', $this->svc->eligibility($s, $this->paidOrder(0))['reason']);
        $this->assertStringContainsString('pagados', $this->svc->eligibility($s, $this->paidOrder(100, 'pending'))['reason']);
        $this->assertStringContainsString('ya fue reembolsado', $this->svc->eligibility($s, $this->paidOrder(100, 'refunded'))['reason']);

        $old = $this->paidOrder();
        DB::table($this->enrollmentTable())->where('student_id', $s->id)->where('course_id', $this->course->id)->update(['created_at' => now()->subDays(30)]);
        $this->assertStringContainsString('Pasaron más de 7 días', $this->svc->eligibility($s, $old->fresh())['reason']);

        DB::table($this->enrollmentTable())->where('student_id', $s->id)->where('course_id', $this->course->id)->update(['created_at' => now()]);
        $this->watch(300);   // 30 % de 1000 s
        $over = $this->svc->eligibility($s, $old->fresh());
        $this->assertFalse($over['ok']);
        $this->assertStringContainsString('30 %', $over['reason']);
        $this->assertSame(30, $over['progress']);

        Watchtime::where('user_id', $s->id)->delete();
        CourseCertificate::create(['user_id' => $s->id, 'course_id' => $this->course->id, 'code' => 'HZ-RFND-' . strtoupper(substr(uniqid(), -6)), 'student_name' => 'Ana', 'course_title' => 'X', 'issued_at' => now()]);
        $this->assertStringContainsString('certificado', $this->svc->eligibility($s, $old->fresh())['reason']);
    }

    private function enrollmentTable(): string
    {
        return (new Enrollment())->getTable();
    }

    public function test_requesting_needs_a_reason_notifies_the_team_and_blocks_a_second_request(): void
    {
        $order = $this->paidOrder();

        $short = $this->svc->request($this->student(), $order, 'corto');
        $this->assertFalse($short['ok']);
        $this->assertSame(0, RefundRequest::count());

        $ok = $this->svc->request($this->student(), $order, '  <b>No era lo que esperaba</b> del temario  ');
        $this->assertTrue($ok['ok']);
        $this->assertSame('No era lo que esperaba del temario', $ok['request']->reason, 'HTML stripped');
        Mail::assertQueued(RefundRequestedEmail::class, fn ($m) => $m->hasTo('equipo@horizontia.test'));

        $again = $this->svc->request($this->student(), $order, 'Quiero pedirlo otra vez por favor');
        $this->assertFalse($again['ok']);
        $this->assertStringContainsString('en revisión', $again['message']);
        $this->assertSame(1, RefundRequest::count());
    }

    public function test_approving_refunds_the_order_removes_access_and_tells_the_student(): void
    {
        $s = $this->student();
        $order = $this->paidOrder();
        $request = $this->svc->request($s, $order, 'No era lo que esperaba del curso')['request'];

        $res = $this->svc->approve($request, $this->admin());

        $this->assertTrue($res['ok'], $res['message']);
        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertFalse(Enrollment::where('student_id', $s->id)->where('course_id', $this->course->id)->exists(), 'access removed');
        $this->assertSame(RefundRequest::APPROVED, $request->fresh()->status);
        $this->assertSame($this->admin()->id, (int) $request->fresh()->reviewed_by);
        Mail::assertQueued(RefundDecisionEmail::class, fn ($m) => $m->hasTo($s->email) && str_contains($m->render(), 'Aprobamos tu reembolso'));

        $this->assertFalse($this->svc->approve($request->fresh(), $this->admin())['ok'], 'cannot be resolved twice');
    }

    public function test_rejecting_needs_a_reason_keeps_the_access_and_sends_the_reason(): void
    {
        $s = $this->student();
        $order = $this->paidOrder();
        $request = $this->svc->request($s, $order, 'No era lo que esperaba del curso')['request'];

        $this->assertFalse($this->svc->reject($request, $this->admin(), 'no')['ok']);
        $this->assertSame(RefundRequest::PENDING, $request->fresh()->status);

        $this->assertTrue($this->svc->reject($request, $this->admin(), 'Ya viste buena parte del contenido.')['ok']);
        $this->assertSame('complete', $order->fresh()->status);
        $this->assertTrue(Enrollment::where('student_id', $s->id)->where('course_id', $this->course->id)->exists());
        Mail::assertQueued(RefundDecisionEmail::class, fn ($m) => str_contains($m->render(), 'Ya viste buena parte del contenido.'));
    }

    public function test_the_student_box_lists_recent_paid_orders_and_the_request_flow_works(): void
    {
        $order = $this->paidOrder();

        $page = Livewire::actingAs($this->student())->test(RefundRequests::class)->assertSee('Garantía de reembolso de 7 días')->assertSee('Solicitar reembolso');
        $page->call('open', $order->id)->assertSet('openOrder', $order->id)
            ->set('reason', 'corto')->call('submit')->assertHasErrors('reason')
            ->set('reason', 'El temario no cubre lo que necesito')->call('submit')->assertHasNoErrors()->assertSet('openOrder', null)->assertSee('En revisión');

        $this->assertSame(1, RefundRequest::where('order_id', $order->id)->count());
    }

    public function test_the_student_box_is_hidden_without_paid_orders_and_nobody_touches_someone_elses_order(): void
    {
        Order::where('user_id', $this->student()->id)->delete();
        Livewire::actingAs($this->student())->test(RefundRequests::class)->assertDontSee('Garantía de reembolso');

        $foreign = $this->paidOrder(500, 'complete', $this->demoStudent());
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Livewire::actingAs($this->student())->test(RefundRequests::class)->call('open', $foreign->id);
    }

    public function test_the_admin_inbox_approves_and_rejects_and_is_admin_only(): void
    {
        $a = $this->svc->request($this->student(), $this->paidOrder(), 'No era lo que esperaba del curso')['request'];

        $page = Livewire::actingAs($this->admin())->test(Refunds::class)->assertSee('Por revisar')->assertSee('No era lo que esperaba del curso');
        $page->call('askReject', $a->id)->set('note', 'x')->call('reject')->assertHasErrors('note')
            ->set('note', 'Pasó el límite de avance.')->call('reject')->assertHasNoErrors();
        $this->assertSame(RefundRequest::REJECTED, $a->fresh()->status);

        $order2 = $this->paidOrder();
        $b = $this->svc->request($this->student(), $order2, 'Prefiero otro curso del catálogo')['request'];
        Livewire::actingAs($this->admin())->test(Refunds::class)->call('approve', $b->id);
        $this->assertSame(RefundRequest::APPROVED, $b->fresh()->status);
        $this->assertSame('refunded', $order2->fresh()->status);

        Livewire::actingAs($this->student())->test(Refunds::class)->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.refunds'))->assertOk()->assertSee('Reembolsos');
    }

    public function test_the_invoices_page_embeds_the_box_for_students_and_the_menu_links_to_the_inbox(): void
    {
        $this->paidOrder();

        // la lista de facturas se carga después (wire:init): se prueba su componente con la caja dentro
        Livewire::actingAs($this->student())->test(\App\Livewire\Pages\Student\Invoices::class)->call('loadData')->assertSeeLivewire(RefundRequests::class);

        $this->actingAs($this->admin())->get(route('admin.insights'))->assertOk()->assertSee(route('admin.refunds'), false);
    }
}
