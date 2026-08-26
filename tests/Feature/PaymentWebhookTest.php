<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * Exercises the payment webhook/callback endpoints with simulated (fake but
 * well-formed, per the controller's expectations) payloads, hitting them
 * directly via the Laravel test client — success-shaped payloads AND
 * malformed/invalid ones (missing fields, unknown order id, wrong gateway).
 *
 * IMPORTANT constraint honoured throughout this file: no real network calls.
 * `admin_settings.payment_method` in this environment has Stripe "on" but
 * with blank stripe_key/stripe_secret (see project QA notes — no real
 * gateway keys configured anywhere). That happens to make every code path
 * exercised here safe to call directly:
 *   - Modules/LaraPayease/Drivers/Stripe::chargeCustomer() returns an error
 *     array immediately when keys are empty, *before* touching the Stripe
 *     SDK — see the `empty($this->getKeys()['stripe_key'])` guard.
 *   - Modules/LaraPayease/Drivers/Stripe::paymentResponse() returns an error
 *     array immediately when `session('stripe_session_id')` is empty (which
 *     it always is here, since we never call prepareCharge) — again before
 *     touching the Stripe SDK.
 *   - PayFast has no driver class at all (Modules/LaraPayease/Drivers has
 *     only Stripe.php) and `PaymentFactory::supportedGateways()` doesn't
 *     list 'payfast', so `getGatewayObject('payfast')` always short-circuits
 *     to `''` — the payfast webhook is effectively a no-op today regardless
 *     of payload.
 * `Http::fake()` is added defensively on every test as a hard guard in case
 * any of the above assumptions ever change.
 *
 * NOT exercised here (see PlatformSmokeTest::SKIPPED_ROUTES for the
 * up-to-date list): `payease.stripe` (StripePaymentController::prepareCharge).
 * Unlike chargeCustomer(), prepareCharge() has no empty-key guard — it
 * unconditionally calls the Stripe PHP SDK's `Session::create(...)`, which
 * uses its own cURL-based HTTP client rather than Laravel's `Http` facade,
 * so `Http::fake()` cannot intercept it. Hitting that route for real would
 * always issue a live request to api.stripe.com even with garbage
 * credentials, which the task rules out. See the report for a related
 * observation: that endpoint also takes `stripe_secret` directly from the
 * request body rather than from server-side settings, which is worth a
 * human security look independent of this testing limitation.
 */
class PaymentWebhookTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
    }

    public function test_payfast_webhook_with_success_shaped_payload_does_not_crash(): void
    {
        Http::fake();

        // A payload shaped like a real PayFast ITN callback would be, with a
        // (fake) signature — payfast isn't actually wired to a driver in
        // this codebase (see class docblock), so this should just no-op.
        $response = $this->post('/payfast/webhook', [
            'm_payment_id' => 'ORDER-123',
            'pf_payment_id' => '987654321',
            'payment_status' => 'COMPLETE',
            'amount_gross' => '19.99',
            'signature' => md5('fake-signature'),
        ]);

        $this->assertLessThan(500, $response->getStatusCode(), $response->getContent());
    }

    public function test_payfast_webhook_with_malformed_payload_does_not_crash(): void
    {
        Http::fake();

        // Missing fields, wrong types, unknown order id.
        $response = $this->post('/payfast/webhook', [
            'unexpected_field' => ['nested' => 'garbage'],
        ]);
        $this->assertLessThan(500, $response->getStatusCode(), $response->getContent());

        $response = $this->post('/payfast/webhook', []);
        $this->assertLessThan(500, $response->getStatusCode(), $response->getContent());
    }

    public function test_payment_success_get_and_post_do_not_crash_for_payfast_method(): void
    {
        Http::fake();

        // No Order rows exist yet in a fresh transaction — this used to
        // throw UrlGenerationException (route('thank-you', ['id' => null]))
        // because Order::latest()->first() was null. Now guarded.
        Order::query()->delete();

        $get = $this->get('/payment/success?payment_method=payfast');
        $this->assertLessThan(500, $get->getStatusCode(), $get->getContent());

        $post = $this->post('/payment/success', ['payment_method' => 'payfast']);
        $this->assertLessThan(500, $post->getStatusCode(), $post->getContent());

        // api/upi variant returns JSON instead of a redirect.
        $apiGet = $this->getJson('/payment/success?payment_method=payfast&source=api&upi=1');
        $apiGet->assertOk();
    }

    public function test_payment_success_does_not_crash_for_stripe_or_unknown_gateway(): void
    {
        Http::fake();

        foreach (['stripe', 'unknown-gateway-xyz', null] as $method) {
            $params = $method !== null ? ['payment_method' => $method] : [];

            $get = $this->get('/payment/success?' . http_build_query($params));
            $this->assertLessThan(500, $get->getStatusCode(), "GET payment/success?payment_method={$method} crashed: " . $get->getContent());

            $post = $this->post('/payment/success', $params);
            $this->assertLessThan(500, $post->getStatusCode(), "POST payment/success (payment_method={$method}) crashed: " . $post->getContent());
        }

        Http::assertNothingSent();
    }

    public function test_payment_process_route_without_session_data_redirects_safely(): void
    {
        Http::fake();

        foreach (['stripe', 'payfast', 'unknown-gateway-xyz'] as $gateway) {
            $response = $this->get("/{$gateway}/process/payment");
            $this->assertLessThan(500, $response->getStatusCode(), "{$gateway}/process/payment crashed: " . $response->getContent());
        }

        Http::assertNothingSent();
    }

    public function test_payment_process_route_with_session_payment_data_does_not_crash_or_call_out(): void
    {
        Http::fake();

        // Simulate having gone through preparePayment(): a payment_data
        // session payload is present when the gateway callback route fires.
        $response = $this->withSession(['payment_data' => [
            'amount' => 19.99,
            'title' => 'Lernen Purchase',
            'description' => 'Lernen Purchase Order Confirmation for reference #1',
            'ipn_url' => url('/'),
            'order_id' => 1,
            'track' => 'track-123',
            'cancel_url' => url('/checkout'),
            'success_url' => url('/'),
            'email' => 'qa-student@horizontia.test',
            'name' => 'QA',
            'payment_type' => 'stripe',
        ]])->get('/stripe/process/payment');

        // Stripe keys are blank in this environment, so chargeCustomer()
        // must fail gracefully (redirect back to checkout with an error)
        // rather than ever reaching the Stripe SDK.
        $this->assertLessThan(500, $response->getStatusCode(), $response->getContent());
        Http::assertNothingSent();
    }
}
