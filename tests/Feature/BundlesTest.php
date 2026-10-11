<?php

namespace Tests\Feature;

use App\Jobs\CompletePurchaseJob;
use App\Livewire\Frontend\Checkout;
use App\Models\CartItem;
use App\Models\Order;
use App\Services\OrderRefundService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Larabuild\Optionbuilder\Facades\Settings;
use Livewire\Livewire;
use Modules\Courses\Livewire\Pages\Admin\Bundles;
use Modules\Courses\Livewire\Pages\Bundle\BundleDetails;
use Modules\Courses\Models\Bundle;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Services\BundleService;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Paquetes: varios cursos a un precio; se compran como cursos sueltos con el descuento repartido. */
class BundlesTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.private_mode' => true, 'academy.timezone' => 'America/Mexico_City']);
        CartItem::where('user_id', $this->student()->id)->delete();
        Cache::forget('cart_items_' . $this->student()->id);
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function course(float $price, string $delivery = 'online', string $status = 'active'): Course
    {
        $c = $this->course2()->replicate();
        $c->title = 'Curso paquete ' . uniqid();
        $c->slug = 'cp-' . uniqid();
        $c->delivery = $delivery;
        $c->status = $status;
        $c->save();
        $c->pricing()->create(['price' => $price, 'discount' => 0, 'final_price' => $price]);

        return $c;
    }

    /** @return array{0: Bundle, 1: array<int, Course>} */
    private function bundle(array $prices = [1000, 500, 500], float $bundlePrice = 1500, string $status = 'active'): array
    {
        $courses = array_map(fn ($p) => $this->course($p), $prices);
        $bundle = Bundle::create(['title' => 'Ruta ' . uniqid(), 'slug' => 'ruta-' . uniqid(), 'price' => $bundlePrice, 'status' => $status]);
        $bundle->courses()->sync(collect($courses)->mapWithKeys(fn ($c, $i) => [$c->id => ['sort' => $i + 1]])->all());

        return [$bundle->fresh(), $courses];
    }

    private function lines(): \Illuminate\Support\Collection
    {
        return CartItem::where('user_id', $this->student()->id)->get();
    }

    private function details(Bundle $b, $user = null)
    {
        return Livewire::actingAs($user ?? $this->student())->test(BundleDetails::class, ['slug' => $b->slug]);
    }

    // ------------------------------------------------------------ pricing

    public function test_the_discount_is_spread_proportionally_and_the_total_is_exact(): void
    {
        [$b, $courses] = $this->bundle([1000, 500, 500], 1500);
        $svc = app(BundleService::class);

        $this->assertEquals(2000.0, $svc->listPrice($b));
        $this->assertSame(25, $svc->savingsPercent($b));
        $shares = $svc->shares($b, $b->courses);
        $this->assertEquals([750.0, 375.0, 375.0], array_values($shares));

        // Awkward numbers: thirds. The shares must still add up to the price to the cent.
        [$odd] = $this->bundle([333.33, 333.33, 333.34], 899.99);
        $this->assertEquals(899.99, round(array_sum($svc->shares($odd, $odd->courses)), 2));
    }

    public function test_courses_the_student_already_owns_are_left_out_with_the_same_discount_for_the_rest(): void
    {
        [$b, $courses] = $this->bundle([1000, 500, 500], 1500);
        Enrollment::updateOrCreate(['student_id' => $this->student()->id, 'course_id' => $courses[0]->id], ['tutor_id' => $courses[0]->instructor_id, 'status' => 'active']);
        $svc = app(BundleService::class);

        $remaining = $svc->remainingFor($this->student(), $b);
        $this->assertCount(2, $remaining);
        $this->assertEquals(750.0, round(array_sum($svc->shares($b, $remaining)), 2), '25% off the 1,000 that is left');
    }

    public function test_only_published_online_paid_courses_are_eligible_and_a_bundle_must_really_save(): void
    {
        $online = $this->course(500);
        $onsite = $this->course(500, 'in_person');
        $draft = $this->course(500, 'online', 'under_review');
        $free = $this->course(0);

        $ids = app(BundleService::class)->eligibleCourses()->pluck('id');

        $this->assertTrue($ids->contains($online->id));
        foreach ([$onsite, $draft, $free] as $c) {
            $this->assertFalse($ids->contains($c->id));
        }
        [$noSaving] = $this->bundle([500, 500], 1000);
        $this->assertFalse(app(BundleService::class)->isSellable($noSaving), 'same price as separate courses is not a bundle');
    }

    // ------------------------------------------------------------ cart

    public function test_buying_a_bundle_puts_each_course_in_the_cart_at_its_share_and_goes_to_checkout(): void
    {
        [$b, $courses] = $this->bundle();

        $this->details($b)->call('buyNow')->assertRedirect(route('checkout'));

        $lines = $this->lines();
        $this->assertCount(3, $lines);
        $this->assertEquals(1500.0, round((float) $lines->sum('price'), 2));
        $this->assertSame([$b->id], $lines->pluck('options.bundle_id')->unique()->values()->all());
        $this->assertEquals([750.0, 375.0, 375.0], $lines->sortBy('cartable_id')->pluck('price')->map(fn ($p) => (float) $p)->values()->all());
    }

    public function test_a_course_already_in_the_cart_at_full_price_is_replaced_by_its_bundle_price(): void
    {
        [$b, $courses] = $this->bundle();
        CartItem::create(['user_id' => $this->student()->id, 'cartable_type' => Course::class, 'cartable_id' => $courses[1]->id, 'name' => $courses[1]->title, 'qty' => 1, 'price' => 500, 'options' => ['id' => $courses[1]->id]]);
        Cache::forget('cart_items_' . $this->student()->id);

        $this->details($b)->call('addToCart');

        $this->assertCount(3, $this->lines());
        $this->assertEquals(375.0, (float) $this->lines()->firstWhere('cartable_id', $courses[1]->id)->price);
    }

    public function test_removing_one_course_of_a_bundle_removes_the_whole_bundle(): void
    {
        [$b, $courses] = $this->bundle();
        $this->details($b)->call('addToCart');
        $this->actingAs($this->student());

        \App\Facades\Cart::remove($courses[2]->id, Course::class);

        $this->assertCount(0, $this->lines());
    }

    public function test_guests_go_to_login_and_non_students_cannot_buy(): void
    {
        [$b] = $this->bundle();

        Livewire::test(BundleDetails::class, ['slug' => $b->slug])->call('buyNow')->assertRedirect(route('login'));
        $this->details($b, $this->tutor())->call('buyNow')->assertNoRedirect();
        $this->assertCount(0, $this->lines());
    }

    public function test_a_student_who_owns_everything_cannot_add_it(): void
    {
        [$b, $courses] = $this->bundle();
        foreach ($courses as $c) {
            Enrollment::updateOrCreate(['student_id' => $this->student()->id, 'course_id' => $c->id], ['tutor_id' => $c->instructor_id, 'status' => 'active']);
        }

        $this->details($b)->call('buyNow')->assertNoRedirect()->assertSee('Ir a mis cursos');
        $this->assertCount(0, $this->lines());
    }

    // ------------------------------------------------------------ checkout and purchase

    private function gateway(): void
    {
        Settings::set('admin_settings', 'payment_method', ['stripe' => ['status' => 'on', 'currency' => 'MXN', 'exchange_rate' => '', 'stripe_key' => 'pk_test_x', 'stripe_secret' => 'sk_test_x']]);
    }

    public function test_the_checkout_charges_the_bundle_price_and_flags_a_price_change(): void
    {
        $this->gateway();
        [$b] = $this->bundle();
        $this->details($b)->call('addToCart');

        $c = Livewire::actingAs($this->student())->test(Checkout::class)->set('form.firstName', 'Ana')->set('form.lastName', 'L')->set('form.email', 'a@b.test')->call('updateInfo');
        $order = Order::where('user_id', $this->student()->id)->latest('id')->firstOrFail();
        $this->assertEquals(1500.0, (float) $order->amount);
        $this->assertSame(3, $order->items()->count());

        // The admin changes the price while the cart is waiting: the buyer must review, not pay the old figure.
        $b->update(['price' => 1200]);
        $c = Livewire::actingAs($this->student())->test(Checkout::class)->set('form.firstName', 'Ana')->set('form.lastName', 'L')->set('form.email', 'a@b.test')->call('updateInfo');
        $c->assertNoRedirect();
        $this->assertEquals(1200.0, round((float) $this->lines()->sum('price'), 2), 'the cart was rebuilt at the new price');
    }

    public function test_a_paused_bundle_is_removed_from_the_cart_at_checkout(): void
    {
        $this->gateway();
        [$b] = $this->bundle();
        $this->details($b)->call('addToCart');
        $b->update(['status' => 'draft']);

        Livewire::actingAs($this->student())->test(Checkout::class)->set('form.firstName', 'Ana')->set('form.lastName', 'L')->set('form.email', 'a@b.test')->call('updateInfo')->assertNoRedirect();

        $this->assertCount(0, $this->lines());
        $this->assertSame(0, Order::where('user_id', $this->student()->id)->where('amount', 1500)->count());
    }

    public function test_paying_enrolls_in_every_course_and_a_refund_takes_them_all_back(): void
    {
        Mail::fake();
        [$b, $courses] = $this->bundle();
        foreach ($courses as $c) {
            Enrollment::where(['student_id' => $this->student()->id, 'course_id' => $c->id])->delete();
        }
        $this->details($b)->call('addToCart');
        $order = Order::create([
            'user_id' => $this->student()->id, 'unique_payment_id' => (string) Str::uuid(), 'amount' => 1500, 'used_wallet_amt' => 0, 'currency' => 'MXN', 'first_name' => 'Ana', 'last_name' => 'L',
            'company' => '', 'country' => 'MX', 'state' => 'X', 'postal_code' => '1', 'city' => 'X', 'phone' => '1', 'email' => $this->student()->email, 'payment_method' => 'stripe', 'status' => 'complete', 'transaction_id' => 'tx-b',
        ]);
        foreach ($this->lines() as $l) {
            $order->items()->create(['title' => $l->name, 'quantity' => 1, 'price' => $l->price, 'total' => $l->price, 'options' => $l->options, 'orderable_type' => Course::class, 'orderable_id' => $l->cartable_id]);
        }

        dispatch_sync(new CompletePurchaseJob($order->fresh()));

        foreach ($courses as $c) {
            $this->assertTrue(Enrollment::where(['student_id' => $this->student()->id, 'course_id' => $c->id])->exists());
        }
        Mail::assertQueued(\App\Mail\PurchaseConfirmedEmail::class, 1);

        app(OrderRefundService::class)->revoke($order->fresh());
        $this->assertSame(0, Enrollment::where('student_id', $this->student()->id)->whereIn('course_id', collect($courses)->pluck('id'))->count());
    }

    // ------------------------------------------------------------ public pages

    public function test_the_listing_shows_only_sellable_published_bundles_and_drafts_are_hidden(): void
    {
        [$live] = $this->bundle();
        [$draft] = $this->bundle([800, 800], 1000, 'draft');

        $this->get(route('courses.bundles.index'))->assertOk()->assertSee($live->title)->assertDontSee($draft->title)->assertSee('data-testid="bundle-card"', false);
        $this->get(route('courses.bundles.show', ['slug' => $draft->slug]))->assertNotFound();
        $this->actingAs($this->admin())->get(route('courses.bundles.show', ['slug' => $draft->slug]))->assertOk()->assertSee('Borrador');
    }

    public function test_the_detail_page_shows_the_courses_the_price_and_the_saving(): void
    {
        [$b, $courses] = $this->bundle();

        $html = $this->actingAs($this->student())->get(route('courses.bundles.show', ['slug' => $b->slug]))->assertOk()->getContent();

        foreach ($courses as $c) {
            $this->assertStringContainsString(html_entity_decode($c->title), html_entity_decode($html));
        }
        $this->assertStringContainsString('Ahorras 25%', $html);
        $this->assertStringContainsString('data-testid="bundle-buy"', $html);
    }

    // ------------------------------------------------------------ admin

    public function test_the_admin_creates_validates_publishes_and_deletes_bundles(): void
    {
        $a = $this->course(1000);
        $b = $this->course(500);
        $admin = Livewire::actingAs($this->admin());

        $c = $admin->test(Bundles::class)->call('create')->set('title', 'Mi paquete')->set('price', '1200')->set('courseIds', [(string) $a->id]);
        $c->call('save')->assertHasErrors('courseIds');
        $c->set('courseIds', [(string) $a->id, (string) $b->id])->set('price', '1500')->call('save')->assertHasErrors('price');   // not cheaper than 1,500
        $c->set('price', '1200')->call('save')->assertHasNoErrors();

        $bundle = Bundle::where('title', 'Mi paquete')->firstOrFail();
        $this->assertSame('draft', $bundle->status);
        $this->assertCount(2, $bundle->courses);
        $this->assertSame('mi-paquete', $bundle->slug);

        Livewire::actingAs($this->admin())->test(Bundles::class)->call('toggleStatus', $bundle->id);
        $this->assertSame('active', $bundle->fresh()->status);
        Livewire::actingAs($this->admin())->test(Bundles::class)->call('delete', $bundle->id);
        $this->assertNull(Bundle::find($bundle->id));
        $this->assertNotNull(Course::find($a->id), 'the courses are untouched');
    }

    public function test_only_admins_reach_the_bundles_screen(): void
    {
        Livewire::actingAs($this->student())->test(Bundles::class)->assertForbidden();
        $this->actingAs($this->admin())->get(route('courses.admin.bundles'))->assertOk()->assertSee('Paquetes');
    }

    public function test_the_course_page_offers_the_bundle_it_belongs_to(): void
    {
        [$b, $courses] = $this->bundle();

        $this->actingAs($this->student())->get(route('courses.course-detail', ['slug' => $courses[0]->slug]))->assertOk()
            ->assertSee('data-testid="course-bundle-hint"', false)->assertSee('Ahorra 25%')->assertSee(route('courses.bundles.show', ['slug' => $b->slug]), false);

        $b->update(['status' => 'draft']);
        $this->actingAs($this->student())->get(route('courses.course-detail', ['slug' => $courses[0]->slug]))->assertOk()->assertDontSee('course-bundle-hint');
    }
}
