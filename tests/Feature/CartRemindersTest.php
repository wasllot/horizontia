<?php

namespace Tests\Feature;

use App\Mail\CartReminderEmail;
use App\Models\CartItem;
use App\Models\Order;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Enrollment;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Recordatorios de carrito abandonado: dos como máximo, sin molestar a quien ya compró, está pagando en OXXO o se dio de baja. */
class CartRemindersTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.timezone' => 'America/Mexico_City']);
        Mail::fake();
        CartItem::query()->delete();
        Enrollment::where('student_id', $this->student()->id)->delete();
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function cartLine(?Course $course = null, int $hoursAgo = 5, array $options = []): CartItem
    {
        $course ??= $this->course2();
        $line = CartItem::create(['user_id' => $this->student()->id, 'cartable_type' => Course::class, 'cartable_id' => $course->id, 'name' => $course->title, 'qty' => 1, 'price' => 499, 'options' => $options]);
        DB::table('cart_items')->where('id', $line->id)->update(['updated_at' => now()->subHours($hoursAgo)]);

        return $line->fresh();
    }

    private function run_(): void
    {
        $this->artisan('app:send-cart-reminders')->assertSuccessful();
    }

    public function test_a_cart_idle_for_three_hours_gets_one_reminder_and_a_second_two_days_later_and_no_more(): void
    {
        $line = $this->cartLine(null, 5);

        $this->run_();
        Mail::assertQueued(CartReminderEmail::class, fn ($m) => $m->hasTo($this->student()->email) && $m->stage === 1);

        $this->run_();   // nothing new an hour later
        Mail::assertQueued(CartReminderEmail::class, 1);

        DB::table('cart_items')->where('id', $line->id)->update(['reminded_at' => now()->subHours(49)]);
        $this->run_();
        Mail::assertQueued(CartReminderEmail::class, fn ($m) => $m->stage === 2);

        DB::table('cart_items')->where('id', $line->id)->update(['reminded_at' => now()->subDays(10)]);
        $this->run_();
        Mail::assertQueued(CartReminderEmail::class, 2);   // never a third
    }

    public function test_recent_and_stale_carts_are_left_alone(): void
    {
        $line = $this->cartLine(null, 1);
        $this->run_();
        Mail::assertNothingQueued();

        DB::table('cart_items')->where('id', $line->id)->update(['updated_at' => now()->subDays(9)]);
        $this->run_();
        Mail::assertNothingQueued();
    }

    public function test_it_skips_owned_courses_unpublished_courses_and_unavailable_dates(): void
    {
        $owned = $this->course2();
        Enrollment::create(['student_id' => $this->student()->id, 'course_id' => $owned->id, 'tutor_id' => $owned->instructor_id, 'status' => 'active']);
        $this->cartLine($owned);
        $this->run_();
        Mail::assertNothingQueued();

        CartItem::query()->delete();
        $draft = $this->course2()->replicate();
        $draft->title = 'Borrador ' . uniqid();
        $draft->slug = 'borrador-' . uniqid();
        $draft->status = 'under_review';
        $draft->save();
        $this->cartLine($draft);
        $this->run_();
        Mail::assertNothingQueued();

        CartItem::query()->delete();
        $event = $this->course2()->replicate();
        $event->title = 'Evento ' . uniqid();
        $event->slug = 'evento-' . uniqid();
        $event->delivery = 'in_person';
        $event->status = 'active';
        $event->save();
        $date = $event->sessions()->create(['starts_at' => now()->addDays(9), 'ends_at' => now()->addDays(9)->addHours(2), 'timezone' => 'America/Mexico_City', 'venue_name' => 'S', 'address' => 'A', 'city' => 'CDMX', 'status' => 'cancelled', 'sort' => 1]);
        $this->cartLine($event, 5, ['session_id' => $date->id]);
        $this->run_();
        Mail::assertNothingQueued();
    }

    public function test_someone_waiting_for_an_oxxo_payment_is_not_reminded(): void
    {
        $this->cartLine();
        Order::create([
            'user_id' => $this->student()->id, 'unique_payment_id' => (string) \Illuminate\Support\Str::uuid(), 'amount' => 499, 'used_wallet_amt' => 0, 'currency' => 'MXN', 'first_name' => 'A', 'last_name' => 'B',
            'company' => '', 'country' => 'MX', 'state' => 'X', 'postal_code' => '1', 'city' => 'X', 'phone' => '1', 'email' => 'a@b.test', 'payment_method' => 'mercadopago', 'status' => 'pending',
        ]);

        $this->run_();

        Mail::assertNothingQueued();
    }

    public function test_the_signed_unsubscribe_link_stops_reminders_and_unsigned_links_do_not_work(): void
    {
        $this->cartLine();
        $user = $this->student();

        $this->get(route('cart-reminders.off', ['user' => $user->id]))->assertForbidden();
        $this->assertNull($user->fresh()->cart_reminders_off_at);

        $this->get(URL::signedRoute('cart-reminders.off', ['user' => $user->id]))->assertOk()->assertSee('no te enviaremos más recordatorios');
        $this->assertNotNull($user->fresh()->cart_reminders_off_at);

        $this->run_();
        Mail::assertNothingQueued();
    }

    public function test_the_email_has_the_course_the_price_the_checkout_link_and_the_opt_out(): void
    {
        $this->cartLine(null, 5, ['session_label' => 'sábado 12 de diciembre · Monterrey']);
        $this->run_();

        Mail::assertQueued(CartReminderEmail::class, function (CartReminderEmail $m) {
            $html = $m->render();
            $this->assertStringContainsString(route('checkout'), $html);
            $this->assertStringContainsString('No quiero recibir estos recordatorios', $html);
            $this->assertStringContainsString('Monterrey', $html);
            $this->assertStringContainsString('signature=', $m->unsubscribeUrl);
            $this->assertArrayHasKey('List-Unsubscribe', $m->headers()->text);

            return true;
        });
    }

    public function test_the_feature_can_be_switched_off(): void
    {
        $this->cartLine();
        config(['academy.cart_reminders' => false]);

        $this->run_();
        Mail::assertNothingQueued();
    }
}
