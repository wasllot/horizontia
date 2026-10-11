<?php

namespace Tests\Feature;

use App\Jobs\CompletePurchaseJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Larabuild\Optionbuilder\Facades\Settings;
use Modules\Courses\Jobs\ClearCourseFundsJob;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Enrollment;
use Modules\LaraPayease\Facades\PaymentDriver;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * Mercado Pago Checkout Pro gateway. No real network: every call to
 * api.mercadopago.com goes through Http::fake().
 */
class MercadoPagoGatewayTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    private const SECRET = 'test-webhook-secret';
    private const AMOUNT = 499.00;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        $this->configureGateway();
        // The sync queue ignores ->delay(), which would run the funds-clearing job before the funds exist.
        Bus::fake([ClearCourseFundsJob::class]);
    }

    private function configureGateway(array $override = []): void
    {
        $methods = setting('admin_settings.payment_method') ?: [];
        $methods['mercadopago'] = array_merge([
            'status'            => 'on',
            'currency'          => 'MXN',
            'exchange_rate'     => '',
            'enable_test_mode'  => '',
            'binary_mode'       => '',
            'mp_access_token'   => 'TEST-access-token',
            'mp_public_key'     => 'TEST-public-key',
            'mp_webhook_secret' => self::SECRET,
        ], $override);
        Settings::set('admin_settings', 'payment_method', $methods);
    }

    private function makeOrder(?User $user = null, ?Course $course = null, float $amount = self::AMOUNT): Order
    {
        $user ??= $this->student();
        $course ??= $this->course2();
        Enrollment::where('student_id', $user->id)->where('course_id', $course->id)->delete();

        $order = Order::create([
            'user_id'           => $user->id,
            'unique_payment_id' => (string) Str::uuid(),
            'amount'            => $amount,
            'used_wallet_amt'   => 0,
            'currency'          => 'MXN',
            'first_name'        => 'QA',
            'last_name'         => 'Student',
            'company'           => '',
            'country'           => 'MX',
            'state'             => 'CDMX',
            'postal_code'       => '01000',
            'city'              => 'CDMX',
            'phone'             => '5555555555',
            'email'             => $user->email,
            'payment_method'    => 'mercadopago',
            'status'            => 'pending',
        ]);
        OrderItem::create([
            'order_id'       => $order->id,
            'title'          => $course->title,
            'quantity'       => 1,
            'options'        => [],
            'price'          => $amount,
            'total'          => $amount,
            'orderable_type' => Course::class,
            'orderable_id'   => $course->id,
        ]);

        return $order;
    }

    private function payment(Order $order, array $override = []): array
    {
        return array_merge([
            'id'                 => 987654321,
            'status'             => 'approved',
            'external_reference' => (string) $order->id,
            'transaction_amount' => self::AMOUNT,
            'currency_id'        => 'MXN',
        ], $override);
    }

    private function fakePayment(array $payment): void
    {
        // Fresh factory so a later call replaces the previous stub instead of queueing behind it.
        Http::swap(new HttpFactory());
        Http::fake(['api.mercadopago.com/v1/payments/*' => Http::response($payment, 200)]);
    }

    private function signedHeaders(string $dataId, string $secret = self::SECRET): array
    {
        $ts = (string) time();
        $requestId = 'req-' . Str::random(8);
        $v1 = hash_hmac('sha256', "id:" . strtolower($dataId) . ";request-id:{$requestId};ts:{$ts};", $secret);

        return ['x-signature' => "ts={$ts},v1={$v1}", 'x-request-id' => $requestId];
    }

    private function sendWebhook(string|int $paymentId, ?array $headers = null)
    {
        $headers ??= $this->signedHeaders((string) $paymentId);

        return $this->withHeaders($headers)->postJson(
            '/mercadopago/webhook?type=payment&data.id=' . $paymentId,
            ['action' => 'payment.updated', 'type' => 'payment', 'data' => ['id' => (string) $paymentId]]
        );
    }

    private function enrollments(Order $order): int
    {
        return Enrollment::where('student_id', $order->user_id)->where('course_id', $this->course2()->id)->count();
    }

    public function test_gateway_is_registered_in_supported_gateways(): void
    {
        $gateways = PaymentDriver::supportedGateways();

        $this->assertArrayHasKey('mercadopago', $gateways);
        $this->assertSame(['mp_access_token', 'mp_public_key', 'mp_webhook_secret'], array_keys($gateways['mercadopago']['keys']));
        $this->assertSame('off', $gateways['mercadopago']['status']);
        $this->assertSame('MXN', $gateways['mercadopago']['currency']);
        $this->assertContains('MXN', PaymentDriver::supportedCurrencies()['mercadopago']);
        $this->assertSame('mercadopago.return', PaymentDriver::getIpnUrl('mercadopago'));
        $this->assertNotEmpty(route('mercadopago.return', ['payment_method' => 'mercadopago']));
        $this->assertSame('mercadopago', getGatewayObject('mercadopago')->driverName());
    }

    public function test_preference_uses_order_data_and_ignores_request_values(): void
    {
        Http::fake(['api.mercadopago.com/checkout/preferences' => Http::response([
            'id' => 'pref-1',
            'init_point' => 'https://www.mercadopago.com.mx/checkout/v1/redirect?pref_id=pref-1',
            'sandbox_init_point' => 'https://sandbox.mercadopago.com.mx/checkout/v1/redirect?pref_id=pref-1',
        ], 201)]);

        $order = $this->makeOrder();

        // Session data and query string try to override amount/currency/order.
        $response = $this->actingAs($this->student())
            ->withSession(['payment_data' => ['order_id' => $order->id, 'amount' => 1, 'currency' => 'USD']])
            ->get('/mercadopago/procesar-pago?amount=1&currency=USD&order_id=999999&unit_price=1');

        $response->assertRedirect('https://www.mercadopago.com.mx/checkout/v1/redirect?pref_id=pref-1');

        Http::assertSent(function (ClientRequest $request) use ($order) {
            $item = $request['items'][0] ?? [];

            return $request->method() === 'POST'
                && $request->url() === 'https://api.mercadopago.com/checkout/preferences'
                && $request->hasHeader('Authorization', 'Bearer TEST-access-token')
                && $request->hasHeader('X-Idempotency-Key')
                && $request['external_reference'] === (string) $order->id
                && $item['unit_price'] == self::AMOUNT
                && $item['currency_id'] === 'MXN'
                && $item['quantity'] === 1
                && $item['title'] === $this->course2()->title
                && $request['payer']['email'] === $this->student()->email
                && $request['auto_return'] === 'approved'
                && $request['binary_mode'] === false
                && $request['expires'] === true
                && !empty($request['expiration_date_to'])
                && str_starts_with($request['notification_url'], 'https://')
                && str_ends_with($request['notification_url'], '/mercadopago/webhook')
                && $request['back_urls']['success'] === route('mercadopago.return', ['order' => $order->id]);
        });
    }

    public function test_test_mode_redirects_to_sandbox_init_point_and_binary_mode_is_sent(): void
    {
        $this->configureGateway(['enable_test_mode' => '1', 'binary_mode' => '1']);
        Http::fake(['api.mercadopago.com/checkout/preferences' => Http::response([
            'init_point' => 'https://live.example/pay',
            'sandbox_init_point' => 'https://sandbox.example/pay',
        ], 201)]);

        $order = $this->makeOrder();
        $this->actingAs($this->student())
            ->withSession(['payment_data' => ['order_id' => $order->id]])
            ->get('/mercadopago/procesar-pago')
            ->assertRedirect('https://sandbox.example/pay');

        Http::assertSent(fn (ClientRequest $r) => $r['binary_mode'] === true);
    }

    public function test_preference_is_not_created_for_another_users_order(): void
    {
        Http::fake();
        $order = $this->makeOrder($this->student());

        $this->actingAs($this->demoStudent())
            ->withSession(['payment_data' => ['order_id' => $order->id]])
            ->get('/mercadopago/procesar-pago')
            ->assertRedirect(route('checkout'));

        Http::assertNothingSent();
    }

    public function test_exchange_rate_is_applied_to_the_charged_amount(): void
    {
        $this->configureGateway(['exchange_rate' => '2']);
        Http::fake(['api.mercadopago.com/checkout/preferences' => Http::response(['init_point' => 'https://live.example/pay'], 201)]);

        $order = $this->makeOrder();
        $this->actingAs($this->student())
            ->withSession(['payment_data' => ['order_id' => $order->id]])
            ->get('/mercadopago/procesar-pago')
            ->assertRedirect('https://live.example/pay');

        Http::assertSent(fn (ClientRequest $r) => $r['items'][0]['unit_price'] == self::AMOUNT * 2);
    }

    public function test_webhook_with_valid_signature_is_accepted(): void
    {
        $order = $this->makeOrder();
        $this->fakePayment($this->payment($order, ['status' => 'in_process']));

        $this->sendWebhook(987654321)->assertOk();
        Http::assertSent(fn (ClientRequest $r) => $r->url() === 'https://api.mercadopago.com/v1/payments/987654321');
    }

    public function test_webhook_with_invalid_signature_is_rejected(): void
    {
        $order = $this->makeOrder();
        $this->fakePayment($this->payment($order));

        $this->sendWebhook(987654321, $this->signedHeaders('987654321', 'another-secret'))->assertStatus(401);
        // Valid signature for a different data.id.
        $this->sendWebhook(987654321, $this->signedHeaders('111'))->assertStatus(401);

        Http::assertNothingSent();
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_webhook_without_signature_is_rejected(): void
    {
        $order = $this->makeOrder();
        $this->fakePayment($this->payment($order));

        $this->postJson('/mercadopago/webhook?type=payment&data.id=987654321', ['data' => ['id' => '987654321']])->assertStatus(401);
        $this->sendWebhook(987654321, ['x-request-id' => 'abc'])->assertStatus(401);

        Http::assertNothingSent();
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_webhook_is_rejected_when_no_secret_is_configured(): void
    {
        $this->configureGateway(['mp_webhook_secret' => '']);
        $order = $this->makeOrder();
        $this->fakePayment($this->payment($order));

        // Signed with the empty string: must still be refused.
        $this->sendWebhook(987654321, $this->signedHeaders('987654321', ''))->assertStatus(401);

        Http::assertNothingSent();
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_approved_payment_completes_order_and_enrolls_student_once_even_if_webhook_repeats(): void
    {
        $order = $this->makeOrder();
        $this->fakePayment($this->payment($order));

        for ($i = 0; $i < 3; $i++) {
            $this->sendWebhook(987654321)->assertOk();
        }

        $order->refresh();
        $this->assertSame('complete', $order->status);
        $this->assertSame('987654321', $order->transaction_id);
        $this->assertSame(1, $this->enrollments($order));
    }

    public function test_complete_purchase_job_is_dispatched_only_once(): void
    {
        Bus::fake([CompletePurchaseJob::class]);
        $order = $this->makeOrder();
        $this->fakePayment($this->payment($order));

        for ($i = 0; $i < 3; $i++) {
            $this->sendWebhook(987654321)->assertOk();
        }

        Bus::assertDispatchedTimes(CompletePurchaseJob::class, 1);
        $this->assertSame('complete', $order->fresh()->status);
    }

    public function test_payment_with_different_amount_does_not_complete_the_order(): void
    {
        $order = $this->makeOrder();
        $this->fakePayment($this->payment($order, ['transaction_amount' => 1.00]));

        $this->sendWebhook(987654321)->assertOk();

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(0, $this->enrollments($order));
    }

    public function test_payment_with_different_currency_does_not_complete_the_order(): void
    {
        $order = $this->makeOrder();
        $this->fakePayment($this->payment($order, ['currency_id' => 'USD']));

        $this->sendWebhook(987654321)->assertOk();

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(0, $this->enrollments($order));
    }

    public function test_pending_and_rejected_payments_do_not_complete_the_order(): void
    {
        $order = $this->makeOrder();

        foreach (['pending', 'in_process', 'rejected', 'cancelled'] as $status) {
            $this->fakePayment($this->payment($order, ['status' => $status]));
            $this->sendWebhook(987654321)->assertOk();

            $this->assertSame('pending', $order->fresh()->status, "status {$status}");
            $this->assertSame(0, $this->enrollments($order), "status {$status}");
        }
    }

    public function test_notification_for_unknown_order_is_ignored(): void
    {
        $this->fakePayment(['id' => 5, 'status' => 'approved', 'external_reference' => '99999999', 'transaction_amount' => 10, 'currency_id' => 'MXN']);

        $this->sendWebhook(5)->assertOk();
    }

    public function test_notification_for_unknown_payment_returns_ok_without_effects(): void
    {
        Http::fake(['api.mercadopago.com/v1/payments/*' => Http::response(['message' => 'Payment not found'], 404)]);

        $this->sendWebhook(123456)->assertOk();
    }

    public function test_api_failure_makes_mercado_pago_retry(): void
    {
        $order = $this->makeOrder();
        Http::fake(['api.mercadopago.com/v1/payments/*' => Http::response(['message' => 'boom'], 500)]);

        $this->sendWebhook(987654321)->assertStatus(500);
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_refunded_payment_revokes_course_access(): void
    {
        $order = $this->makeOrder();

        $this->fakePayment($this->payment($order));
        $this->sendWebhook(987654321)->assertOk();
        $this->assertSame(1, $this->enrollments($order));

        $this->fakePayment($this->payment($order, ['status' => 'refunded']));
        $this->sendWebhook(987654321)->assertOk();
        $this->assertSame(0, $this->enrollments($order));

        // Replays stay harmless.
        $this->sendWebhook(987654321)->assertOk();
        $this->assertSame(0, $this->enrollments($order));
    }

    public function test_charged_back_payment_revokes_course_access(): void
    {
        $order = $this->makeOrder();
        $this->fakePayment($this->payment($order));
        $this->sendWebhook(987654321)->assertOk();

        $this->fakePayment($this->payment($order, ['status' => 'charged_back']));
        $this->sendWebhook(987654321)->assertOk();

        $this->assertSame(0, $this->enrollments($order));
    }

    public function test_refund_of_an_unrelated_payment_does_not_revoke_access(): void
    {
        $order = $this->makeOrder();
        $this->fakePayment($this->payment($order));
        $this->sendWebhook(987654321)->assertOk();

        $this->fakePayment($this->payment($order, ['id' => 555, 'status' => 'refunded']));
        $this->sendWebhook(555)->assertOk();

        $this->assertSame(1, $this->enrollments($order));
    }

    public function test_refund_method_calls_the_refunds_endpoint(): void
    {
        Http::fake(['api.mercadopago.com/v1/payments/*/refunds' => Http::response(['id' => 1, 'amount' => 100], 201)]);
        $driver = getGatewayObject('mercadopago');

        $driver->refund('987654321', 100.0);
        $driver->refund('987654321');

        $requests = [];
        Http::assertSentCount(2);
        Http::assertSent(function (ClientRequest $r) use (&$requests) {
            $requests[] = $r;
            return $r->url() === 'https://api.mercadopago.com/v1/payments/987654321/refunds'
                && $r->method() === 'POST'
                && $r->hasHeader('X-Idempotency-Key')
                && $r->hasHeader('Authorization', 'Bearer TEST-access-token');
        });
        $this->assertEquals(100, $requests[0]['amount']);
        $this->assertArrayNotHasKey('amount', $requests[1]->data());
    }

    public function test_return_url_confirms_payment_with_mercado_pago_and_redirects_to_thank_you(): void
    {
        $order = $this->makeOrder();
        $this->fakePayment($this->payment($order));

        $this->actingAs($this->student())
            ->get('/mercadopago/retorno?order=' . $order->id . '&payment_id=987654321&status=approved')
            ->assertRedirect(route('thank-you', ['id' => $order->id]));

        $this->assertSame('complete', $order->fresh()->status);
        $this->assertSame(1, $this->enrollments($order));
    }

    public function test_return_url_does_not_trust_the_query_string(): void
    {
        $order = $this->makeOrder();
        $this->fakePayment($this->payment($order, ['status' => 'pending']));

        $this->actingAs($this->student())
            ->get('/mercadopago/retorno?order=' . $order->id . '&payment_id=987654321&status=approved&collection_status=approved')
            ->assertRedirect(route('payment.pending', ['order' => $order->id]));

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(0, $this->enrollments($order));

        // Without a payment id nothing can be confirmed either.
        $this->actingAs($this->student())
            ->get('/mercadopago/retorno?order=' . $order->id . '&status=approved')
            ->assertRedirect(route('payment.pending', ['order' => $order->id]));
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_return_url_for_another_users_order_is_denied(): void
    {
        Http::fake();
        $order = $this->makeOrder($this->student());

        $this->actingAs($this->demoStudent())
            ->get('/mercadopago/retorno?order=' . $order->id . '&payment_id=987654321&status=approved')
            ->assertForbidden();

        Http::assertNothingSent();
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_return_url_with_payment_of_another_order_does_not_complete_anything(): void
    {
        $mine = $this->makeOrder($this->student());
        $other = $this->makeOrder($this->demoStudent());
        // The payment belongs to the other order.
        $this->fakePayment($this->payment($other));

        $this->actingAs($this->student())
            ->get('/mercadopago/retorno?order=' . $mine->id . '&payment_id=987654321')
            ->assertRedirect(route('payment.pending', ['order' => $mine->id]));

        $this->assertSame('pending', $mine->fresh()->status);
        $this->assertSame('pending', $other->fresh()->status);
    }

    public function test_return_url_requires_authentication(): void
    {
        $order = $this->makeOrder();

        $this->get('/mercadopago/retorno?order=' . $order->id)->assertRedirect();
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_return_url_after_webhook_just_redirects_without_extra_completion(): void
    {
        Bus::fake([CompletePurchaseJob::class]);
        $order = $this->makeOrder();
        $this->fakePayment($this->payment($order));

        $this->sendWebhook(987654321)->assertOk();
        $this->actingAs($this->student())
            ->get('/mercadopago/retorno?order=' . $order->id . '&payment_id=987654321')
            ->assertRedirect(route('thank-you', ['id' => $order->id]));

        Bus::assertDispatchedTimes(CompletePurchaseJob::class, 1);
    }

    // ------------------------------------------------------------ pending payments (OXXO / SPEI / card under review)

    private function oxxoPayment(Order $order, array $override = []): array
    {
        return $this->payment($order, array_merge([
            'status' => 'pending', 'payment_type_id' => 'ticket', 'payment_method_id' => 'oxxo',
            'date_of_expiration' => now()->addDays(3)->toIso8601String(),
            'transaction_details' => ['external_resource_url' => 'https://www.mercadopago.com.mx/sandbox/payments/987654321/ticket?caller_id=1'],
        ], $override));
    }

    public function test_an_oxxo_payment_waits_without_enrolling_and_tells_the_buyer_once(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $order = $this->makeOrder();
        $this->fakePayment($this->oxxoPayment($order));

        $this->sendWebhook(987654321)->assertOk();
        $this->sendWebhook(987654321)->assertOk();   // Mercado Pago repeats notifications

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(0, $this->enrollments($order));
        \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\PaymentPendingEmail::class, 1);
        \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\PaymentPendingEmail::class, function ($m) use ($order) {
            $html = $m->render();
            $this->assertSame('OXXO', $m->info['label']);
            $this->assertStringContainsString('ticket?caller_id=1', $html);
            $this->assertStringContainsString('OXXO', $html);

            return $m->hasTo($order->email);
        });
    }

    public function test_the_return_from_an_oxxo_payment_shows_the_pending_page_and_clears_the_cart(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $order = $this->makeOrder();
        $this->fakePayment($this->oxxoPayment($order));

        $this->actingAs($this->student())->get('/mercadopago/retorno?order=' . $order->id . '&payment_id=987654321&status=pending')
            ->assertRedirect(route('payment.pending', ['order' => $order->id]));

        $this->actingAs($this->student())->get(route('payment.pending', ['order' => $order->id]))->assertOk()
            ->assertSee('Falta completar tu pago con OXXO')->assertSee('data-testid="voucher-link"', false)->assertSee('ticket?caller_id=1', false);
    }

    public function test_a_card_under_review_says_so(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $order = $this->makeOrder();
        $this->fakePayment($this->payment($order, ['status' => 'in_process', 'payment_type_id' => 'credit_card', 'payment_method_id' => 'visa']));

        $this->sendWebhook(987654321)->assertOk();

        \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\PaymentPendingEmail::class, fn ($m) => $m->info['kind'] === 'review');
        $this->actingAs($this->student())->get(route('payment.pending', ['order' => $order->id]))->assertOk()->assertSee('Estamos revisando tu pago');
    }

    public function test_the_oxxo_payment_that_is_approved_later_completes_the_order_and_sends_the_receipt(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        config(['academy.private_mode' => true]);
        $order = $this->makeOrder();
        $this->fakePayment($this->oxxoPayment($order));
        $this->sendWebhook(987654321)->assertOk();

        $this->fakePayment($this->payment($order, ['status' => 'approved']));
        $this->sendWebhook(987654321)->assertOk();

        $this->assertSame('complete', $order->fresh()->status);
        $this->assertSame(1, $this->enrollments($order));
        $this->assertNull(\App\Services\PendingPayments::info($order), 'the pending instructions are gone');
        \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\PurchaseConfirmedEmail::class, function ($m) use ($order) {
            $this->assertStringContainsString('Ir a mis cursos', $m->render());

            return $m->hasTo($order->email);
        });
        // The pending page of a paid order goes to the thank-you page.
        $this->actingAs($this->student())->get(route('payment.pending', ['order' => $order->id]))->assertRedirect(route('thank-you', ['id' => $order->id]));
    }

    public function test_the_pending_page_is_only_for_the_buyer(): void
    {
        $order = $this->makeOrder($this->student());

        $this->actingAs($this->demoStudent())->get(route('payment.pending', ['order' => $order->id]))->assertForbidden();
    }

    public function test_max_installments_is_sent_in_the_preference_only_when_set(): void
    {
        $order = $this->makeOrder();
        Http::fake(['api.mercadopago.com/checkout/preferences' => Http::response(['id' => 'p', 'init_point' => 'https://mp.test/x'], 201)]);

        getGatewayObject('mercadopago')->createPreference($order);
        Http::assertSent(fn ($r) => !isset($r['payment_methods']));

        $this->configureGateway(['max_installments' => '6']);
        Http::swap(new HttpFactory());
        Http::fake(['api.mercadopago.com/checkout/preferences' => Http::response(['id' => 'p', 'init_point' => 'https://mp.test/x'], 201)]);
        getGatewayObject('mercadopago')->createPreference($order);
        Http::assertSent(fn ($r) => ($r['payment_methods']['installments'] ?? null) === 6);
    }

    public function test_a_free_or_card_purchase_sends_the_spanish_receipt_once(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        config(['academy.private_mode' => true]);
        $order = $this->makeOrder();
        $order->update(['status' => 'complete']);

        dispatch_sync(new CompletePurchaseJob($order->fresh()));
        dispatch_sync(new CompletePurchaseJob($order->fresh()));

        \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\PurchaseConfirmedEmail::class, 1);
    }
}
