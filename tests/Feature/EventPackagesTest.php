<?php

namespace Tests\Feature;

use App\Mail\EventHotelCodeEmail;
use App\Mail\EventHotelSummaryEmail;
use App\Mail\EventTicketsEmail;
use App\Models\Event;
use App\Models\EventOrder;
use App\Models\EventTicket;
use App\Services\Events\EventHotelNotifier;
use App\Services\Events\EventSales;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Larabuild\Optionbuilder\Facades\Settings;
use Livewire\Livewire;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Paquetes por número de personas (varios en una compra), código y aviso al hotel, y los textos del documento de ajustes de Astrofest. */
class EventPackagesTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    private const PACKAGES = [
        ['key' => 'p1', 'name' => '1 persona', 'persons' => 1, 'price' => 1300, 'active' => true],
        ['key' => 'p2', 'name' => '2 personas', 'persons' => 2, 'price' => 2100, 'active' => true],
        ['key' => 'p3', 'name' => '3 personas', 'persons' => 3, 'price' => 2900, 'active' => true],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        Event::query()->update(['redirect_events_index' => false, 'status' => Event::DRAFT]);
        $methods = setting('admin_settings.payment_method') ?: [];
        $methods['mercadopago'] = ['status' => 'on', 'currency' => 'MXN', 'exchange_rate' => '', 'enable_test_mode' => '', 'binary_mode' => '',
            'mp_access_token' => 'TEST-token', 'mp_public_key' => 'TEST-pk', 'mp_webhook_secret' => 'pk-secret'];
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
            'slug' => 'astro-' . Str::lower(Str::random(6)), 'name' => 'Astrofest Prueba', 'status' => Event::PUBLISHED,
            'start_at' => now()->addMonths(2), 'end_at' => now()->addMonths(2)->addDay(), 'venue_name' => 'Club Campestre', 'venue_city' => 'Estado de México',
            'price' => 1300, 'packages' => self::PACKAGES, 'iva_rate' => 16, 'max_per_order' => 10, 'sales_enabled' => true, 'hold_minutes' => 45, 'offline_hold_hours' => 72,
            'policies_html' => '<p>Política de clima y cancelación.</p>',
        ], $override));
    }

    private function buyer(int $persons, array $override = []): array
    {
        return array_merge(['name' => 'Ana Pérez', 'email' => 'ana@example.com', 'phone' => '55 1234 5678', 'accept_policies' => 1,
            'attendees' => array_map(fn ($i) => 'Asistente ' . ($i + 1), range(0, $persons - 1))], $override);
    }

    private function fakeMp(): void
    {
        Http::swap(new HttpFactory());
        Http::fake(['api.mercadopago.com/checkout/preferences' => Http::response(['id' => 'PREF1', 'init_point' => 'https://www.mercadopago.com.mx/checkout/v1/redirect?pref_id=PREF1'], 201)]);
    }

    /** Compra pendiente creada por el servicio (sin pasar por HTTP). */
    private function order(Event $e, array $items, array $buyer = []): EventOrder
    {
        $persons = app(EventSales::class)->persons($e, $items);

        return app(EventSales::class)->createOrder($e, array_merge($this->buyer($persons), ['items' => $items], $buyer));
    }

    private function paid(Event $e, array $items = ['p2' => 1], array $buyer = []): EventOrder
    {
        return app(EventSales::class)->markPaid($this->order($e, $items, $buyer), 'PAY-' . Str::random(6), 'credit_card');
    }

    /** Texto que lee una persona: sin scripts, estilos ni etiquetas. */
    private function visibleText(string $html): string
    {
        return html_entity_decode(strip_tags(preg_replace('#<(script|style)\b.*?</\1>#si', ' ', $html)));
    }

    // ================= Paquetes =================

    public function test_package_list_is_normalised_sorted_and_falls_back_to_a_single_ticket(): void
    {
        $e = $this->event(['packages' => [
            ['key' => 'x', 'name' => '', 'persons' => 3, 'price' => 2900, 'active' => true],
            ['key' => 'x', 'name' => 'Pareja', 'persons' => 2, 'price' => 2100, 'active' => true],
            ['key' => 'x', 'name' => 'Apagado', 'persons' => 4, 'price' => 3500, 'active' => false],
            ['key' => 'x', 'name' => 'Gratis', 'persons' => 5, 'price' => 0, 'active' => true],
        ]]);
        $list = $e->packageList();
        $this->assertSame(['p2', 'p3'], array_column($list, 'key'), 'solo paquetes vigentes con precio, del más chico al más grande');
        $this->assertSame('Pareja', $list[0]['name']);
        $this->assertSame('3 personas', $list[1]['name'], 'sin nombre se llama «N personas»');
        $this->assertSame(2100.0, $e->startingPrice());

        $legacy = $this->event(['packages' => null, 'price' => 1740]);
        $this->assertSame([['key' => 'p1', 'name' => 'Entrada individual', 'persons' => 1, 'price' => 1740.0]], $legacy->packageList());
        $this->assertFalse($legacy->sellsPackages());
        $this->assertTrue($e->sellsPackages());
        $this->assertFalse($this->event(['packages' => null, 'price' => 0])->hasPrice());
        $this->assertTrue($this->event(['price' => 0])->hasPrice(), 'con paquetes no hace falta precio individual');
    }

    public function test_quote_adds_every_package_and_counts_persons_like_the_flyer(): void
    {
        $sales = app(EventSales::class);
        $e = $this->event();

        $four = $sales->quote($e, ['p2' => 2]);
        $this->assertSame(4200.0, $four['total'], 'flyer: 4 personas = 2 paquetes de 2 personas');
        $this->assertSame(4, $four['persons']);
        $this->assertSame(1050.0, $four['total'] / $four['persons'], '$1,050 por persona');

        $mixed = $sales->quote($e, ['p2' => 2, 'p1' => 1]);
        $this->assertSame(5500.0, $mixed['total'], 'dos de 2 personas y uno de 1');
        $this->assertSame(5, $mixed['persons']);
        $this->assertCount(2, $mixed['lines']);
        $this->assertSame(['p1', 'p2'], collect($mixed['lines'])->pluck('key')->sort()->values()->all());
        $this->assertSame(round(5500 * 16 / 116, 2), $mixed['iva'], 'IVA contenido en el total');

        $this->assertSame(['p3' => 2], $sales->selection($e, ['p3' => 2, 'p9' => 4, 'p1' => 0, 'p2' => -1]), 'ignora paquetes que no existen y cantidades vacías');
        $this->assertSame(['p1' => 3], $sales->selection($e, 3), 'un número suelto = entradas individuales');
    }

    public function test_group_discount_counts_persons_not_packages(): void
    {
        $e = $this->event(['discounts' => ['group' => ['enabled' => true, 'percent' => 10, 'min_qty' => 4, 'label' => 'Grupo']]]);
        $sales = app(EventSales::class);
        $this->assertSame(2100.0, $sales->quote($e, ['p2' => 1])['total'], '2 personas: sin descuento');
        $this->assertSame(3780.0, $sales->quote($e, ['p2' => 2])['total'], '4 personas en 2 paquetes: -10 %');
    }

    public function test_landing_shows_the_package_selector_prices_and_structured_offers(): void
    {
        $html = $this->get($this->event()->url())->assertOk()->getContent();
        foreach (['name="items[p1]"', 'name="items[p2]"', 'name="items[p3]"', '$1,300', '$2,100', '$2,900', '$1,050 por persona', 'Desde $1,300 MXN', 'Elige tu paquete', '¿Son más amigos? Combina paquetes', 'Hasta 10 personas por compra'] as $needle) {
            $this->assertStringContainsString($needle, $html, "falta: $needle");
        }
        $this->assertStringContainsString('"@type": "AggregateOffer"', $html);
        $this->assertStringContainsString('"lowPrice": "1300"', $html);
        $this->assertStringContainsString('"highPrice": "2900"', $html);
        $this->assertStringContainsString('"offerCount": 3', $html);
        $this->assertStringNotContainsString('id="evQty"', $html);
    }

    public function test_cart_add_stores_several_packages_and_rejects_bad_selections(): void
    {
        $e = $this->event(['max_per_order' => 6]);

        $this->post($e->url('carrito'), ['items' => ['p2' => 2, 'p1' => 1]])->assertRedirect($e->url('carrito'));
        $this->assertSame(['p2' => 2, 'p1' => 1], session('event_cart.' . $e->id));

        $this->post($e->url('carrito'), ['items' => ['p1' => 0, 'p2' => 0]])->assertRedirect($e->url() . '#entrada')->assertSessionHas('event_error');
        $this->assertSame(['p2' => 2, 'p1' => 1], session('event_cart.' . $e->id), 'una selección vacía no borra el carrito');

        $this->post($e->url('carrito'), ['items' => ['p3' => 3]])->assertRedirect($e->url() . '#entrada');
        $this->assertStringContainsString('hasta 6 personas', session('event_error'));
        $this->assertSame(['p2' => 2, 'p1' => 1], session('event_cart.' . $e->id), 'pasarse del máximo no cambia el carrito');

        $this->post($e->url('carrito'), ['items' => ['p9' => 3, 'p3' => 1]]);
        $this->assertSame(['p3' => 1], session('event_cart.' . $e->id), 'se ignoran los paquetes que no existen');
    }

    public function test_cart_lines_can_be_changed_added_and_removed_within_the_person_limit(): void
    {
        $e = $this->event(['max_per_order' => 5]);
        $this->post($e->url('carrito'), ['items' => ['p2' => 1]]);

        $this->post($e->url('carrito/actualizar'), ['add' => 'p1']);
        $this->assertSame(['p2' => 1, 'p1' => 1], session('event_cart.' . $e->id));
        $this->post($e->url('carrito/actualizar'), ['line' => 'p2|2']);
        $this->assertSame(['p2' => 2, 'p1' => 1], session('event_cart.' . $e->id), '2×2 + 1 = 5 personas, justo el máximo');

        $this->post($e->url('carrito/actualizar'), ['add' => 'p1'])->assertSessionHas('event_error');
        $this->assertSame(['p2' => 2, 'p1' => 1], session('event_cart.' . $e->id), 'no deja pasar de 5 personas');

        $this->post($e->url('carrito/actualizar'), ['line' => 'p1|0']);
        $this->assertSame(['p2' => 2], session('event_cart.' . $e->id));
        $this->post($e->url('carrito/actualizar'), ['line' => 'p2|0']);
        $this->assertNull(session('event_cart.' . $e->id), 'sin líneas el carrito queda vacío');
    }

    public function test_a_cart_saved_before_packages_still_works(): void
    {
        $e = $this->event(['packages' => null, 'price' => 1740]);
        $this->withSession(['event_cart.' . $e->id => 3])->get($e->url('carrito'))->assertOk()->assertSee('Entrada individual')->assertSee('$5,220 MXN');
    }

    public function test_cart_and_checkout_pages_show_each_package_and_ask_a_name_per_person(): void
    {
        $e = $this->event();
        $this->post($e->url('carrito'), ['items' => ['p2' => 2, 'p1' => 1]]);

        $this->get($e->url('carrito'))->assertOk()
            ->assertSee('Tus paquetes')->assertSee('2 × 2 personas ($2,100)', false)->assertSee('1 × 1 persona ($1,300)', false)
            ->assertSee('$5,500 MXN')->assertSee('Agrega otro paquete');

        $html = $this->get($e->url('pago'))->assertOk()->getContent();
        $this->assertSame(5, substr_count($html, 'name="attendees['), 'un nombre por persona: 2×2 + 1 = 5');
        $this->assertStringContainsString('Resumen: 5 personas', $html);
        $this->assertStringContainsString('2 × 2 personas + 1 × 1 persona', $html);
        $this->assertStringContainsString('La pulsera y el diploma llevan el nombre', $html);
    }

    public function test_order_keeps_a_snapshot_of_the_packages_and_creates_one_ticket_per_person(): void
    {
        $this->fakeMp();
        $e = $this->event();
        $this->post($e->url('carrito'), ['items' => ['p2' => 2, 'p1' => 1]]);
        $this->post($e->url('pago'), $this->buyer(5))->assertRedirect('https://www.mercadopago.com.mx/checkout/v1/redirect?pref_id=PREF1');

        $order = EventOrder::where('event_id', $e->id)->firstOrFail();
        $this->assertSame(5, $order->quantity);
        $this->assertSame(5500.0, (float) $order->total);
        $this->assertSame(1100.0, (float) $order->unit_price, 'precio promedio por persona');
        $this->assertCount(5, $order->tickets);
        $this->assertSame('2 × 2 personas + 1 × 1 persona', $order->packagesLabel());
        $this->assertEqualsCanonicalizing(['p1', 'p2'], array_column($order->items, 'key'));

        // Cambiar el precio después no altera lo que pagó esta persona.
        $e->update(['packages' => [['key' => 'p2', 'name' => '2 personas', 'persons' => 2, 'price' => 9999, 'active' => true]]]);
        $this->assertSame(2100.0, (float) collect($order->fresh()->lines())->firstWhere('key', 'p2')['unit_price']);

        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/checkout/preferences') && (float) $r['items'][0]['unit_price'] === 5500.0 && str_contains($r['items'][0]['title'], '2 × 2 personas + 1 × 1 persona'));
    }

    public function test_checkout_requires_a_name_for_every_person_of_every_package(): void
    {
        $this->fakeMp();
        $e = $this->event();
        $this->post($e->url('carrito'), ['items' => ['p3' => 1]]);
        $this->post($e->url('pago'), $this->buyer(3, ['attendees' => ['Solo uno']]))->assertSessionHasErrors('attendees');
        $this->assertSame(0, EventOrder::where('event_id', $e->id)->count());
    }

    public function test_capacity_counts_persons_so_a_big_package_cannot_oversell(): void
    {
        $e = $this->event(['capacity' => 4]);
        $sales = app(EventSales::class);
        $this->assertSame(4, $sales->maxQuantity($e));
        $this->post($e->url('carrito'), ['items' => ['p3' => 2]])->assertRedirect($e->url() . '#entrada');
        $this->assertNull(session('event_cart.' . $e->id));

        $this->order($e, ['p3' => 1, 'p1' => 1]);   // 4 personas apartadas
        $this->assertSame(0, $sales->available($e));
        $this->assertSame('sold_out', $sales->state($e));
        $this->expectException(\App\Services\Events\EventSalesException::class);
        $this->order($e, ['p1' => 1]);
    }

    public function test_orders_from_before_the_packages_read_as_individual_tickets(): void
    {
        $e = $this->event(['packages' => null, 'price' => 1740]);
        $order = EventOrder::create(['event_id' => $e->id, 'code' => strtolower((string) Str::ulid()), 'name' => 'Ana', 'email' => 'a@example.com', 'phone' => '5555555555', 'quantity' => 2, 'unit_price' => 1740, 'total' => 3480, 'iva' => 480, 'status' => 'paid']);
        $this->assertSame('2 × Entrada individual', $order->packagesLabel());
        $this->assertSame(3480.0, $order->lines()[0]['subtotal']);
    }

    public function test_sticky_bar_hero_and_closing_use_the_starting_price(): void
    {
        $html = $this->get($this->event()->url())->assertOk()->getContent();
        $this->assertStringContainsString('Paquetes desde', $html);
        $this->assertSame(1, preg_match('#<div class="ev-bar".*?\$1,300 MXN#s', $html));
        $this->assertStringContainsString('Desde $1,300 MXN · IVA incluido', $html);
    }

    // ================= Código y aviso al hotel =================

    public function test_every_paid_order_gets_its_own_unique_hotel_code(): void
    {
        Mail::fake();
        $e = $this->event();
        $a = $this->paid($e);
        $b = $this->paid($e, ['p1' => 1], ['email' => 'luis@example.com']);

        $this->assertMatchesRegularExpression('/^HAB-[A-Z2-9]{4}-[A-Z2-9]{4}$/', $a->hotel_code);
        $this->assertNotSame($a->hotel_code, $b->hotel_code);
        $this->assertNull($this->order($e, ['p1' => 1])->hotel_code, 'una compra pendiente todavía no tiene código');
    }

    public function test_hotel_code_is_not_assigned_when_rooms_are_hidden_and_a_shared_code_wins(): void
    {
        Mail::fake();
        $hidden = $this->paid($this->event(['content' => ['hide' => ['rooms' => true]]]));
        $this->assertNull($hidden->hotel_code);

        $shared = $this->event(['content' => ['text' => ['rooms' => ['code' => 'ASTRO26']]]]);
        $order = $this->paid($shared);
        $this->assertNull($order->hotel_code, 'con código general no se genera uno por compra');
        $this->assertSame('ASTRO26', $order->roomCode());
    }

    public function test_hotel_gets_one_email_per_paid_order_with_the_code_and_buyer_data(): void
    {
        Mail::fake();
        $e = $this->event(['hotel_email' => 'Reservas@Hotel.test, otro@hotel.test; reservas@hotel.test', 'contact_email' => 'hola@astrofest.test']);
        $this->assertSame(['reservas@hotel.test', 'otro@hotel.test'], $e->hotelEmails(), 'normaliza y quita repetidos');

        $order = $this->paid($e, ['p2' => 1, 'p1' => 1], ['name' => 'Ana Pérez', 'phone' => '55 9999 0000']);
        Mail::assertQueued(EventHotelCodeEmail::class, 1);
        Mail::assertQueued(EventHotelCodeEmail::class, fn ($m) => $m->hasTo('reservas@hotel.test') && $m->hasTo('otro@hotel.test') && $m->hasCc('hola@astrofest.test'));

        $mail = new EventHotelCodeEmail($order->fresh(['event', 'tickets']));
        $mail->assertSeeInHtml($order->hotel_code);
        $mail->assertSeeInHtml('Ana Pérez');
        $mail->assertSeeInHtml('ana@example.com');
        $mail->assertSeeInHtml('55 9999 0000');
        $mail->assertSeeInHtml('1 × 2 personas + 1 × 1 persona');
        $mail->assertSeeInHtml('Asistente 1, Asistente 2, Asistente 3');
        $this->assertStringContainsString($order->hotel_code, $mail->envelope()->subject);

        $this->assertNotNull($order->fresh()->hotel_notified_at);
        $this->assertFalse(app(EventHotelNotifier::class)->notify($order->fresh()), 'ya se avisó: no se repite');
        Mail::assertQueued(EventHotelCodeEmail::class, 1);
    }

    public function test_hotel_is_not_notified_without_hotel_email_unpaid_orders_or_hidden_rooms(): void
    {
        Mail::fake();
        $this->paid($this->event());
        $this->paid($this->event(['hotel_email' => 'reservas@hotel.test', 'content' => ['hide' => ['rooms' => true]]]));
        $pending = $this->order($this->event(['hotel_email' => 'reservas@hotel.test']), ['p1' => 1]);
        $this->assertFalse(app(EventHotelNotifier::class)->notify($pending));
        Mail::assertNotQueued(EventHotelCodeEmail::class);
        $this->assertNull($pending->fresh()->hotel_notified_at);
    }

    public function test_a_payment_webhook_sends_the_buyer_and_the_hotel_exactly_once(): void
    {
        Mail::fake();
        $e = $this->event(['hotel_email' => 'reservas@hotel.test']);
        $order = $this->order($e, ['p2' => 1]);
        $sales = app(EventSales::class);
        $sales->markPaid($order, 'PAY-1', 'credit_card');
        $sales->markPaid($order->fresh(), 'PAY-1', 'credit_card');   // el webhook de Mercado Pago llega repetido

        Mail::assertQueued(EventTicketsEmail::class, 1);
        Mail::assertQueued(EventHotelCodeEmail::class, 1);
        $this->assertSame($order->fresh()->hotel_code, $order->fresh()->roomCode());
    }

    public function test_buyer_email_lists_the_packages_carries_the_code_and_never_says_kit(): void
    {
        Mail::fake();
        $e = $this->event(['hotel_phone' => '55 4766 8637', 'hotel_whatsapp' => '52 55 4766 8637']);
        $order = $this->paid($e, ['p2' => 2, 'p1' => 1]);

        $mail = new EventTicketsEmail($order->fresh(['event', 'tickets']));
        $mail->assertSeeInHtml('2 × 2 personas ($2,100)');
        $mail->assertSeeInHtml('1 × 1 persona ($1,300)');
        $mail->assertSeeInHtml($order->hotel_code);
        $mail->assertSeeInHtml('55 4766 8637');
        $mail->assertSeeInHtml('el hotel ya lo tiene registrado');
        $mail->assertSeeInHtml('recibe tu pulsera.');
        $html = $mail->render();
        $this->assertDoesNotMatchRegularExpression('/\bkit\b/i', $this->visibleText($html));
        $this->assertSame(5, substr_count($html, 'Clave de acceso'), 'una clave por persona');
    }

    public function test_thanks_page_shows_the_hotel_code_to_the_buyer(): void
    {
        Mail::fake();
        $e = $this->event();
        $order = $this->paid($e);
        $text = $this->visibleText($this->get($e->url('gracias') . '?order=' . $order->code)->assertOk()->getContent());
        $this->assertStringContainsString('¿Prefieres habitación?', $text);
        $this->assertStringContainsString($order->hotel_code, $text);
        $this->assertStringContainsString('1 × 2 personas', $text);
    }

    public function test_final_list_goes_to_the_hotel_once_when_sales_close_with_a_csv(): void
    {
        Mail::fake();
        $e = $this->event(['hotel_email' => 'reservas@hotel.test', 'sales_close_at' => now()->addDay()]);
        $a = $this->paid($e, ['p2' => 1], ['name' => '=CMD("x")']);
        $b = $this->paid($e, ['p1' => 1], ['email' => 'luis@example.com', 'name' => 'Luis Gómez']);
        $notifier = app(EventHotelNotifier::class);

        $this->assertSame(0, $notifier->sendDueSummaries(), 'las ventas siguen abiertas');
        $e->update(['sales_close_at' => now()->subMinute()]);
        $this->assertSame(1, $notifier->sendDueSummaries());
        $this->assertSame(0, $notifier->sendDueSummaries(), 'la lista se manda una sola vez');
        Mail::assertQueued(EventHotelSummaryEmail::class, 1);
        Mail::assertQueued(EventHotelSummaryEmail::class, fn ($m) => $m->hasTo('reservas@hotel.test') && $m->orders->count() === 2);
        $this->assertNotNull($e->fresh()->hotel_summary_sent_at);

        $csv = $notifier->csv($e->fresh());
        $this->assertStringContainsString('Código de reservación', $csv);
        $this->assertStringContainsString($a->hotel_code, $csv);
        $this->assertStringContainsString($b->hotel_code, $csv);
        $this->assertStringContainsString('luis@example.com', $csv);
        $this->assertStringContainsString("'=CMD", $csv, 'neutraliza fórmulas');

        (new EventHotelSummaryEmail($e->fresh(), $notifier->orders($e), $csv))->assertSeeInHtml('Lista final de códigos de reservación');
    }

    public function test_final_list_needs_orders_and_a_hotel_email(): void
    {
        Mail::fake();
        $notifier = app(EventHotelNotifier::class);
        $empty = $this->event(['hotel_email' => 'reservas@hotel.test', 'sales_close_at' => now()->subHour()]);
        $this->assertFalse($notifier->sendSummary($empty), 'sin compras no hay nada que enviar');
        $this->assertNull($empty->fresh()->hotel_summary_sent_at);

        $noHotel = $this->event();
        $this->paid($noHotel);
        $noHotel->update(['sales_close_at' => now()->subHour()]);
        $this->assertFalse($notifier->sendSummary($noHotel));
        Mail::assertNotQueued(EventHotelSummaryEmail::class);
    }

    public function test_the_summary_command_is_registered_and_scheduled(): void
    {
        Mail::fake();
        $e = $this->event(['hotel_email' => 'reservas@hotel.test']);
        $this->paid($e);
        $e->update(['sales_close_at' => now()->subHour()]);
        $this->artisan('app:send-event-hotel-summary')->expectsOutputToContain('1 lista enviada')->assertSuccessful();
        Mail::assertQueued(EventHotelSummaryEmail::class, 1);

        Artisan::call('schedule:list');
        $this->assertStringContainsString('app:send-event-hotel-summary', Artisan::output());
    }

    // ================= Panel de administración =================

    public function test_admin_edits_packages_hotel_and_speakers_and_the_page_follows(): void
    {
        $e = $this->event(['packages' => null, 'price' => 1740]);
        $this->actingAs($this->admin());

        Livewire::test(\App\Livewire\Pages\Admin\Events\Events::class)
            ->call('edit', $e->id)
            ->call('addPackage')->set('pk.0.persons', '1')->set('pk.0.price', '1300')
            ->call('addPackage')->set('pk.1.persons', '2')->set('pk.1.price', '2100')->set('pk.1.name', 'Pareja')
            ->set('f.hotel_email', 'Reservas@Hotel.test; otro@hotel.test')->set('f.hotel_phone', '55 4766 8637')
            ->set('t.speakers.c2', 'Dr. Ejemplo, Instituto de Pruebas')
            ->call('save')->assertHasNoErrors();

        $e->refresh();
        $this->assertSame([1, 2], array_column($e->packageList(), 'persons'));
        $this->assertSame('Pareja', $e->packageList()[1]['name']);
        $this->assertSame('reservas@hotel.test, otro@hotel.test', $e->hotel_email);
        $this->assertSame('55 4766 8637', $e->hotel_phone);
        $this->get($e->url())->assertSee('Dr. Ejemplo, Instituto de Pruebas. En el auditorio.')->assertSee('Pareja');
    }

    public function test_admin_validates_packages_and_hotel_emails(): void
    {
        $e = $this->event();
        $this->actingAs($this->admin());

        Livewire::test(\App\Livewire\Pages\Admin\Events\Events::class)
            ->call('edit', $e->id)
            ->set('pk.0.persons', '2')->set('pk.1.persons', '2')
            ->call('save')->assertHasErrors(['pk.0.persons', 'pk.1.persons'])
            ->call('edit', $e->id)
            ->set('pk.0.price', '0')->call('save')->assertHasErrors(['pk.0.price'])
            ->call('edit', $e->id)
            ->set('f.hotel_email', 'esto-no-es-un-correo')->call('save')->assertHasErrors(['f.hotel_email']);
        $this->assertSame(3, count($e->fresh()->packageList()), 'nada se guardó');

        Livewire::test(\App\Livewire\Pages\Admin\Events\Events::class)
            ->call('edit', $e->id)->call('removePackage', 2)->call('save')->assertHasNoErrors();
        $this->assertSame(['p1', 'p2'], array_column($e->fresh()->packageList(), 'key'));
    }

    public function test_admin_orders_show_packages_and_code_and_can_resend_to_the_hotel(): void
    {
        Mail::fake();
        $e = $this->event(['hotel_email' => 'reservas@hotel.test']);
        $order = $this->paid($e, ['p2' => 1, 'p1' => 1]);
        $this->actingAs($this->admin());

        $this->get(route('admin.events.orders'))->assertOk()->assertSee($order->hotel_code)->assertSee('1 × 2 personas + 1 × 1 persona')->assertSee('avisado')->assertSee('Enviar lista al hotel');

        Mail::fake();
        Livewire::test(\App\Livewire\Pages\Admin\Events\EventOrders::class)->call('resendHotel', $order->id);
        Mail::assertQueued(EventHotelCodeEmail::class, 1);
        Livewire::test(\App\Livewire\Pages\Admin\Events\EventOrders::class)->call('sendHotelList');
        Mail::assertQueued(EventHotelSummaryEmail::class, 1);

        $csv = $this->get(route('admin.events.export', $e))->assertOk()->streamedContent();
        $this->assertStringContainsString('Código de habitación', $csv);
        $this->assertStringContainsString($order->hotel_code, $csv);
        $this->assertStringContainsString('1 × 2 personas + 1 × 1 persona', $csv);
    }

    public function test_orders_screen_hides_the_hotel_list_button_when_there_is_no_hotel_email(): void
    {
        $this->paid($this->event());
        $this->actingAs($this->admin())->get(route('admin.events.orders'))->assertOk()->assertDontSee('Enviar lista al hotel');
    }

    // ================= Ajustes del documento de la reunión con Luz =================

    public function test_landing_no_longer_promises_a_kit_passport_stickers_sky_map_patch_or_shirt(): void
    {
        $text = $this->visibleText($this->get($this->event()->url())->assertOk()->getContent());
        $this->assertDoesNotMatchRegularExpression('/\bkit\b/i', $text, 'la palabra «kit» ya no aparece');
        foreach (['Pasaporte', 'pasaporte', 'stickers', 'mapa del cielo', 'parche', 'playera', 'Pide un deseo', 'deseo pedido'] as $gone) {
            $this->assertStringNotContainsString($gone, $text, "ya no debe decir: $gone");
        }
    }

    public function test_wish_button_is_gone_from_markup_script_and_styles(): void
    {
        $html = $this->get($this->event()->url())->assertOk()->getContent();
        $this->assertStringNotContainsString('evWish', $html);
        $this->assertStringNotContainsString('ev-wish', file_get_contents(public_path('css/event.css')));
    }

    public function test_collectibles_badge_rally_and_ticket_copy_follow_the_meeting_notes(): void
    {
        $text = $this->visibleText($this->get($this->event()->url())->assertOk()->getContent());
        foreach ([
            'Cada lluvia de meteoros tendrá su propia pulsera. Guárdala: será tu pase de coleccionista.',
            'primera edición de una nueva temporada de noches de estrellas',
            'Distintivo de Astrofest, rally y diploma',
            'Desde la bienvenida te contamos cómo participar',
            'Celofán rojo incluido',
            'Pulsera coleccionable, celofán rojo y diploma de participación',
            'Espacio para tu casa de campaña, según tu paquete',
            'Los encontrarás en tu diploma.',
            'Desde 2013',
            'Habitación (se reserva con el hotel)',
            'Estacionamiento: $60 por día',
        ] as $needle) {
            $this->assertStringContainsString($needle, $text, "falta: $needle");
        }
        $this->assertStringNotContainsString('Camping para 1 persona', $text);
        $this->assertDoesNotMatchRegularExpression('/ desde 2013/', $text, 'no debe quedar el año suelto');
    }

    public function test_program_is_the_new_hourly_schedule_for_saturday_and_sunday_with_the_planetarium_note(): void
    {
        $text = $this->visibleText($this->get($this->event()->url())->assertOk()->getContent());
        foreach ([
            'Recepción y registro', 'Recibe tu pulsera con tu clave de acceso', 'Arranca el planetario móvil', 'cupo de 50 personas',
            'Conferencia 1: El cielo, las constelaciones y el zodiaco', 'Conferencia 3: Cometas, asteroides y lluvias de estrellas', 'Segunda fogata y momento estelar',
            'Observación libre', 'Desayuno buffet en el restaurante Don José', 'Observación solar', 'Conferencia 4: El Sol, nuestra estrella', 'Cierre: premios del rally y de la AstroRifa',
            'Programa sujeto a cambios por clima', 'Estará dando funciones a lo largo de la noche, a partir de las 18:00 h.',
        ] as $needle) {
            $this->assertStringContainsString($needle, $text, "falta: $needle");
        }
        $this->assertStringNotContainsString('Café al amanecer', $text, 'el programa anterior fue reemplazado');
        $this->assertStringNotContainsString('Registro del evento', $text);
    }

    public function test_conference_speakers_only_appear_once_they_are_confirmed(): void
    {
        $e = $this->event();
        $text = $this->visibleText($this->get($e->url())->assertOk()->getContent());
        $this->assertStringNotContainsString('Galindo', $text);
        $this->assertStringNotContainsString('Octavio Uribe', $text);

        $e->update(['content' => ['text' => ['speakers' => ['c2' => 'Dr. Jesús Galindo, Instituto de Investigaciones Estéticas, UNAM', 'c4' => 'Lic. Octavio Uribe Fernández']]]]);
        $text = $this->visibleText($this->get($e->url())->assertOk()->getContent());
        $this->assertStringContainsString('Dr. Jesús Galindo, Instituto de Investigaciones Estéticas, UNAM. En el auditorio.', $text);
        $this->assertStringContainsString('Lic. Octavio Uribe Fernández. En el auditorio.', $text);
    }

    public function test_transport_note_and_public_transport_faq_are_present_and_editable(): void
    {
        $e = $this->event();
        $html = $this->get($e->url())->assertOk()->getContent();
        $text = $this->visibleText($html);
        $this->assertStringContainsString('Te damos las indicaciones para que llegues sin problema', $text);
        $this->assertStringContainsString('¿Cómo llego en transporte público?', $text);
        $this->assertStringContainsString('Autobuses del Norte', $text);
        $this->assertStringContainsString('«Pirámides»', $text);
        $this->assertStringContainsString('href="#preguntas"', $html);
        foreach (['Indios Verdes', '$30'] as $unverified) {
            $this->assertStringNotContainsString($unverified, $text, 'ese dato no está confirmado y no se publica');
        }

        $e->update(['content' => ['text' => ['transport' => 'Ruta confirmada por la organización.']]]);
        $this->get($e->url())->assertSee('Ruta confirmada por la organización.')->assertDontSee('Autobuses del Norte');
    }

    public function test_faq_explains_packages_arrival_and_the_hotel_code(): void
    {
        $text = $this->visibleText($this->get($this->event()->url())->assertOk()->getContent());
        foreach (['¿Puedo comprar varios paquetes?', '2 paquetes de 2 personas y 1 de 1 persona', 'La recepción y el registro abren', 'el hotel ya lo tiene registrado', 'recibe tu pulsera.'] as $needle) {
            $this->assertStringContainsString($needle, $text, "falta: $needle");
        }
        $this->assertStringContainsString('recibirás un correo de confirmación con un código', $text);
        $this->assertStringContainsString('precio especial (sujeto a disponibilidad)', $text);
    }

    public function test_promo_video_is_embedded_lazily_with_poster_and_structured_data_and_can_be_hidden(): void
    {
        $this->assertFileExists(public_path('images/eventos/astrofest-promo-poster.webp'));
        if (!is_file(public_path('videos/eventos/astrofest-promo.mp4'))) {
            $this->markTestSkipped('El MP4 no viaja en git (*.mp4 está ignorado): se sube a mano a public/videos/eventos/.');
        }
        $this->assertLessThan(20 * 1024 * 1024, filesize(public_path('videos/eventos/astrofest-promo.mp4')), 'comprimido para web');

        $e = $this->event();
        $html = $this->get($e->url())->assertOk()->getContent();
        $this->assertStringContainsString('id="video"', $html);
        $this->assertMatchesRegularExpression('#<video id="evPromo" controls playsinline preload="none" poster="[^"]+astrofest-promo-poster\.webp#', $html);
        $this->assertMatchesRegularExpression('#<source src="[^"]+astrofest-promo\.mp4\?v=\d+" type="video/mp4">#', $html);
        $this->assertStringNotContainsString('autoplay', $html, 'el video no arranca solo');
        $this->assertStringContainsString('"@type": "VideoObject"', str_replace('":"', '": "', $html));
        $this->assertStringContainsString('Así se vive', $html);

        $e->update(['content' => ['hide' => ['video' => true]]]);
        $hidden = $this->get($e->url())->assertOk()->getContent();
        $this->assertStringNotContainsString('id="evPromo"', $hidden);
        $this->assertStringNotContainsString('VideoObject', $hidden);
    }

    public function test_checkout_discloses_that_hotel_data_is_shared_only_when_there_is_a_hotel_email(): void
    {
        $with = $this->event(['hotel_email' => 'reservas@hotel.test']);
        $this->post($with->url('carrito'), ['items' => ['p1' => 1]]);
        $this->get($with->url('pago'))->assertOk()->assertSee('compartimos con el hotel tu nombre, correo, teléfono y código de reservación');

        $without = $this->event();
        $this->post($without->url('carrito'), ['items' => ['p1' => 1]]);
        $this->get($without->url('pago'))->assertOk()->assertDontSee('compartimos con el hotel');
    }

    public function test_tickets_keep_their_access_codes_and_qr_for_every_person(): void
    {
        Mail::fake();
        $order = $this->paid($this->event(), ['p3' => 1, 'p1' => 1]);
        $this->assertSame(4, EventTicket::where('event_order_id', $order->id)->count());
        $this->assertSame(4, EventTicket::where('event_order_id', $order->id)->distinct('code')->count('code'));
    }

    public function test_closing_background_is_a_starry_sky_not_the_brown_dawn_and_can_be_replaced_from_the_panel(): void
    {
        $e = $this->event();
        $html = $this->get($e->url())->assertOk()->getContent();
        $closing = substr($html, (int) strpos($html, 'class="ev-sec ev-night ev-final"'));
        $this->assertStringContainsString('stock/hero-geminids.webp', $closing);
        $this->assertStringNotContainsString('stock/amanecer.webp', $closing, 'el fondo café daba la impresión de cielo nublado');

        $e->update(['assets' => ['final_bg' => 'uploads/events/fondo-nuevo.webp']]);
        $closing = substr($this->get($e->url())->getContent(), (int) strpos($this->get($e->url())->getContent(), 'class="ev-sec ev-night ev-final"'));
        $this->assertStringContainsString('uploads/events/fondo-nuevo.webp', $closing);
        $this->assertArrayHasKey('final_bg', \App\Livewire\Pages\Admin\Events\Events::ASSETS);
    }

    public function test_without_the_mp4_the_page_shows_no_broken_player(): void
    {
        // Carpeta pública vacía (el MP4 no viaja en git y el usuario de pruebas no puede mover archivos de public/).
        $original = public_path();
        $empty = sys_get_temp_dir() . '/ev-sin-video-' . Str::random(6);
        mkdir($empty . '/build', 0777, true);
        copy($original . '/build/manifest.json', $empty . '/build/manifest.json');   // el layout lo necesita
        app()->usePublicPath($empty);
        try {
            $html = $this->get($this->event()->url())->assertOk()->getContent();
            $this->assertStringNotContainsString('id="evPromo"', $html);
            $this->assertStringNotContainsString('id="video"', $html);
            $this->assertStringNotContainsString('VideoObject', $html);
        } finally {
            app()->usePublicPath($original);
            @unlink($empty . '/build/manifest.json');
            @rmdir($empty . '/build');
            @rmdir($empty);
        }
    }
}
