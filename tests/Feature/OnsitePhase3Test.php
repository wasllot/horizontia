<?php

namespace Tests\Feature;

use App\Jobs\CompletePurchaseJob;
use App\Livewire\Frontend\Checkout;
use App\Mail\OnsiteDateEmail;
use App\Models\CartItem;
use App\Models\Order;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Larabuild\Optionbuilder\Facades\Settings;
use Livewire\Livewire;
use Modules\Courses\Livewire\Pages\Course\CourseDetails;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\CourseSession;
use Modules\Courses\Models\Enrollment;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Cursos presenciales, fase 3: la compra guarda la fecha, control de cupo en el checkout, correo con .ics, gracias, Mis cursos y recordatorio. */
class OnsitePhase3Test extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.timezone' => 'America/Mexico_City', 'academy.private_mode' => true]);
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function onsite(array $dates = [['days' => 20], ['days' => 40, 'city' => 'Monterrey']], float $price = 0, string $delivery = 'in_person'): Course
    {
        $c = $this->course2()->replicate();
        $c->title = 'Taller Fase Tres';
        $c->slug = 'f3-' . uniqid();
        $c->delivery = $delivery;
        $c->status = 'active';
        $c->save();
        $c->pricing()->create(['price' => $price, 'discount' => 0, 'final_price' => $price]);
        foreach ($dates as $i => $d) {
            $c->sessions()->create(array_merge([
                'starts_at' => now()->addDays($d['days'] ?? 20), 'ends_at' => now()->addDays($d['days'] ?? 20)->addHours(4), 'timezone' => 'America/Mexico_City',
                'venue_name' => 'Sede ' . ($i + 1), 'address' => 'Calle ' . ($i + 1), 'city' => $d['city'] ?? 'Guadalajara', 'status' => 'scheduled', 'sort' => $i + 1,
            ], array_diff_key($d, ['days' => 1, 'city' => 1])));
        }

        return $c;
    }

    private function gateways(): void
    {
        Settings::set('admin_settings', 'payment_method', ['stripe' => ['status' => 'on', 'currency' => 'MXN', 'exchange_rate' => '', 'stripe_key' => 'pk_test_x', 'stripe_secret' => 'sk_test_x']]);
    }

    /** Puts the course with the chosen date in the student's cart exactly like the course page does. */
    private function addToCart(Course $course, CourseSession $date, $student = null): void
    {
        $student ??= $this->student();
        CartItem::where('user_id', $student->id)->delete();
        \Illuminate\Support\Facades\Cache::forget('cart_items_' . $student->id);
        Livewire::withQueryParams(['slug' => $course->slug])->actingAs($student)->test(CourseDetails::class)->call('selectSession', $date->id)->call('addToCart');
    }

    private function pay($student = null)
    {
        $student ??= $this->student();

        return Livewire::actingAs($student)->test(Checkout::class)
            ->set('form.firstName', 'Ana')->set('form.lastName', 'Lopez')->set('form.email', 'ana@example.com')
            ->call('updateInfo');
    }

    private function enrollmentOf($student, Course $course): ?Enrollment
    {
        return Enrollment::where(['student_id' => $student->id, 'course_id' => $course->id])->first();
    }

    // ------------------------------------------------------------ the purchase keeps the date

    public function test_a_free_in_person_purchase_stores_the_chosen_date_and_sends_the_confirmation_with_ics(): void
    {
        Mail::fake();
        $this->gateways();
        $student = $this->student();
        $course = $this->onsite();
        $date = $course->sessions()->skip(1)->first();
        Enrollment::where(['student_id' => $student->id, 'course_id' => $course->id])->delete();
        $this->addToCart($course, $date);

        $this->pay()->assertHasNoErrors();

        $this->assertSame($date->id, $this->enrollmentOf($student, $course)?->session_id);
        Mail::assertQueued(OnsiteDateEmail::class, function (OnsiteDateEmail $m) use ($student, $date) {
            $this->assertTrue($m->hasTo($student->email));
            $this->assertSame('confirmation', $m->kind);
            $this->assertSame($date->id, $m->session->id);
            $this->assertStringContainsString('Tu lugar está confirmado', $m->envelope()->subject);
            $html = $m->render();
            $this->assertStringContainsString('Monterrey', $html);
            $this->assertStringContainsString('Calle 2', $html);
            $this->assertCount(1, $m->attachments());

            return true;
        });
    }

    public function test_the_thanks_page_my_courses_and_the_classroom_show_the_date_with_a_calendar_link(): void
    {
        Mail::fake();
        $this->gateways();
        $student = $this->student();
        $course = $this->onsite();
        $date = $course->sessions()->first();
        Enrollment::where(['student_id' => $student->id, 'course_id' => $course->id])->delete();
        $this->addToCart($course, $date);
        $this->pay();
        $order = Order::where('user_id', $student->id)->latest('id')->firstOrFail();

        $this->actingAs($student)->get(route('thank-you', ['id' => $order->id]))->assertOk()
            ->assertSee('Calle 1')->assertSee(route('courses.onsite.ics', $date->id), false)->assertSee('Añadir al calendario');
        // Another account must not see the venue of somebody else's order.
        $this->actingAs($this->demoStudent())->get(route('thank-you', ['id' => $order->id]))->assertOk()->assertDontSee('Calle 1');

        Livewire::actingAs($student)->test(\Modules\Courses\Livewire\Pages\Student\CourseList\CourseList::class)->call('loadCoursesData')
            ->assertSee('Presencial')->assertSee('Sede 1')->assertSee('Ver mi fecha');
        $this->actingAs($student)->get(route('courses.course-taking', ['slug' => $course->slug]))->assertOk()->assertSee('Tu fecha presencial')->assertSee('Calle 1');
    }

    public function test_online_purchases_do_not_get_a_date_nor_the_onsite_email(): void
    {
        Mail::fake();
        $this->gateways();
        $student = $this->student();
        $course = $this->course2();
        $course->pricing()->update(['price' => 0, 'discount' => 0, 'final_price' => 0]);
        Enrollment::where(['student_id' => $student->id, 'course_id' => $course->id])->delete();
        CartItem::where('user_id', $student->id)->delete();
        \Illuminate\Support\Facades\Cache::forget('cart_items_' . $student->id);
        $this->actingAs($student, 'sanctum')->postJson('/api/course-cart', ['slug' => $course->slug])->assertOk();

        $this->pay();

        $this->assertNull($this->enrollmentOf($student, $course)?->session_id);
        Mail::assertNotQueued(OnsiteDateEmail::class);
    }

    // ------------------------------------------------------------ the server re-checks the date at checkout

    public function test_a_date_that_sells_out_cancels_or_closes_after_adding_to_the_cart_blocks_the_checkout(): void
    {
        Mail::fake();
        $this->gateways();
        $student = $this->student();
        $course = $this->onsite([['days' => 10, 'capacity' => 1], ['days' => 20], ['days' => 30]]);
        [$soldOut, $cancelled, $closed] = $course->sessions->all();
        Enrollment::where(['student_id' => $student->id, 'course_id' => $course->id])->delete();

        $this->addToCart($course, $soldOut);
        Enrollment::create(['student_id' => $this->demoStudent()->id, 'course_id' => $course->id, 'session_id' => $soldOut->id, 'tutor_id' => $course->instructor_id, 'status' => 'active']);
        $this->pay()->assertSee('se agotó');

        $this->addToCart($course, $cancelled);
        $cancelled->update(['status' => 'cancelled']);
        $this->pay()->assertSee('fue cancelada');

        $this->addToCart($course, $closed);
        $closed->update(['sales_close_at' => now()->subMinute()]);
        $this->pay()->assertSee('cerraron');

        $this->assertNull($this->enrollmentOf($student, $course));
        $this->assertSame(0, Order::where('user_id', $student->id)->where('status', 'complete')->count(), 'no order was completed');
        Mail::assertNotQueued(OnsiteDateEmail::class);
    }

    public function test_a_cart_line_without_a_valid_date_or_with_a_date_of_another_course_is_refused(): void
    {
        $this->gateways();
        $student = $this->student();
        $course = $this->onsite();
        $other = $this->onsite();
        Enrollment::where(['student_id' => $student->id, 'course_id' => $course->id])->delete();
        $service = new \Modules\Courses\Services\OnsiteSeatsService();

        $this->assertSame('missing', $service->check($course, null)[0]);
        $this->assertSame('invalid', $service->check($course, $other->sessions()->first()->id)[0], 'a date of another course (IDOR)');
        $this->assertSame('invalid', $service->check($course, 999999)[0]);
        $this->assertNull($service->check($course, $course->sessions()->first()->id));
        $this->assertNull($service->check($this->course2(), null), 'online courses need no date');
    }

    public function test_the_checkout_warns_about_an_unavailable_date_before_paying(): void
    {
        $this->gateways();
        $student = $this->student();
        $course = $this->onsite();
        $date = $course->sessions()->first();
        Enrollment::where(['student_id' => $student->id, 'course_id' => $course->id])->delete();
        $this->addToCart($course, $date);
        $date->update(['status' => 'cancelled']);

        $this->actingAs($student)->get('/finalizar-compra')->assertOk()->assertSee('data-testid="date-problem"', false)->assertSee('fue cancelada');
    }

    // ------------------------------------------------------------ the enrollment itself

    public function test_completing_an_order_ignores_a_date_that_belongs_to_another_course(): void
    {
        Mail::fake();
        $student = $this->student();
        $course = $this->onsite();
        $other = $this->onsite();
        Enrollment::where(['student_id' => $student->id, 'course_id' => $course->id])->delete();

        $order = $this->completedOrder($student, $course, $other->sessions()->first()->id);
        dispatch_sync(new CompletePurchaseJob($order));

        $this->assertNotNull($this->enrollmentOf($student, $course));
        $this->assertNull($this->enrollmentOf($student, $course)->session_id);
        Mail::assertNotQueued(OnsiteDateEmail::class);
    }

    public function test_a_repeated_payment_notice_does_not_send_the_confirmation_twice(): void
    {
        Mail::fake();
        $student = $this->student();
        $course = $this->onsite();
        $date = $course->sessions()->first();
        Enrollment::where(['student_id' => $student->id, 'course_id' => $course->id])->delete();
        $order = $this->completedOrder($student, $course, $date->id);

        dispatch_sync(new CompletePurchaseJob($order));
        dispatch_sync(new CompletePurchaseJob($order->fresh()));

        Mail::assertQueued(OnsiteDateEmail::class, 1);
        $this->assertSame(1, Enrollment::where(['student_id' => $student->id, 'course_id' => $course->id])->count());
    }

    public function test_two_payments_for_the_last_seat_confirm_both_and_alert_the_admins(): void
    {
        Mail::fake();
        $student = $this->student();
        $course = $this->onsite([['days' => 15, 'capacity' => 1]], price: 500);
        $date = $course->sessions()->first();
        Enrollment::where(['student_id' => $student->id, 'course_id' => $course->id])->delete();
        Enrollment::create(['student_id' => $this->demoStudent()->id, 'course_id' => $course->id, 'session_id' => $date->id, 'tutor_id' => $course->instructor_id, 'status' => 'active']);
        $admin = $this->admin();
        $before = $admin->notifications()->count();

        dispatch_sync(new CompletePurchaseJob($this->completedOrder($student, $course, $date->id)));

        $this->assertSame($date->id, $this->enrollmentOf($student, $course)->session_id, 'who already paid is confirmed');
        $this->assertTrue((new \Modules\Courses\Services\OnsiteSeatsService())->isOversold($date->fresh()));
        $this->assertGreaterThan($before, $admin->notifications()->count());
        $this->assertStringContainsString('superó su cupo', json_encode($admin->notifications()->latest()->first()->data, JSON_UNESCAPED_UNICODE));
    }

    private function completedOrder($student, Course $course, $sessionId): Order
    {
        $order = Order::create([
            'user_id' => $student->id, 'unique_payment_id' => (string) \Illuminate\Support\Str::uuid(), 'amount' => (float) $course->pricing->final_price, 'used_wallet_amt' => 0, 'currency' => 'MXN',
            'first_name' => 'Ana', 'last_name' => 'Lopez', 'company' => '', 'country' => 'MX', 'state' => 'CDMX', 'postal_code' => '01000', 'city' => 'CDMX', 'phone' => '5555555555',
            'email' => $student->email, 'payment_method' => 'stripe', 'status' => 'complete',
        ]);
        $order->items()->create([
            'title' => $course->title, 'quantity' => 1, 'price' => (float) $course->pricing->final_price, 'total' => (float) $course->pricing->final_price,
            'orderable_id' => $course->id, 'orderable_type' => Course::class, 'options' => ['session_id' => $sessionId, 'sub_category' => 'x'],
        ]);

        return $order->fresh();
    }

    // ------------------------------------------------------------ calendar and reminder

    public function test_the_ics_is_valid_and_only_for_the_buyer_the_instructor_and_admins(): void
    {
        $student = $this->student();
        $course = $this->onsite();
        $date = $course->sessions()->first();
        Enrollment::where(['student_id' => $student->id, 'course_id' => $course->id])->delete();
        Enrollment::create(['student_id' => $student->id, 'course_id' => $course->id, 'session_id' => $date->id, 'tutor_id' => $course->instructor_id, 'status' => 'active']);

        $res = $this->actingAs($student)->get(route('courses.onsite.ics', $date->id))->assertOk();
        $this->assertStringStartsWith('text/calendar', $res->headers->get('Content-Type'));
        $body = $res->getContent();
        foreach (['BEGIN:VCALENDAR', 'BEGIN:VEVENT', 'DTSTART:', 'DTEND:', 'LOCATION:Sede 1', 'END:VCALENDAR'] as $line) {
            $this->assertStringContainsString($line, $body);
        }

        $this->actingAs($this->demoStudent())->get(route('courses.onsite.ics', $date->id))->assertForbidden();
        $this->actingAs($this->admin())->get(route('courses.onsite.ics', $date->id))->assertOk();
        auth()->logout();
        $this->get(route('courses.onsite.ics', $date->id))->assertRedirect();
        $date->update(['status' => 'cancelled']);
        $this->actingAs($student)->get(route('courses.onsite.ics', $date->id))->assertNotFound();
    }

    public function test_the_reminder_goes_out_once_to_students_of_dates_starting_within_24_hours(): void
    {
        Mail::fake();
        $student = $this->student();
        $course = $this->onsite([['days' => 0], ['days' => 10]]);
        $soon = $course->sessions()->first();
        $soon->update(['starts_at' => now()->addHours(20), 'ends_at' => now()->addHours(24)]);
        $later = $course->sessions()->skip(1)->first();
        Enrollment::where(['student_id' => $student->id, 'course_id' => $course->id])->delete();
        $near = Enrollment::create(['student_id' => $student->id, 'course_id' => $course->id, 'session_id' => $soon->id, 'tutor_id' => $course->instructor_id, 'status' => 'active']);
        $far = Enrollment::create(['student_id' => $this->demoStudent()->id, 'course_id' => $course->id, 'session_id' => $later->id, 'tutor_id' => $course->instructor_id, 'status' => 'active']);

        $this->artisan('app:send-onsite-reminders')->assertSuccessful();
        $this->artisan('app:send-onsite-reminders')->assertSuccessful();

        Mail::assertQueued(OnsiteDateEmail::class, 1);
        Mail::assertQueued(OnsiteDateEmail::class, fn (OnsiteDateEmail $m) => $m->kind === 'reminder' && $m->hasTo($student->email));
        $this->assertNotNull($near->fresh()->reminded_at);
        $this->assertNull($far->fresh()->reminded_at);
    }
}
