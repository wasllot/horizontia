<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppMessage;
use App\Livewire\Frontend\Checkout;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\WhatsappMessage;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Larabuild\Optionbuilder\Facades\Settings;
use Livewire\Livewire;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\CourseLiveStream;
use Modules\Courses\Services\LiveSessionService;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** WhatsApp automático: solo con consentimiento, una vez por aviso, con la API de Meta, sin afectar la compra si falla. */
class WhatsAppAutomationTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.private_mode' => true, 'academy.whatsapp_country_code' => '52']);
        $this->configure();
        $this->student()->forceFill(['whatsapp_phone' => null, 'whatsapp_optin_at' => null])->save();
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function configure(array $over = []): void
    {
        foreach ($over + ['wa_auto_enabled' => '1', 'wa_phone_number_id' => '1098765432101234', 'wa_access_token' => 'EAAG-test-token', 'wa_language' => 'es_MX'] as $k => $v) {
            Settings::set('_integrations', $k, $v);
        }
    }

    private function optIn(string $phone = '55 1234 5678'): void
    {
        app(WhatsAppService::class)->optIn($this->student(), $phone);
    }

    private function paidOrder(): Order
    {
        $o = Order::create(['user_id' => $this->student()->id, 'unique_payment_id' => (string) Str::uuid(), 'amount' => 800, 'used_wallet_amt' => 0, 'currency' => 'MXN', 'first_name' => 'Ana', 'last_name' => 'L', 'company' => '', 'country' => 'MX', 'state' => 'X', 'postal_code' => '1', 'city' => 'X', 'phone' => '5512345678', 'email' => 'a@b.test', 'payment_method' => 'mercadopago', 'status' => 'complete']);
        $o->items()->create(['title' => 'Curso de ventas', 'quantity' => 1, 'price' => 800, 'total' => 800, 'options' => [], 'orderable_type' => Course::class, 'orderable_id' => $this->course()->id]);

        return $o;
    }

    public function test_phone_numbers_are_normalized_to_international_digits(): void
    {
        $this->assertSame('525512345678', WhatsAppService::normalizePhone('55 1234-5678'));
        $this->assertSame('525512345678', WhatsAppService::normalizePhone('+52 (55) 1234 5678'));
        $this->assertSame('5215512345678', WhatsAppService::normalizePhone('005215512345678'));
        $this->assertNull(WhatsAppService::normalizePhone('12345'));
        $this->assertNull(WhatsAppService::normalizePhone('hola'));
    }

    public function test_nothing_is_sent_without_consent_or_when_it_is_off_or_misconfigured(): void
    {
        Queue::fake();
        $order = $this->paidOrder();
        $svc = app(WhatsAppService::class);

        $svc->purchaseConfirmed($order);                       // sin consentimiento
        $this->optIn();
        $this->configure(['wa_auto_enabled' => '0']);
        $svc->purchaseConfirmed($order);                       // apagado
        $this->configure(['wa_auto_enabled' => '1', 'wa_access_token' => '']);
        $svc->purchaseConfirmed($order);                       // sin token

        Queue::assertNothingPushed();
        $this->assertSame(0, WhatsappMessage::count());
    }

    public function test_a_confirmed_purchase_sends_the_template_once_with_the_right_payload(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.ABC']]], 200)]);
        $this->optIn();
        $order = $this->paidOrder();

        app(WhatsAppService::class)->purchaseConfirmed($order);
        app(WhatsAppService::class)->purchaseConfirmed($order);   // el webhook se repite: no se duplica

        Http::assertSentCount(1);
        Http::assertSent(function (Request $r) {
            $b = $r->data();

            return str_contains($r->url(), '/1098765432101234/messages') && $r->hasHeader('Authorization', 'Bearer EAAG-test-token')
                && $b['to'] === '525512345678' && $b['template']['name'] === 'horizontia_compra' && $b['template']['language']['code'] === 'es_MX'
                && str_contains(json_encode($b['template']['components']), 'Curso de ventas');
        });
        $row = WhatsappMessage::firstOrFail();
        $this->assertSame(['sent', 'wamid.ABC', '5678'], [$row->status, $row->wamid, $row->to_tail]);
    }

    public function test_a_failing_api_is_logged_without_breaking_anything_and_keeps_the_phone_out_of_the_log(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Template name does not exist']], 400)]);
        $this->optIn();

        app(WhatsAppService::class)->purchaseConfirmed($this->paidOrder());

        $row = WhatsappMessage::firstOrFail();
        $this->assertSame(['failed', 'Template name does not exist'], [$row->status, $row->error]);
        $this->assertStringNotContainsString('525512345678', json_encode($row->toArray()), 'only the last digits are stored');
    }

    public function test_server_errors_are_retried_by_the_queue(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Temporarily unavailable']], 503)]);
        $this->optIn();
        $row = WhatsappMessage::create(['user_id' => $this->student()->id, 'event' => 'purchase', 'subject' => 'order:1', 'template' => 'horizontia_compra', 'status' => 'queued']);

        $this->expectException(\RuntimeException::class);
        (new SendWhatsAppMessage($row->id, '525512345678', 'horizontia_compra', 'es_MX', ['Ana']))->handle();
    }

    public function test_checkout_stores_the_consent_only_when_the_box_is_ticked_and_asks_for_the_number(): void
    {
        Settings::set('admin_settings', 'payment_method', ['stripe' => ['status' => 'on', 'currency' => 'MXN', 'exchange_rate' => '', 'stripe_key' => 'pk_test_x', 'stripe_secret' => 'sk_test_x']]);
        $c = $this->course2()->replicate();
        $c->title = 'Curso wa';
        $c->slug = 'wa-' . uniqid();
        $c->status = 'active';
        $c->delivery = 'online';
        $c->save();
        $c->pricing()->create(['price' => 100, 'discount' => 0, 'final_price' => 100]);
        CartItem::where('user_id', $this->student()->id)->delete();
        Cache::forget('cart_items_' . $this->student()->id);
        $this->app->forgetInstance('cart');
        \App\Facades\Cart::clearResolvedInstance('cart');
        CartItem::create(['user_id' => $this->student()->id, 'cartable_id' => $c->id, 'cartable_type' => Course::class, 'name' => $c->title, 'qty' => 1, 'price' => 100, 'options' => ['price' => 100]]);

        $page = Livewire::actingAs($this->student())->test(Checkout::class)->assertSee('data-testid="co-whatsapp"', false)
            ->set('form.firstName', 'Ana')->set('form.lastName', 'L')->set('form.email', 'a@b.test');

        $page->set('form.whatsappOptin', true)->set('form.phone', '')->call('updateInfo')->assertHasErrors('form.phone');
        $this->assertNull($this->student()->fresh()->whatsapp_optin_at);

        $page->set('form.phone', '55 1234 5678')->call('updateInfo')->assertHasNoErrors();
        $u = $this->student()->fresh();
        $this->assertSame('525512345678', $u->whatsapp_phone);
        $this->assertNotNull($u->whatsapp_optin_at);
    }

    public function test_the_consent_box_is_hidden_when_automation_is_off(): void
    {
        $this->configure(['wa_auto_enabled' => '0']);
        $c = $this->course2();
        CartItem::where('user_id', $this->student()->id)->delete();
        Cache::forget('cart_items_' . $this->student()->id);
        CartItem::create(['user_id' => $this->student()->id, 'cartable_id' => $c->id, 'cartable_type' => Course::class, 'name' => $c->title, 'qty' => 1, 'price' => 100, 'options' => ['price' => 100]]);

        $this->actingAs($this->student())->get(route('checkout'))->assertDontSee('data-testid="co-whatsapp"', false);
    }

    public function test_live_session_reminders_go_to_enrolled_people_with_consent_once(): void
    {
        Queue::fake();
        $this->optIn();
        $stream = app(LiveSessionService::class)->create($this->course(), ['title' => 'Masterclass', 'meeting_link' => 'https://meet.google.com/abc-defg-hij', 'date_time' => now()->addHours(5), 'duration_minutes' => 60], null, announce: false);

        $svc = app(WhatsAppService::class);
        $this->assertSame(1, $svc->liveReminder($stream->fresh('course')));
        $this->assertSame(0, $svc->liveReminder($stream->fresh('course')), 'once per session');
        Queue::assertPushed(SendWhatsAppMessage::class, fn ($j) => $j->template === 'horizontia_en_vivo' && in_array('Masterclass', $j->params, true));
    }

    public function test_opting_out_stops_the_notices(): void
    {
        Queue::fake();
        $this->optIn();
        app(WhatsAppService::class)->optOut($this->student()->fresh());

        app(WhatsAppService::class)->purchaseConfirmed($this->paidOrder());

        Queue::assertNothingPushed();
    }

    public function test_custom_template_names_are_used_but_invalid_ones_fall_back_to_the_default(): void
    {
        $this->configure(['wa_template_purchase' => 'mi_plantilla_compra', 'wa_template_live' => 'Nombre inválido!']);

        $this->assertSame('mi_plantilla_compra', WhatsAppService::template('purchase'));
        $this->assertSame('horizontia_en_vivo', WhatsAppService::template('live'));
        $this->assertSame('horizontia_presencial', WhatsAppService::template('onsite'));
    }

    public function test_the_settings_screen_and_the_report_show_it_and_the_token_is_not_exported(): void
    {
        $html = $this->actingAs($this->admin())->get('/admin/ajustes')->assertOk()->getContent();
        $this->assertStringContainsString('wa_access_token', $html);
        $this->assertStringContainsString('Avisos automáticos', $html);
        $this->assertMatchesRegularExpression('/type="password"[^>]*name="wa_access_token"|name="wa_access_token"[^>]*type="password"/', $html);

        WhatsappMessage::create(['user_id' => $this->student()->id, 'event' => 'purchase', 'subject' => 'order:9', 'template' => 'horizontia_compra', 'to_tail' => '5678', 'status' => 'failed', 'error' => 'Template name does not exist']);
        Livewire::actingAs($this->admin())->test(\App\Livewire\Pages\Admin\Reports\Reports::class)->call('setTab', 'whatsapp')->assertSee('Avisos por WhatsApp')->assertSee('Template name does not exist');

        $export = json_encode((new \App\Services\ConfigTransfer\ConfigTransfer())->export(['general', 'integrations'], ['credentials' => false]));
        $this->assertStringNotContainsString('EAAG-test-token', $export);
    }
}
