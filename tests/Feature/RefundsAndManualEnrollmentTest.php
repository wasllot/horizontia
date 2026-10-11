<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderRefundService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Courses\Livewire\Pages\Admin\CourseEnrollments;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Enrollment;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Reembolsos y altas manuales desde el panel: el pedido queda como «reembolsado» y nunca se borra. */
class RefundsAndManualEnrollmentTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.timezone' => 'America/Mexico_City']);
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function paidOrder(string $method = 'stripe'): Order
    {
        $student = $this->student();
        $course = $this->course2();
        Enrollment::updateOrCreate(['student_id' => $student->id, 'course_id' => $course->id], ['tutor_id' => $course->instructor_id, 'status' => 'active']);
        $order = Order::create([
            'user_id' => $student->id, 'unique_payment_id' => (string) Str::uuid(), 'amount' => 500, 'used_wallet_amt' => 0, 'currency' => 'MXN', 'first_name' => 'Ana', 'last_name' => 'L',
            'company' => '', 'country' => 'MX', 'state' => 'X', 'postal_code' => '1', 'city' => 'X', 'phone' => '1', 'email' => $student->email,
            'payment_method' => $method, 'status' => 'complete', 'transaction_id' => 'tx-' . uniqid(),
        ]);
        OrderItem::create(['order_id' => $order->id, 'title' => $course->title, 'quantity' => 1, 'options' => [], 'price' => 500, 'total' => 500, 'orderable_type' => Course::class, 'orderable_id' => $course->id]);

        return $order->fresh();
    }

    public function test_marking_an_order_refunded_removes_the_access_and_keeps_the_order(): void
    {
        $order = $this->paidOrder();

        $this->assertTrue(app(OrderRefundService::class)->revoke($order));

        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertNotNull(Order::find($order->id), 'the order is kept as the record');
        $this->assertFalse(Enrollment::where(['student_id' => $order->user_id, 'course_id' => $this->course2()->id])->exists());
        $this->assertFalse(app(OrderRefundService::class)->revoke($order->fresh()), 'idempotent');
    }

    public function test_pending_orders_cannot_be_refunded(): void
    {
        $order = $this->paidOrder();
        $order->update(['status' => 'pending']);

        $this->assertFalse(app(OrderRefundService::class)->revoke($order->fresh()));
        $this->assertFalse(app(OrderRefundService::class)->refund($order->fresh())['ok']);
    }

    public function test_the_admin_can_refund_and_mark_orders_from_the_enrollments_screen(): void
    {
        $order = $this->paidOrder();

        Livewire::actingAs($this->admin())->test(CourseEnrollments::class)->assertSee('Reembolsar')->call('refund', $order->id);

        $this->assertSame('refunded', $order->fresh()->status, 'a non-Mercado Pago order is refunded by hand and marked');
        Livewire::actingAs($this->admin())->test(CourseEnrollments::class)->assertSee('Reembolsado');
    }

    public function test_a_mercado_pago_order_is_refunded_through_the_gateway_and_a_rejection_keeps_the_access(): void
    {
        $order = $this->paidOrder('mercadopago');
        \Illuminate\Support\Facades\Http::fake(['api.mercadopago.com/*' => \Illuminate\Support\Facades\Http::response(['message' => 'cannot refund'], 400)]);
        \Larabuild\Optionbuilder\Facades\Settings::set('admin_settings', 'payment_method', ['mercadopago' => ['status' => 'on', 'mp_access_token' => 'TEST-x', 'mp_public_key' => 'TEST-k', 'mp_webhook_secret' => 's']]);

        $result = app(OrderRefundService::class)->refund($order);

        $this->assertFalse($result['ok']);
        $this->assertSame('complete', $order->fresh()->status);
        $this->assertTrue(Enrollment::where(['student_id' => $order->user_id, 'course_id' => $this->course2()->id])->exists(), 'a failed refund keeps the access');
    }

    public function test_a_successful_mercado_pago_refund_marks_the_order_and_removes_the_access(): void
    {
        $order = $this->paidOrder('mercadopago');
        \Illuminate\Support\Facades\Http::fake(['api.mercadopago.com/*' => \Illuminate\Support\Facades\Http::response(['id' => 1, 'status' => 'approved'], 201)]);
        \Larabuild\Optionbuilder\Facades\Settings::set('admin_settings', 'payment_method', ['mercadopago' => ['status' => 'on', 'mp_access_token' => 'TEST-x', 'mp_public_key' => 'TEST-k', 'mp_webhook_secret' => 's']]);

        $result = app(OrderRefundService::class)->refund($order);

        $this->assertTrue($result['ok']);
        $this->assertSame('mercadopago', $result['via']);
        $this->assertSame('refunded', $order->fresh()->status);
        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => str_contains($r->url(), '/v1/payments/' . $order->transaction_id . '/refunds'));
    }

    public function test_only_admins_can_use_the_screen(): void
    {
        Livewire::actingAs($this->student())->test(CourseEnrollments::class)->assertForbidden();
    }

    public function test_manual_enrollment_creates_access_without_an_order(): void
    {
        $student = $this->demoStudent();
        $course = $this->course2();
        Enrollment::where(['student_id' => $student->id, 'course_id' => $course->id])->delete();

        Livewire::actingAs($this->admin())->test(CourseEnrollments::class)
            ->set('manualOpen', true)->set('manualEmail', $student->email)->set('manualCourseId', $course->id)->call('enrollManually')->assertHasNoErrors();

        $this->assertTrue(Enrollment::where(['student_id' => $student->id, 'course_id' => $course->id])->exists());
    }

    public function test_manual_enrollment_validates_the_account_and_requires_a_date_for_in_person_courses(): void
    {
        Mail::fake();
        $student = $this->demoStudent();
        $course = $this->course2()->replicate();
        $course->title = 'Presencial manual';
        $course->slug = 'pm-' . uniqid();
        $course->delivery = 'in_person';
        $course->status = 'active';
        $course->save();
        $date = $course->sessions()->create(['starts_at' => now()->addDays(9), 'ends_at' => now()->addDays(9)->addHours(2), 'timezone' => 'America/Mexico_City', 'venue_name' => 'Sede', 'address' => 'Calle 1', 'city' => 'CDMX', 'status' => 'scheduled', 'sort' => 1]);

        $c = Livewire::actingAs($this->admin())->test(CourseEnrollments::class)->set('manualOpen', true);
        $c->set('manualEmail', 'nadie@no-existe.test')->set('manualCourseId', $course->id)->call('enrollManually')->assertHasErrors('manualEmail');
        $c->set('manualEmail', $this->tutor()->email)->call('enrollManually')->assertHasErrors('manualEmail');   // not a student
        $c->set('manualEmail', $student->email)->call('enrollManually')->assertHasErrors('manualSessionId');
        $this->assertFalse(Enrollment::where(['student_id' => $student->id, 'course_id' => $course->id])->exists());

        $c->set('manualSessionId', $date->id)->call('enrollManually')->assertHasNoErrors();
        $this->assertSame($date->id, Enrollment::where(['student_id' => $student->id, 'course_id' => $course->id])->first()->session_id);
        Mail::assertQueued(\App\Mail\OnsiteDateEmail::class, fn ($m) => $m->hasTo($student->email));
    }
}
