<?php

namespace Tests\Feature;

use App\Livewire\Frontend\Checkout;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Larabuild\Optionbuilder\Facades\Settings;
use Livewire\Livewire;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Livewire\Pages\Course\CourseDetails;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * One-page checkout of the private academy: minimal fields, preselected gateway, server-side totals,
 * "Comprar ahora" from the course page, and the unchanged marketplace checkout when the mode is off.
 */
class OnePageCheckoutTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.private_mode' => true]);
    }

    private function gateways(array $enabled): void
    {
        $methods = [];
        foreach ($enabled as $name) {
            $methods[$name] = ['status' => 'on', 'currency' => 'MXN', 'exchange_rate' => '', 'stripe_key' => 'pk_test_x', 'stripe_secret' => 'sk_test_x',
                'mp_access_token' => 'TEST-token', 'mp_public_key' => 'TEST-key', 'mp_webhook_secret' => 'secret', 'enable_test_mode' => '', 'binary_mode' => ''];
        }
        Settings::set('admin_settings', 'payment_method', $methods);
    }

    private function filledCart(User $student): void
    {
        CartItem::where('user_id', $student->id)->delete();
        $this->actingAs($student, 'sanctum')->postJson('/api/course-cart', ['slug' => $this->course2()->slug])->assertOk();
    }

    public function test_checkout_renders_one_page_with_summary_trust_signals_and_no_address_fields(): void
    {
        $this->gateways(['stripe']);
        $student = $this->student();
        $this->filledCart($student);

        $this->actingAs($student)->get('/finalizar-compra')->assertOk()
            ->assertSee('Finalizar compra')->assertSee('Tus datos')->assertSee('Método de pago')->assertSee('Resumen del pedido')
            ->assertSee('Garantía de reembolso de ' . config('academy.refund_days') . ' días')->assertSee('Acceso inmediato')
            ->assertDontSee('placeholder="Zip', false)->assertDontSee('id="country"', false)->assertDontSee('Billing', false);
    }

    public function test_one_click_payment_needs_only_name_email_and_method_and_charges_the_server_total(): void
    {
        $this->gateways(['stripe']);
        $student = $this->student();
        $this->filledCart($student);

        $c = Livewire::actingAs($student)->test(Checkout::class)
            ->set('form.firstName', 'Ana')->set('form.lastName', 'Lopez')->set('form.email', 'ana@example.com')
            ->set('form.totalAmount', 0.01)   // tampered
            ->call('updateInfo')->assertHasNoErrors()->assertRedirect(route('payment.process', ['gateway' => 'stripe']));

        $order = Order::where('user_id', $student->id)->latest('id')->firstOrFail();
        $this->assertEquals(19.99, (float) $order->amount);
        $this->assertSame('ana@example.com', $order->email);
        $this->assertSame('pending', $order->status);
    }

    public function test_name_and_a_valid_email_are_still_required(): void
    {
        $this->gateways(['stripe']);
        $student = $this->student();
        $this->filledCart($student);

        Livewire::actingAs($student)->test(Checkout::class)
            ->set('form.firstName', '')->set('form.lastName', '')->set('form.email', 'no-es-correo')
            ->call('updateInfo')->assertHasErrors(['form.firstName', 'form.lastName', 'form.email']);

        $this->assertSame(0, Order::where('user_id', $student->id)->where('email', 'no-es-correo')->count());
    }

    public function test_mercado_pago_is_preselected_when_enabled_otherwise_the_first_enabled_gateway(): void
    {
        $student = $this->student();
        $this->filledCart($student);

        $this->gateways(['stripe', 'mercadopago']);
        Livewire::actingAs($student)->test(Checkout::class)->assertSet('form.paymentMethod', 'mercadopago');

        $this->gateways(['stripe']);
        Livewire::actingAs($student)->test(Checkout::class)->assertSet('form.paymentMethod', 'stripe');
    }

    public function test_pay_is_blocked_when_no_gateway_is_enabled_and_the_page_explains_it(): void
    {
        $this->gateways([]);
        $student = $this->student();
        $this->filledCart($student);

        $this->actingAs($student)->get('/finalizar-compra')->assertOk()->assertSee('no hay métodos de pago disponibles');
        Livewire::actingAs($student)->test(Checkout::class)
            ->set('form.firstName', 'Ana')->set('form.lastName', 'L')->set('form.email', 'ana@example.com')->call('updateInfo');
        $this->assertSame(0, Order::where('user_id', $student->id)->where('email', 'ana@example.com')->count());
    }

    public function test_empty_cart_shows_a_friendly_state_instead_of_a_broken_form(): void
    {
        $student = $this->student();
        CartItem::where('user_id', $student->id)->delete();
        \Illuminate\Support\Facades\Cache::forget('cart_items_' . $student->id);

        $this->actingAs($student)->get('/finalizar-compra')->assertOk()
            ->assertSee('Tu carrito está vacío')->assertSee('Ver cursos')->assertDontSee('Resumen del pedido');
    }

    public function test_removing_an_item_updates_the_totals_and_the_cart(): void
    {
        $this->gateways(['stripe']);
        $student = $this->student();
        $this->filledCart($student);
        $course = $this->course2();

        Livewire::actingAs($student)->test(Checkout::class)
            ->call('removeCart', $course->id, get_class($course))
            ->assertSee('Tu carrito está vacío')->assertDispatched('cart-updated');

        $this->assertSame(0, CartItem::where('user_id', $student->id)->count());
    }

    public function test_buy_now_from_the_course_page_adds_once_and_goes_to_checkout(): void
    {
        $student = $this->student();
        CartItem::where('user_id', $student->id)->delete();
        \Illuminate\Support\Facades\Cache::forget('cart_items_' . $student->id);
        $course = $this->course2();

        $component = Livewire::withQueryParams(['slug' => $course->slug])->actingAs($student)->test(CourseDetails::class);
        $component->call('buyNow')->assertRedirect(route('checkout'));
        $component->call('buyNow')->assertRedirect(route('checkout'));   // double click: no duplicate line

        $this->assertSame(1, CartItem::where('user_id', $student->id)->where('cartable_id', $course->id)->count());
    }

    public function test_buy_now_sends_guests_to_login_and_brings_them_back_to_the_course(): void
    {
        $course = $this->course2();

        Livewire::withQueryParams(['slug' => $course->slug])->test(CourseDetails::class)
            ->call('buyNow')->assertRedirect(route('login'));

        $this->assertSame(route('courses.course-detail', ['slug' => $course->slug]), session('url.intended'));
    }

    public function test_course_page_offers_buy_now_and_keeps_add_to_cart(): void
    {
        $student = $this->student();
        CartItem::where('user_id', $student->id)->delete();
        \Illuminate\Support\Facades\Cache::forget('cart_items_' . $student->id);

        $this->actingAs($student)->get('/curso/' . $this->course2()->slug)->assertOk()->assertSee('Comprar ahora')->assertSee('Agregar al carrito');
    }

    public function test_a_free_course_enrolls_the_student_at_once_without_a_queue_worker(): void
    {
        // Completing the order runs inline (dispatch_sync), so enrollment never depends on a queue worker being up.
        $this->gateways(['stripe']);
        $student = $this->student();
        $course = $this->course2();
        $course->pricing()->update(['price' => 0, 'discount' => 0, 'final_price' => 0]);
        Enrollment::where(['student_id' => $student->id, 'course_id' => $course->id])->delete();
        $this->filledCart($student);

        Livewire::actingAs($student)->test(Checkout::class)
            ->set('form.firstName', 'Ana')->set('form.lastName', 'Lopez')->set('form.email', 'ana@example.com')
            ->call('updateInfo')->assertHasNoErrors();

        $order = Order::where('user_id', $student->id)->latest('id')->firstOrFail();
        $this->assertSame('complete', $order->status);
        $this->assertTrue(Enrollment::where(['student_id' => $student->id, 'course_id' => $course->id])->exists(), 'the free course must be enrolled immediately');

        // …and it shows up in the student's courses and no longer offers to buy it.
        $this->actingAs($student)->get(route('courses.course-list'))->assertOk();
        Livewire::actingAs($student)->test(\Modules\Courses\Livewire\Pages\Student\CourseList\CourseList::class)->call('loadCoursesData')->assertSee($course->title);
        $this->actingAs($student)->get(route('courses.course-detail', ['slug' => $course->slug]))->assertOk()->assertDontSee('Comprar ahora');
    }

    public function test_marketplace_checkout_keeps_requiring_the_billing_address(): void
    {
        config(['academy.private_mode' => false]);
        $this->gateways(['stripe']);
        $student = $this->student();
        $this->filledCart($student);

        Livewire::actingAs($student)->test(Checkout::class)
            ->set('form.firstName', 'Ana')->set('form.lastName', 'L')->set('form.email', 'ana@example.com')
            ->set('form.paymentMethod', 'stripe')->set('form.country', '')->set('form.zipcode', '')
            ->call('updateInfo')->assertHasErrors(['form.country', 'form.zipcode']);
    }
}
