<?php

namespace Tests\Feature;

use App\Jobs\CompletePurchaseJob;
use App\Livewire\Frontend\Checkout;
use App\Livewire\Pages\Admin\Coupons\Coupons;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Order;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Larabuild\Optionbuilder\Facades\Settings;
use Livewire\Livewire;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Enrollment;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Cupones: se validan en el servidor, descuentan solo cursos sueltos y se cuentan al pagarse el pedido. */
class CouponsTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        $this->app['cache']->setDefaultDriver('array');
        config(['academy.private_mode' => true]);
        CartItem::where('user_id', $this->student()->id)->delete();
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function course(float $price): Course
    {
        $c = $this->course2()->replicate();
        $c->title = 'Curso cupón ' . uniqid();
        $c->slug = 'cc-' . uniqid();
        $c->delivery = 'online';
        $c->status = 'active';
        $c->save();
        $c->pricing()->create(['price' => $price, 'discount' => 0, 'final_price' => $price]);

        return $c;
    }

    private function inCart(Course $course, float $price, array $options = []): void
    {
        CartItem::create(['user_id' => $this->student()->id, 'cartable_id' => $course->id, 'cartable_type' => Course::class, 'name' => $course->title, 'qty' => 1, 'price' => $price, 'options' => $options + ['price' => $price]]);
        Cache::forget('cart_items_' . $this->student()->id);
        $this->app->forgetInstance('cart');
        \App\Facades\Cart::clearResolvedInstance('cart');
    }

    private function coupon(array $over = []): Coupon
    {
        return Coupon::create($over + ['code' => 'T' . strtoupper(Str::random(7)), 'type' => 'percent', 'value' => 20, 'per_user_limit' => 1, 'active' => true, 'used_count' => 0])->fresh();
    }

    private function quote(Coupon $c, array $lines): array
    {
        return app(CouponService::class)->quote($c, $this->student(), $lines);
    }

    private function line(Course $course, float $price, array $options = []): array
    {
        return ['cartable_id' => $course->id, 'cartable_type' => Course::class, 'qty' => 1, 'price' => $price, 'options' => $options];
    }

    // ------------------------------------------------------------ reglas

    public function test_percent_and_fixed_discounts_are_computed_and_never_exceed_the_price(): void
    {
        $a = $this->course(1000);
        $q = $this->quote($this->coupon(['value' => 25]), [$this->line($a, 1000)]);
        $this->assertTrue($q['ok']);
        $this->assertEquals(250.0, $q['discount']);

        $this->assertEquals(300.0, $this->quote($this->coupon(['type' => 'fixed', 'value' => 300]), [$this->line($a, 1000)])['discount']);
        $this->assertEquals(1000.0, $this->quote($this->coupon(['type' => 'fixed', 'value' => 5000]), [$this->line($a, 1000)])['discount'], 'a fixed coupon cannot go below zero');
    }

    public function test_the_discount_is_spread_over_the_eligible_lines_and_adds_up_exactly(): void
    {
        [$a, $b, $c] = [$this->course(100), $this->course(100), $this->course(100)];
        $q = $this->quote($this->coupon(['type' => 'fixed', 'value' => 100]), [$this->line($a, 100), $this->line($b, 100), $this->line($c, 100)]);

        $this->assertEquals(100.0, round(array_sum($q['shares']), 2));
        $this->assertCount(3, $q['shares']);
    }

    public function test_invalid_codes_explain_why(): void
    {
        $a = $this->course(1000);
        $lines = [$this->line($a, 1000)];
        $cases = [
            ['active' => false, 'ya no está activo'],
            ['starts_at' => now()->addDay(), 'todavía no está vigente'],
            ['ends_at' => now()->subDay(), 'ya venció'],
            ['max_uses' => 2, 'used_count' => 2, 'límite de usos'],
            ['min_amount' => 5000, 'compra mínima'],
        ];
        foreach ($cases as $case) {
            $msg = array_pop($case);
            $q = $this->quote($this->coupon($case), $lines);
            $this->assertFalse($q['ok']);
            $this->assertStringContainsString($msg, $q['message']);
        }
    }

    public function test_a_coupon_for_specific_courses_ignores_the_rest_and_bundle_lines_never_get_a_discount(): void
    {
        [$a, $b] = [$this->course(1000), $this->course(1000)];
        $only = $this->coupon(['course_ids' => [$a->id]]);

        $this->assertEquals(200.0, $this->quote($only, [$this->line($a, 1000), $this->line($b, 1000)])['discount']);
        $this->assertFalse($this->quote($only, [$this->line($b, 1000)])['ok']);
        $this->assertFalse($this->quote($this->coupon(), [$this->line($a, 1000, ['bundle_id' => 5])])['ok'], 'bundle courses already carry their own discount');
    }

    public function test_per_person_limit_and_first_purchase_only(): void
    {
        $a = $this->course(1000);
        $c = $this->coupon();
        Order::create(['user_id' => $this->student()->id, 'unique_payment_id' => (string) Str::uuid(), 'amount' => 10, 'used_wallet_amt' => 0, 'currency' => 'MXN', 'first_name' => 'A', 'last_name' => 'B', 'company' => '', 'country' => 'MX', 'state' => 'X', 'postal_code' => '1', 'city' => 'X', 'phone' => '1', 'email' => 'a@b.test', 'payment_method' => 'stripe', 'status' => 'complete', 'coupon_code' => $c->code, 'discount_amount' => 50]);
        $order = Order::where('coupon_code', $c->code)->first();

        $this->assertTrue($this->quote($c, [$this->line($a, 1000)])['ok']);
        app(CouponService::class)->redeem($order);
        $this->assertFalse($this->quote($c, [$this->line($a, 1000)])['ok'], 'one use per person');
        $this->assertFalse($this->quote($this->coupon(['first_purchase_only' => true]), [$this->line($a, 1000)])['ok'], 'the student already has a paid order');
    }

    public function test_redeeming_counts_once_per_order(): void
    {
        $c = $this->coupon();
        $order = Order::create(['user_id' => $this->student()->id, 'unique_payment_id' => (string) Str::uuid(), 'amount' => 10, 'used_wallet_amt' => 0, 'currency' => 'MXN', 'first_name' => 'A', 'last_name' => 'B', 'company' => '', 'country' => 'MX', 'state' => 'X', 'postal_code' => '1', 'city' => 'X', 'phone' => '1', 'email' => 'a@b.test', 'payment_method' => 'stripe', 'status' => 'complete', 'coupon_code' => $c->code, 'discount_amount' => 50]);

        app(CouponService::class)->redeem($order);
        app(CouponService::class)->redeem($order->fresh());

        $this->assertSame(1, $c->fresh()->used_count);
        $this->assertSame(1, $c->redemptions()->count());
    }

    // ------------------------------------------------------------ checkout

    private function gateway(): void
    {
        Settings::set('admin_settings', 'payment_method', ['stripe' => ['status' => 'on', 'currency' => 'MXN', 'exchange_rate' => '', 'stripe_key' => 'pk_test_x', 'stripe_secret' => 'sk_test_x']]);
    }

    private function checkout()
    {
        return Livewire::actingAs($this->student())->test(Checkout::class);
    }

    public function test_the_buyer_applies_a_code_and_the_order_is_charged_the_discounted_total(): void
    {
        $this->gateway();
        $a = $this->course(1000);
        $this->inCart($a, 1000);
        $c = $this->coupon(['code' => 'BIENVENIDA20', 'value' => 20]);

        $page = $this->checkout()->set('coupon', ' bienvenida20 ')->call('applyCoupon');
        $page->assertSee('BIENVENIDA20')->assertSee('data-testid="co-coupon-applied"', false);

        $page->set('form.firstName', 'Ana')->set('form.lastName', 'L')->set('form.email', 'a@b.test')->call('updateInfo');
        $order = Order::where('user_id', $this->student()->id)->latest('id')->firstOrFail();
        $this->assertEquals(800.0, (float) $order->amount);
        $this->assertSame('BIENVENIDA20', $order->coupon_code);
        $this->assertEquals(200.0, (float) $order->discount_amount);
        $this->assertEquals(800.0, (float) $order->items()->sum('total'));
        $this->assertSame(0, $c->fresh()->used_count, 'it only counts when the order is paid');
    }

    public function test_a_wrong_code_shows_an_error_and_charges_the_full_price(): void
    {
        $this->gateway();
        $this->inCart($this->course(1000), 1000);

        $this->checkout()->set('coupon', 'NOEXISTE')->call('applyCoupon')->assertDispatched('showAlertMessage');
        $this->assertEquals(1000.0, \App\Facades\Cart::total());
    }

    public function test_the_code_stops_applying_when_it_is_paused_after_being_added(): void
    {
        $this->gateway();
        $this->inCart($this->course(1000), 1000);
        $c = $this->coupon(['value' => 10]);
        $page = $this->checkout()->set('coupon', $c->code)->call('applyCoupon');
        $this->assertEquals(900.0, \App\Facades\Cart::total());

        $c->update(['active' => false]);
        $page->set('form.firstName', 'Ana')->set('form.lastName', 'L')->set('form.email', 'a@b.test')->call('updateInfo');

        $order = Order::where('user_id', $this->student()->id)->latest('id')->firstOrFail();
        $this->assertEquals(1000.0, (float) $order->amount, 'the server revalidates: a paused code does not discount');
        $this->assertNull($order->coupon_code);
    }

    public function test_a_hundred_percent_code_completes_the_order_and_enrolls_for_free(): void
    {
        Mail::fake();
        $this->gateway();
        $a = $this->course(500);
        Enrollment::where(['student_id' => $this->student()->id, 'course_id' => $a->id])->delete();
        $this->inCart($a, 500);
        $c = $this->coupon(['value' => 100]);

        $this->checkout()->set('coupon', $c->code)->call('applyCoupon')
            ->set('form.firstName', 'Ana')->set('form.lastName', 'L')->set('form.email', 'a@b.test')->call('updateInfo');

        $order = Order::where('user_id', $this->student()->id)->latest('id')->firstOrFail();
        $this->assertSame('complete', (string) $order->status);
        $this->assertTrue(Enrollment::where(['student_id' => $this->student()->id, 'course_id' => $a->id])->exists());
        $this->assertSame(1, $c->fresh()->used_count);
    }

    public function test_paying_the_order_redeems_the_code_once_even_if_the_webhook_repeats(): void
    {
        Mail::fake();
        $c = $this->coupon();
        $a = $this->course(1000);
        $order = Order::create(['user_id' => $this->student()->id, 'unique_payment_id' => (string) Str::uuid(), 'amount' => 800, 'used_wallet_amt' => 0, 'currency' => 'MXN', 'first_name' => 'A', 'last_name' => 'B', 'company' => '', 'country' => 'MX', 'state' => 'X', 'postal_code' => '1', 'city' => 'X', 'phone' => '1', 'email' => 'a@b.test', 'payment_method' => 'stripe', 'status' => 'complete', 'coupon_code' => $c->code, 'discount_amount' => 200]);
        $order->items()->create(['title' => $a->title, 'quantity' => 1, 'price' => 1000, 'total' => 800, 'options' => [], 'orderable_type' => Course::class, 'orderable_id' => $a->id]);

        dispatch_sync(new CompletePurchaseJob($order->fresh()));
        dispatch_sync(new CompletePurchaseJob($order->fresh()));

        $this->assertSame(1, $c->fresh()->used_count);
    }

    // ------------------------------------------------------------ administración

    public function test_the_admin_creates_edits_pauses_and_deletes_coupons(): void
    {
        $admin = Livewire::actingAs($this->admin());
        $page = $admin->test(Coupons::class)->call('create')
            ->set('code', 'verano 15!')->set('type', 'percent')->set('value', '15')->set('perUserLimit', 1)->call('save')->assertHasNoErrors();
        $c = Coupon::where('code', 'VERANO15')->firstOrFail();
        $this->assertEquals(15.0, $c->value);
        $this->assertNull($c->course_ids);

        $page->call('edit', $c->id)->set('value', '25')->set('maxUses', '10')->call('save')->assertHasNoErrors();
        $this->assertEquals(25.0, $c->fresh()->value);
        $this->assertSame(10, $c->fresh()->max_uses);

        $page->call('toggle', $c->id);
        $this->assertFalse($c->fresh()->active);
        $page->call('delete', $c->id);
        $this->assertNull(Coupon::find($c->id));
    }

    public function test_the_admin_form_validates(): void
    {
        $this->coupon(['code' => 'REPETIDO']);
        $page = Livewire::actingAs($this->admin())->test(Coupons::class)->call('create');

        $page->set('code', 'REPETIDO')->set('value', '10')->call('save')->assertHasErrors('code');
        $page->set('code', 'OTRO1')->set('value', '150')->call('save')->assertHasErrors('value');
        $page->set('value', '10')->set('scope', 'courses')->call('save')->assertHasErrors('courseIds');
        $page->set('scope', 'all')->set('startsAt', '2026-12-10')->set('endsAt', '2026-12-01')->call('save')->assertHasErrors('endsAt');
    }

    public function test_only_admins_reach_the_coupons_screen(): void
    {
        $this->assertNotSame(200, $this->actingAs($this->student())->get(route('admin.coupons'))->getStatusCode());
        $this->actingAs($this->admin())->get(route('admin.coupons'))->assertOk()->assertSee('Cupones');
        Livewire::actingAs($this->student())->test(Coupons::class)->assertForbidden();
    }
}
