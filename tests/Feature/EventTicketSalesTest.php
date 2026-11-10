<?php

namespace Tests\Feature;

use App\Mail\EventTicketsEmail;
use App\Models\Event;
use App\Models\EventOrder;
use App\Models\EventTicket;
use App\Models\User;
use App\Services\Events\EventSales;
use App\Support\QrCode;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Larabuild\Optionbuilder\Facades\Settings;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Página aislada de evento con compra de invitado: aislamiento, reglas de venta, Mercado Pago, QR, correo, CSV y SEO. */
class EventTicketSalesTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    private const SECRET = 'evt-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        Event::query()->update(['redirect_events_index' => false, 'status' => Event::DRAFT]);
        $methods = setting('admin_settings.payment_method') ?: [];
        $methods['mercadopago'] = ['status' => 'on', 'currency' => 'MXN', 'exchange_rate' => '', 'enable_test_mode' => '', 'binary_mode' => '',
            'mp_access_token' => 'TEST-token', 'mp_public_key' => 'TEST-pk', 'mp_webhook_secret' => self::SECRET];
        Settings::set('admin_settings', 'payment_method', $methods);
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function event(array $override = []): Event
    {
        return Event::create(array_merge([
            'slug' => 'astro-' . Str::lower(Str::random(6)), 'name' => 'Astrofest® Prueba', 'status' => Event::PUBLISHED,
            'start_at' => now()->addMonths(2), 'end_at' => now()->addMonths(2)->addDay(), 'venue_name' => 'Club Campestre', 'venue_city' => 'Estado de México',
            'price' => 1740, 'iva_rate' => 16, 'max_per_order' => 6, 'sales_enabled' => true, 'hold_minutes' => 45, 'offline_hold_hours' => 72,
            'policies_html' => '<p>Política de clima y cancelación.</p>',
        ], $override));
    }

    private function buyer(array $override = []): array
    {
        return array_merge(['name' => 'Ana Pérez', 'email' => 'ana@example.com', 'phone' => '55 1234 5678', 'attendees' => ['Ana Pérez'], 'minors' => 0, 'has_telescope' => 'si', 'heard_from' => 'Instagram', 'accept_policies' => 1], $override);
    }

    private function cartWith(Event $e, int $qty)
    {
        return $this->post($e->url('carrito'), ['quantity' => $qty]);
    }

    private function fakeMp(array $payment = [], ?string $initPoint = 'https://www.mercadopago.com.mx/checkout/v1/redirect?pref_id=PREF123'): void
    {
        Http::swap(new HttpFactory());
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response(['id' => 'PREF123', 'init_point' => $initPoint], 201),
            'api.mercadopago.com/v1/payments/*' => Http::response($payment, 200),
        ]);
    }

    private function place(Event $e, int $qty, array $buyer = []): \Illuminate\Testing\TestResponse
    {
        $this->cartWith($e, $qty);

        return $this->post($e->url('pago'), $this->buyer(array_merge(['attendees' => array_map(fn ($i) => 'Asistente ' . ($i + 1), range(0, $qty - 1))], $buyer)));
    }

    private function signedHeaders(string $dataId): array
    {
        $ts = (string) time();
        $rid = 'req-' . Str::random(8);

        return ['x-signature' => "ts={$ts},v1=" . hash_hmac('sha256', 'id:' . strtolower($dataId) . ";request-id:{$rid};ts:{$ts};", self::SECRET), 'x-request-id' => $rid];
    }

    private function webhook(string $paymentId)
    {
        return $this->withHeaders($this->signedHeaders($paymentId))->postJson('/mercadopago/webhook?type=payment&data.id=' . $paymentId, ['type' => 'payment', 'data' => ['id' => $paymentId]]);
    }

    // ---- QR ----

    public function test_qr_generator_builds_a_valid_matrix_and_png(): void
    {
        $m = QrCode::matrix('https://horizontia.com/eventos/astrofest/boleto/AF-ABCD-2345');
        $this->assertSame(count($m), count($m[0]));
        $this->assertTrue($m[0][0] && $m[0][6] && $m[6][0], 'finder pattern');
        $this->assertStringStartsWith("\x89PNG", QrCode::png('AF-ABCD-2345'));
        $this->assertStringContainsString('<svg', QrCode::svg('AF-ABCD-2345'));
        $this->assertSame(21, count(QrCode::matrix('HZ')), 'versión 1');
    }

    // ---- Aislamiento y SEO ----

    public function test_event_page_is_isolated_from_the_rest_of_the_site(): void
    {
        $e = $this->event();
        $html = $this->get($e->url())->assertOk()->getContent();

        $this->assertStringContainsString('Astrofest', $html);
        $this->assertStringContainsString('Aparta tu lugar', $html);
        foreach (['href="' . url('/cursos') . '"', 'href="' . url('/nosotros') . '"', 'href="' . url('/contacto') . '"', 'href="' . url('/blog') . '"', 'Iniciar sesión', 'Solicitar acceso', 'Únete a nuestra comunidad', 'type="search"'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $html, "no debe aparecer: $forbidden");
        }
        $this->assertStringContainsString('Aviso de cookies', $html);
        $this->assertStringContainsString('index, follow', $html);
        $this->assertStringContainsString('rel="canonical" href="' . $e->url() . '"', $html);
        $this->assertStringContainsString('"@type": "Event"', $html);
        $this->assertStringContainsString('"price": "1740"', $html);
        $this->assertStringContainsString('InStock', $html);
        foreach (['AstroSapiens', 'Astrotienda', 'Galindo', 'Humanitalent', 'POR CONFIRMAR', 'lorem ipsum', '⚠'] as $banned) {
            $this->assertStringNotContainsString($banned, $html);
        }
    }

    public function test_cart_checkout_and_thanks_are_noindex_and_out_of_the_sitemap(): void
    {
        $e = $this->event();
        $this->cartWith($e, 2)->assertRedirect($e->url('carrito'));
        $this->get($e->url('carrito'))->assertOk()->assertSee('noindex', false)->assertSee('Volver al evento');
        $this->get($e->url('pago'))->assertOk()->assertSee('noindex', false);

        $sitemap = $this->get('/sitemap.xml')->getContent();
        $this->assertStringContainsString($e->url(), $sitemap);
        $this->assertStringNotContainsString($e->url('carrito'), $sitemap);
        $this->assertStringNotContainsString($e->url('pago'), $sitemap);
        $this->assertStringContainsString($e->url('politicas'), $sitemap);
    }

    public function test_unknown_event_pages_show_the_section_404_and_drafts_are_hidden(): void
    {
        $e = $this->event(['status' => Event::DRAFT]);
        $this->get($e->url())->assertNotFound()->assertSee('Esta página se perdió en el espacio');
        $this->get('/eventos/no-existe')->assertNotFound()->assertSee('Volver al evento');

        $e->update(['status' => Event::PUBLISHED]);
        $this->get($e->url('algo-raro'))->assertNotFound()->assertSee('Esta página se perdió en el espacio');
    }

    public function test_events_index_redirects_to_the_featured_event_with_a_302(): void
    {
        $e = $this->event(['redirect_events_index' => true]);
        $this->get('/eventos')->assertStatus(302)->assertRedirect($e->url());
    }

    // ---- Reglas de venta ----

    public function test_sales_stay_closed_without_policies_price_or_the_master_switch(): void
    {
        $sales = app(EventSales::class);
        $this->assertSame('soon', $sales->state($this->event(['policies_html' => null])));
        $this->assertSame('soon', $sales->state($this->event(['sales_enabled' => false])));
        $this->assertSame('soon', $sales->state($this->event(['price' => 0])));
        $this->assertSame('open', $sales->state($this->event()));

        $e = $this->event(['policies_html' => null]);
        $this->get($e->url())->assertOk()->assertSee('disponibles muy pronto')->assertDontSee('Agregar al carrito');
        $this->cartWith($e, 1)->assertRedirect($e->url());
        $this->assertNull(session('event_cart.' . $e->id));
    }

    public function test_price_is_shown_with_iva_included_and_split_in_the_quote(): void
    {
        $e = $this->event();
        $q = app(EventSales::class)->quote($e, 3);
        $this->assertSame(5220.0, $q['total']);
        $this->assertSame(720.0, $q['iva']);
        $this->assertSame(4500.0, $q['base']);
        $this->get($e->url())->assertSee('$1,740 MXN');
    }

    public function test_discounts_are_off_by_default_and_apply_inside_their_window(): void
    {
        $sales = app(EventSales::class);
        $e = $this->event(['discounts' => [
            'early_bird' => ['enabled' => false, 'percent' => 15, 'ends_at' => now()->addDay()->toDateString()],
            'group' => ['enabled' => true, 'percent' => 10, 'min_qty' => 4, 'label' => 'Grupo'],
        ]]);
        $this->assertSame(1740.0, $sales->quote($e, 1)['total']);
        $this->assertSame(6264.0, $sales->quote($e, 4)['total'], 'grupo de 4: -10 %');

        $e->update(['discounts' => ['early_bird' => ['enabled' => true, 'percent' => 15, 'label' => 'Preventa', 'ends_at' => now()->subDays(2)->toDateString()]]]);
        $this->assertSame(1740.0, $sales->quote($e->fresh(), 1)['total'], 'preventa vencida');
        $e->update(['discounts' => ['early_bird' => ['enabled' => true, 'percent' => 15, 'label' => 'Preventa', 'ends_at' => now()->addDay()->toDateString()]]]);
        $this->assertSame(1479.0, $sales->quote($e->fresh(), 1)['total']);
    }

    public function test_quantity_is_capped_by_the_per_order_limit_and_the_remaining_capacity(): void
    {
        $e = $this->event(['max_per_order' => 6, 'capacity' => 4]);
        $this->cartWith($e, 99);
        $this->assertSame(['p1' => 4], session('event_cart.' . $e->id));
        $e->update(['capacity' => 100]);
        $this->cartWith($e, 99);
        $this->assertSame(['p1' => 6], session('event_cart.' . $e->id));
    }

    // ---- Compra de invitado y Mercado Pago ----

    public function test_guest_checkout_creates_order_and_named_tickets_and_redirects_to_mercado_pago(): void
    {
        $this->fakeMp();
        $e = $this->event();
        $this->assertGuest();

        $this->place($e, 2, ['attendees' => ['Ana Pérez', 'Luis Gómez']])->assertRedirect('https://www.mercadopago.com.mx/checkout/v1/redirect?pref_id=PREF123');

        $order = EventOrder::where('event_id', $e->id)->firstOrFail();
        $this->assertSame('pending', $order->status);
        $this->assertSame(3480.0, (float) $order->total);
        $this->assertSame(480.0, (float) $order->iva);
        $this->assertSame(['Ana Pérez', 'Luis Gómez'], $order->tickets->pluck('attendee_name')->all());
        $this->assertNotNull($order->policies_accepted_at);
        $this->assertMatchesRegularExpression('/^AF-[A-Z2-9]{4}-[A-Z2-9]{4}$/', $order->tickets->first()->code);

        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/checkout/preferences')
            && $r['external_reference'] === 'EV-' . $order->id && (float) $r['items'][0]['unit_price'] === 3480.0 && $r['items'][0]['currency_id'] === 'MXN'
            && str_ends_with($r['notification_url'], '/mercadopago/webhook') && str_contains($r['back_urls']['success'], '/gracias?order=' . $order->code));
    }

    public function test_checkout_validates_attendees_policies_and_invoice_data(): void
    {
        $this->fakeMp();
        $e = $this->event();
        $this->cartWith($e, 2);
        $this->post($e->url('pago'), $this->buyer(['attendees' => ['Solo uno'], 'accept_policies' => null]))->assertSessionHasErrors(['attendees', 'accept_policies']);
        $this->post($e->url('pago'), $this->buyer(['attendees' => ['A', 'B'], 'wants_invoice' => 1]))->assertSessionHasErrors(['invoice.rfc', 'invoice.razon_social', 'invoice.cp']);
        $this->assertSame(0, EventOrder::where('event_id', $e->id)->count());
    }

    public function test_honeypot_field_blocks_bots_silently(): void
    {
        $this->fakeMp();
        $e = $this->event();
        $this->cartWith($e, 1);
        $this->post($e->url('pago'), $this->buyer(['website' => 'http://spam']))->assertRedirect($e->url());
        $this->assertSame(0, EventOrder::count() - EventOrder::where('event_id', '!=', $e->id)->count());
    }

    public function test_a_failed_preference_releases_the_seat_immediately(): void
    {
        $this->fakeMp([], null);
        $e = $this->event(['capacity' => 2]);
        $this->place($e, 2)->assertSessionHas('event_error');
        $this->assertSame(0, EventOrder::where('event_id', $e->id)->where('status', 'pending')->count());
        $this->assertSame(2, app(EventSales::class)->available($e));
    }

    public function test_inventory_never_oversells_and_shows_sold_out(): void
    {
        $this->fakeMp();
        $e = $this->event(['capacity' => 3]);
        $this->place($e, 2)->assertRedirect();
        $this->assertSame(1, app(EventSales::class)->available($e));

        // Otra persona: quedaba 1 lugar y pide 2.
        $this->flushSession();
        $this->cartWith($e, 2);
        $this->assertSame(['p1' => 1], session('event_cart.' . $e->id));
        $this->post($e->url('pago'), $this->buyer(['attendees' => ['A']]))->assertRedirect();
        $this->assertSame(0, app(EventSales::class)->available($e));
        $this->assertSame('sold_out', app(EventSales::class)->state($e));

        $this->get($e->url())->assertSee('Agotado')->assertSee('SoldOut', false)->assertDontSee('Agregar al carrito');
    }

    public function test_stale_pending_orders_free_their_seats(): void
    {
        $this->fakeMp();
        $e = $this->event(['capacity' => 2]);
        $this->place($e, 2);
        $this->assertSame(0, app(EventSales::class)->available($e));
        EventOrder::where('event_id', $e->id)->update(['expires_at' => now()->subMinute()]);
        $this->assertSame(2, app(EventSales::class)->available($e), 'una reserva vencida ya no cuenta');
        $this->assertSame(1, app(EventSales::class)->expireStale());
        $this->assertSame('expired', EventOrder::where('event_id', $e->id)->first()->status);
    }

    public function test_approved_payment_confirms_order_sends_tickets_once_and_is_idempotent(): void
    {
        Mail::fake();
        $this->fakeMp();
        $e = $this->event();
        $this->place($e, 2);
        $order = EventOrder::where('event_id', $e->id)->firstOrFail();

        $payment = ['id' => 555001, 'status' => 'approved', 'external_reference' => 'EV-' . $order->id, 'transaction_amount' => 3480.0, 'currency_id' => 'MXN', 'payment_type_id' => 'credit_card'];
        $this->fakeMp($payment);
        $this->webhook('555001')->assertOk()->assertJson(['message' => 'complete']);
        $this->webhook('555001')->assertOk();

        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertSame('555001', $order->payment_id);
        $this->assertNotNull($order->paid_at);
        Mail::assertQueuedCount(1);
        Mail::assertQueued(EventTicketsEmail::class, fn ($m) => $m->hasTo('ana@example.com'));
    }

    public function test_payment_with_the_wrong_amount_is_ignored(): void
    {
        Mail::fake();
        $this->fakeMp();
        $e = $this->event();
        $this->place($e, 2);
        $order = EventOrder::where('event_id', $e->id)->firstOrFail();

        $this->fakeMp(['id' => 555002, 'status' => 'approved', 'external_reference' => 'EV-' . $order->id, 'transaction_amount' => 10.0, 'currency_id' => 'MXN']);
        $this->webhook('555002')->assertOk()->assertJson(['message' => 'ignored']);
        $this->assertSame('pending', $order->fresh()->status);
        Mail::assertNothingQueued();
    }

    public function test_pending_cash_payment_extends_the_hold_and_refund_revokes_it(): void
    {
        $this->fakeMp();
        $e = $this->event(['offline_hold_hours' => 72]);
        $this->place($e, 1);
        $order = EventOrder::where('event_id', $e->id)->firstOrFail();

        $this->fakeMp(['id' => 555003, 'status' => 'pending', 'external_reference' => 'EV-' . $order->id, 'transaction_amount' => 1740.0, 'currency_id' => 'MXN', 'payment_type_id' => 'ticket']);
        $this->webhook('555003')->assertOk()->assertJson(['message' => 'pending']);
        $this->assertTrue($order->fresh()->expires_at->gt(now()->addHours(70)));

        Mail::fake();
        $this->fakeMp(['id' => 555003, 'status' => 'approved', 'external_reference' => 'EV-' . $order->id, 'transaction_amount' => 1740.0, 'currency_id' => 'MXN']);
        $this->webhook('555003')->assertOk();
        $this->assertSame('paid', $order->fresh()->status);

        $this->fakeMp(['id' => 555003, 'status' => 'refunded', 'external_reference' => 'EV-' . $order->id, 'transaction_amount' => 1740.0, 'currency_id' => 'MXN']);
        $this->webhook('555003')->assertOk()->assertJson(['message' => 'refunded']);
        $this->assertSame('refunded', $order->fresh()->status);
    }

    public function test_thanks_page_reads_the_payment_from_mercado_pago_and_shows_qr_tickets(): void
    {
        Mail::fake();
        $this->fakeMp();
        $e = $this->event();
        $this->place($e, 1);
        $order = EventOrder::where('event_id', $e->id)->firstOrFail();

        // Volver sin pago confirmado: nada se da por bueno desde la URL.
        $this->get($e->url('gracias') . '?order=' . $order->code . '&payment_id=999&status=approved')->assertOk()->assertSee('Tu pago está pendiente');
        $this->assertSame('pending', $order->fresh()->status);

        $this->fakeMp(['id' => 777, 'status' => 'approved', 'external_reference' => 'EV-' . $order->id, 'transaction_amount' => 1740.0, 'currency_id' => 'MXN']);
        $res = $this->get($e->url('gracias') . '?order=' . $order->code . '&payment_id=777')->assertOk()->assertSee('Tu lugar está apartado')->assertSee('noindex', false);
        $code = $order->tickets()->first()->code;
        $res->assertSee($code);

        $png = $this->get($e->url('boleto/' . $code . '.png'))->assertOk();
        $this->assertSame('image/png', $png->headers->get('Content-Type'));
        $this->get($e->url('gracias') . '?order=zzz')->assertRedirect($e->url());
    }

    public function test_qr_is_only_served_for_paid_tickets(): void
    {
        $e = $this->event();
        $order = EventOrder::create(['event_id' => $e->id, 'code' => strtolower((string) Str::ulid()), 'name' => 'X', 'email' => 'x@example.com', 'phone' => '5555555555', 'quantity' => 1, 'unit_price' => 1740, 'total' => 1740, 'iva' => 240, 'status' => 'pending']);
        $t = EventTicket::create(['event_id' => $e->id, 'event_order_id' => $order->id, 'attendee_name' => 'X', 'code' => 'AF-AAAA-BBBB']);
        $this->get($e->url('boleto/AF-AAAA-BBBB.png'))->assertNotFound();
        $order->update(['status' => 'paid']);
        $this->get($e->url('boleto/AF-AAAA-BBBB.png'))->assertOk();
    }

    public function test_confirmation_email_has_the_event_design_and_an_inline_qr(): void
    {
        $e = $this->event(['contact_email' => 'hola@astrofest.test']);
        $order = EventOrder::create(['event_id' => $e->id, 'code' => strtolower((string) Str::ulid()), 'name' => 'Ana Pérez', 'email' => 'ana@example.com', 'phone' => '5555555555', 'quantity' => 1, 'unit_price' => 1740, 'total' => 1740, 'iva' => 240, 'status' => 'paid', 'payment_id' => '1']);
        EventTicket::create(['event_id' => $e->id, 'event_order_id' => $order->id, 'attendee_name' => 'Ana Pérez', 'code' => 'AF-QQQQ-WWWW']);

        $mail = new EventTicketsEmail($order->fresh(['event', 'tickets']));
        $mail->assertSeeInHtml('AF-QQQQ-WWWW');
        $mail->assertSeeInHtml('Ana Pérez');
        $mail->assertSeeInHtml('$240');
        $mail->assertDontSeeInHtml('garantía');
        $mail->assertDontSeeInHtml('curso');
        $this->assertSame(['hola@astrofest.test'], collect($mail->envelope()->replyTo)->map->address->all());
    }

    public function test_noindex_switch_hides_the_event_from_search_engines_and_the_sitemap(): void
    {
        $e = $this->event(['content' => ['noindex' => true]]);
        $this->get($e->url())->assertOk()->assertSee('noindex, nofollow', false);
        $this->get($e->url('politicas'))->assertOk()->assertSee('noindex, nofollow', false);
        $sitemap = $this->get('/sitemap.xml')->getContent();
        $this->assertStringNotContainsString($e->url(), $sitemap);

        $e->update(['content' => ['noindex' => false]]);
        $this->get($e->url())->assertSee('index, follow', false);
        $this->assertStringContainsString($e->url(), $this->get('/sitemap.xml')->getContent());
    }

    public function test_finished_event_shows_edition_finished_and_does_not_sell(): void
    {
        $e = $this->event(['status' => Event::FINISHED]);
        $this->get($e->url())->assertOk()->assertSee('Edición finalizada')->assertDontSee('Agregar al carrito');
        $this->assertSame('finished', app(EventSales::class)->state($e));
    }

    public function test_rooms_block_has_no_hotel_buttons_and_can_be_hidden(): void
    {
        $e = $this->event(['hotel_booking_url' => 'https://hotel.test/reservas', 'hotel_phone' => '+52 55 0000 0000']);
        $html = $this->get($e->url())->assertOk()->getContent();
        $this->assertStringContainsString('¿Prefieres habitación?', $html);
        $this->assertStringContainsString('recibirás un correo de confirmación con un código', $html);
        foreach (['Reservar habitación en el hotel', 'hotel.test', 'click_reserve_room', 'Llamar:'] as $gone) {
            $this->assertStringNotContainsString($gone, $html);
        }
        $this->assertStringNotContainsString('Abrir en Google Maps', $html, 'el mapa sigue oculto sin enlace');

        $e->update(['content' => ['hide' => ['rooms' => true]]]);
        $this->get($e->url())->assertDontSee('¿Prefieres habitación?');
    }

    public function test_confirmation_email_carries_the_hotel_code_when_set(): void
    {
        $e = $this->event(['content' => ['text' => ['rooms' => ['code' => 'ASTRO26']]]]);
        $order = EventOrder::create(['event_id' => $e->id, 'code' => strtolower((string) Str::ulid()), 'name' => 'Ana', 'email' => 'a@example.com', 'phone' => '5555555555', 'quantity' => 1, 'unit_price' => 1740, 'total' => 1740, 'iva' => 240, 'status' => 'paid']);
        EventTicket::create(['event_id' => $e->id, 'event_order_id' => $order->id, 'attendee_name' => 'Ana', 'code' => 'AF-HOTE-L123']);
        $mail = new EventTicketsEmail($order->fresh(['event', 'tickets']));
        $mail->assertSeeInHtml('¿Prefieres habitación?');
        $mail->assertSeeInHtml('ASTRO26');

        $e->update(['content' => []]);
        (new EventTicketsEmail($order->fresh(['event', 'tickets'])))->assertSeeInHtml('En breve te compartiremos');
    }

    public function test_event_footer_shows_the_official_social_networks(): void
    {
        $html = $this->get($this->event()->url())->assertOk()->getContent();
        foreach (['facebook.com/somoshorizontia', 'instagram.com/somoshorizontia', 'youtube.com/@SomosHorizontia', 'linkedin.com/company/somoshorizontia'] as $u) {
            $this->assertStringContainsString($u, $html);
        }
        $this->assertStringContainsString('aria-label="Instagram de Horizontia"', $html);
    }

    // ---- Admin ----

    public function test_admin_exports_the_orders_csv_and_non_admins_cannot(): void
    {
        $e = $this->event();
        $order = EventOrder::create(['event_id' => $e->id, 'code' => strtolower((string) Str::ulid()), 'name' => '=HYPERLINK("x")', 'email' => 'a@example.com', 'phone' => '5555555555', 'quantity' => 1, 'unit_price' => 1740, 'total' => 1740, 'iva' => 240, 'status' => 'paid', 'utm' => ['utm_source' => 'instagram']]);
        EventTicket::create(['event_id' => $e->id, 'event_order_id' => $order->id, 'attendee_name' => 'Ana', 'code' => 'AF-CSV1-CSV2']);

        $this->actingAs($this->student())->get(route('admin.events.export', $e))->assertForbidden();

        $res = $this->actingAs($this->admin())->get(route('admin.events.export', $e))->assertOk();
        $csv = $res->streamedContent();
        $this->assertStringContainsString('AF-CSV1-CSV2', $csv);
        $this->assertStringContainsString('instagram', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv, 'neutraliza fórmulas');
    }

    public function test_admin_screen_lists_and_saves_an_event(): void
    {
        $e = $this->event();
        $this->actingAs($this->admin());
        $this->get(route('admin.events'))->assertOk()->assertSee($e->name);

        \Livewire\Livewire::test(\App\Livewire\Pages\Admin\Events\Events::class)
            ->call('edit', $e->id)
            ->set('f.price', '2000')->set('f.capacity', '150')->set('f.contact_email', 'evento@example.com')
            ->set('d.group.enabled', true)->set('d.group.percent', '10')
            ->set('t.hero.title', 'Titular nuevo')->set('hide.rooms', true)
            ->call('save')->assertHasNoErrors();

        $e->refresh();
        $this->assertSame(2000.0, (float) $e->price);
        $this->assertSame(150, $e->capacity);
        $this->assertSame('Titular nuevo', $e->text('hero.title'));
        $this->assertFalse($e->show('rooms'));
        $this->assertTrue($e->discounts['group']['enabled']);
    }

    public function test_admin_orders_screen_lists_paid_purchases_and_non_admins_cannot_open_it(): void
    {
        $e = $this->event();
        EventOrder::create(['event_id' => $e->id, 'code' => strtolower((string) Str::ulid()), 'name' => 'Compradora Pagada', 'email' => 'paga@example.com', 'phone' => '5555555555', 'quantity' => 1, 'unit_price' => 1740, 'total' => 1740, 'iva' => 240, 'status' => 'paid']);
        EventOrder::create(['event_id' => $e->id, 'code' => strtolower((string) Str::ulid()), 'name' => 'Compradora Pendiente', 'email' => 'pendiente@example.com', 'phone' => '5555555555', 'quantity' => 1, 'unit_price' => 1740, 'total' => 1740, 'iva' => 240, 'status' => 'pending', 'expires_at' => now()->addMinutes(30)]);

        $this->actingAs($this->admin());
        $this->get(route('admin.events.orders'))->assertOk()->assertSee('Compradora Pagada')->assertDontSee('Compradora Pendiente');

        \Livewire\Livewire::test(\App\Livewire\Pages\Admin\Events\EventOrders::class)
            ->assertSee('Compradora Pagada')
            ->set('orderStatus', 'pending')
            ->assertSee('Compradora Pendiente')->assertDontSee('Compradora Pagada');

        \Livewire\Livewire::actingAs($this->student())->test(\App\Livewire\Pages\Admin\Events\EventOrders::class)->assertForbidden();
        $this->actingAs($this->student())->get(route('admin.events.orders'))->assertForbidden();
    }

    public function test_admin_events_screen_is_forbidden_to_non_admins(): void
    {
        \Livewire\Livewire::actingAs($this->student())->test(\App\Livewire\Pages\Admin\Events\Events::class)->assertForbidden();
        $this->actingAs($this->student())->get(route('admin.events'))->assertForbidden();
    }

    public function test_landing_uses_only_images_that_exist_and_credits_the_free_license_photos(): void
    {
        $e = $this->event();
        $html = $this->get($e->url())->assertOk()->getContent();

        preg_match_all('#/images/eventos/[\w\-/]+\.(?:webp|png|svg|jpg)#', $html, $m);
        $this->assertNotEmpty($m[0]);
        foreach (array_unique($m[0]) as $path) {
            $this->assertFileExists(public_path(ltrim($path, '/')), "falta la imagen {$path}");
        }
        $this->assertStringContainsString('Wikimedia Commons', $html);
        $this->assertStringContainsString('Wikimedia', $html);
        $this->assertStringContainsString('imagen ilustrativa', $html);
    }

    public function test_landing_has_the_reviewed_sections_and_accessible_tabs(): void
    {
        $html = $this->get($this->event()->url())->assertOk()->getContent();

        foreach (['role="tablist"', 'Compra tranquilo', 'Ciencia en un minuto', 'Lo que verás en la noche', 'Imprimir lista', 'Distintivo de Astrofest, rally y', 'Antes de ir', 'Pago seguro con Mercado Pago', 'id="evBar"'] as $needle) {
            $this->assertStringContainsString($needle, $html, "falta: $needle");
        }
        $this->assertSame(1, substr_count($html, '<h1'), 'un solo H1');
    }

    public function test_calendar_file_has_the_event_dates_in_utc(): void
    {
        $e = $this->event(['start_at' => '2026-12-12 21:00:00', 'end_at' => '2026-12-13 19:00:00', 'venue_address' => 'Autopista México–Tuxpan, km 27']);
        $res = $this->get($e->url('evento.ics'))->assertOk();
        $this->assertStringContainsString('text/calendar', $res->headers->get('Content-Type'));
        $ics = str_replace("\r\n ", '', $res->getContent());
        $this->assertStringContainsString('DTSTART:20261212T210000Z', $ics);
        $this->assertStringContainsString('DTEND:20261213T190000Z', $ics);
        $this->assertStringContainsString('SUMMARY:Astrofest® Prueba', $ics);
        $this->assertStringContainsString('LOCATION:Club Campestre\, Autopista México–Tuxpan\, km 27', $ics);
    }

    public function test_checkout_shows_the_steps_and_keeps_optional_questions_collapsed(): void
    {
        $e = $this->event();
        $this->cartWith($e, 2);
        $html = $this->get($e->url('pago'))->assertOk()->getContent();
        $this->assertStringContainsString('aria-label="Pasos de la compra"', $html);
        $this->assertStringContainsString('Datos opcionales', $html);
        $this->assertStringContainsString('Resumen: 2 personas', $html);
    }

    public function test_event_pages_use_the_horizontia_favicon(): void
    {
        $e = $this->event();
        $html = $this->get($e->url())->assertOk()->getContent();
        $this->assertStringContainsString('images/favicon/favicon-32.png', $html);
        $this->assertStringContainsString('favicon.ico', $html);
        foreach (['favicon-16.png', 'favicon-32.png', 'favicon-192.png', 'apple-touch-icon.png'] as $f) {
            $this->assertFileExists(public_path('images/favicon/' . $f));
        }
        $this->assertGreaterThan(1000, filesize(public_path('favicon.ico')));
    }

    public function test_too_many_attempts_keep_the_buyer_inside_the_event_page_and_buckets_are_independent(): void
    {
        $e = $this->event();
        $this->cartWith($e, 1);
        // 30 «agregar al carrito» no gastan el contador del pago (cada ruta tiene el suyo).
        for ($i = 0; $i < 25; $i++) {
            $this->cartWith($e, 1);
        }
        $this->post($e->url('pago'), $this->buyer(['attendees' => ['A'], 'accept_policies' => null]))->assertSessionHasErrors('accept_policies');

        // El intento 13 del pago en un minuto no muestra el 429 del sitio: regresa al evento con un aviso.
        for ($i = 0; $i < 11; $i++) {
            $this->post($e->url('pago'), $this->buyer(['attendees' => ['A'], 'accept_policies' => null]));
        }
        $res = $this->from($e->url('pago'))->post($e->url('pago'), $this->buyer(['attendees' => ['A'], 'accept_policies' => null]));
        $res->assertRedirect($e->url('pago'))->assertSessionHas('event_error');
        $this->assertStringContainsString('Demasiados intentos', session('event_error'));
    }
}
