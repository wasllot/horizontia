<?php

namespace Tests\Feature;

use App\Mail\EventHotelCodeEmail;
use App\Mail\EventTicketsEmail;
use App\Models\Event;
use App\Services\Events\EventSales;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * Los correos siguen los dos modelos que mandó el cliente («Modelo correo para participante» y «Modelo correo para hotel», 11 nov):
 * banner, «Confirmación de registro», clave de confirmación, paquete, personas registradas, fechas, recomendaciones, habitación por
 * WhatsApp y cierre con la mascota.
 */
class EventEmailModelTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        Event::query()->update(['redirect_events_index' => false, 'status' => Event::DRAFT]);
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function event(array $override = []): Event
    {
        return Event::create(array_merge([
            'slug' => 'modelo-' . Str::lower(Str::random(6)), 'name' => 'Astrofest Gemínidas 2026', 'status' => Event::PUBLISHED,
            'start_at' => now()->addMonths(2), 'end_at' => now()->addMonths(2)->addDay(), 'venue_name' => 'Club Campestre Teotihuacán', 'venue_city' => 'Estado de México',
            'price' => 1300, 'packages' => [['key' => 'p2', 'name' => '2 personas', 'persons' => 2, 'price' => 2100, 'active' => true], ['key' => 'p1', 'name' => '1 persona', 'persons' => 1, 'price' => 1300, 'active' => true]],
            'iva_rate' => 16, 'max_per_order' => 10, 'sales_enabled' => true, 'hold_minutes' => 45, 'offline_hold_hours' => 72,
            'policies_html' => '<p>Política.</p>', 'hotel_whatsapp' => '+52 55 4766 8637', 'hotel_email' => 'hotel@example.com', 'contact_email' => 'contacto@example.com',
        ], $override));
    }

    private function paid(Event $e, array $items = ['p2' => 1]): \App\Models\EventOrder
    {
        Mail::fake();
        $sales = app(EventSales::class);
        $persons = $sales->persons($e, $items);
        $order = $sales->createOrder($e, ['name' => 'Oscar Alejandro Cota', 'email' => 'oscar@example.com', 'phone' => '4772918443', 'items' => $items,
            'attendees' => array_map(fn ($i) => 'Asistente ' . ($i + 1), range(0, $persons - 1))]);

        return $sales->markPaid($order, 'PAY-' . Str::random(6), 'credit_card');
    }

    private function text(string $html): string
    {
        $html = preg_replace(['#<(script|style)\b.*?</\1>#si', '#<br\s*/?>#i'], ' ', $html);

        return html_entity_decode(preg_replace('/\s+/', ' ', strip_tags($html)));
    }

    // ================= Fechas y WhatsApp =================

    public function test_dates_are_written_the_way_the_client_wrote_them_and_in_the_events_timezone(): void
    {
        $mk = fn (string $start, ?string $end) => new Event(['start_at' => Carbon::parse($start, 'UTC'), 'end_at' => $end ? Carbon::parse($end, 'UTC') : null]);

        $two = $mk('2026-12-12 21:00:00', '2026-12-13 19:00:00');
        $this->assertSame('sábado 12 y domingo 13 de diciembre de 2026', $two->datesLabel());
        $this->assertSame('el 12 y 13 de diciembre', $two->closingDatesLabel());

        $three = $mk('2026-12-11 21:00:00', '2026-12-13 19:00:00');
        $this->assertSame('del viernes 11 al domingo 13 de diciembre de 2026', $three->datesLabel());
        $this->assertSame('del 11 al 13 de diciembre', $three->closingDatesLabel());

        $one = $mk('2026-12-12 21:00:00', '2026-12-12 23:00:00');
        $this->assertSame('sábado 12 de diciembre de 2026', $one->datesLabel());
        $this->assertSame('el 12 de diciembre', $one->closingDatesLabel());

        $months = $mk('2026-12-31 21:00:00', '2027-01-01 19:00:00');
        $this->assertSame('jueves 31 de diciembre de 2026 y viernes 1 de enero de 2027', $months->datesLabel());
        $this->assertSame('del 31 de diciembre al 1 de enero', $months->closingDatesLabel());

        $late = $mk('2026-12-13 03:00:00', null);   // 03:00 UTC del 13 = 21:00 del 12 en México
        $this->assertSame('sábado 12 de diciembre de 2026', $late->datesLabel(), 'se cuenta en la zona del evento, no en UTC');

        $this->assertNull((new Event())->datesLabel());
        $this->assertNull((new Event())->closingDatesLabel());
    }

    public function test_hotel_whatsapp_link_is_built_from_whatever_number_was_typed(): void
    {
        foreach (['+52 55 4766 8637', '52 55 4766 8637', '(55) 4766-8637', '55 4766 8637'] as $typed) {
            $this->assertSame('https://wa.me/525547668637', (new Event(['hotel_whatsapp' => $typed]))->hotelWhatsappUrl(), $typed);
        }
        $this->assertNull((new Event(['hotel_whatsapp' => '']))->hotelWhatsappUrl());
        $this->assertNull((new Event(['hotel_whatsapp' => '12345']))->hotelWhatsappUrl());
        $this->assertNull((new Event())->hotelWhatsappUrl());
    }

    // ================= Correo del comprador =================

    public function test_buyer_email_follows_the_participant_model_in_order(): void
    {
        $e = $this->event();
        $order = $this->paid($e);
        $html = (new EventTicketsEmail($order->fresh(['event', 'tickets'])))->render();
        $text = $this->text($html);

        $ordered = [
            'Confirmación de registro', 'Listo, Oscar', 'Tu lugar en Astrofest Gemínidas 2026 está apartado.', 'Clave de confirmación: ' . $order->hotel_code,
            'Paquete: Espacio para 2 personas', 'Personas registradas: 2', 'Fechas: ' . $e->datesLabel(),
            'Entrada 1 de 2', 'PARA QUE LO DISFRUTES AL MÁXIMO', 'A continuación, te damos algunas recomendaciones:',
            'Trae ropa abrigadora.', 'Tu casa de campaña y sleeping bag.', 'Lámpara de luz roja.', 'Silla o tapete.',
            'Si tienes telescopio, tráelo; si no, en el evento podrás observar con los nuestros.', 'Binoculares.',
            '¿PREFIERES HABITACIÓN?', 'Si tienes interés en hacer cambio para una habitación en el hotel, comunícate por WhatsApp al siguiente número: +52 55 4766 8637',
            'y comparte tu clave de confirmación (' . $order->hotel_code . ') para que te identifiquen y te ofrezcan un precio especial.',
            '¡GRACIAS POR MIRAR HACIA ARRIBA!', '¡Nos vemos ' . $e->closingDatesLabel() . '!',
        ];
        $at = -1;
        foreach ($ordered as $needle) {
            $pos = mb_strpos($text, $needle);
            $this->assertNotFalse($pos, 'falta en el correo: ' . $needle);
            $this->assertGreaterThan($at, $pos, 'fuera de orden: ' . $needle);
            $at = $pos;
        }
        $this->assertStringContainsString('href="https://wa.me/525547668637"', $html, 'el número del hotel es un enlace de WhatsApp');
        $this->assertStringNotContainsString('el hotel ya lo tiene registrado', $text, 'ya no se promete que el hotel tiene el código');
        $this->assertSame(2, substr_count($html, 'Clave de acceso'), 'se conserva un QR y una clave de acceso por persona');
    }

    public function test_buyer_email_adapts_the_hotel_contact_and_the_room_block_can_be_hidden(): void
    {
        $phone = $this->paid($this->event(['hotel_whatsapp' => null, 'hotel_phone' => '55 1111 2222']));
        $html = (new EventTicketsEmail($phone->fresh(['event', 'tickets'])))->render();
        $this->assertStringContainsString('por teléfono al siguiente número', $this->text($html));
        $this->assertStringContainsString('href="tel:5511112222"', $html);

        $none = $this->paid($this->event(['hotel_whatsapp' => null, 'hotel_phone' => null]));
        $this->assertStringContainsString('directamente con el hotel', $this->text((new EventTicketsEmail($none->fresh(['event', 'tickets'])))->render()));

        $hidden = $this->paid($this->event(['content' => ['hide' => ['rooms' => true]]]));
        $out = $this->text((new EventTicketsEmail($hidden->fresh(['event', 'tickets'])))->render());
        $this->assertStringNotContainsString('PREFIERES HABITACIÓN', $out);
        $this->assertStringContainsString('Clave de confirmación: ' . $hidden->hotel_code, $out, 'la clave es del registro: sale aunque no haya sección de hotel');
    }

    public function test_buyer_email_with_several_packages_lists_them_instead_of_one_space(): void
    {
        $order = $this->paid($this->event(), ['p2' => 2, 'p1' => 1]);
        $out = $this->text((new EventTicketsEmail($order->fresh(['event', 'tickets'])))->render());
        $this->assertStringContainsString('Paquete: 2 × 2 personas + 1 × 1 persona', $out);
        $this->assertStringContainsString('Personas registradas: 5', $out);
    }

    // ================= Correo del hotel =================

    public function test_hotel_email_follows_the_hotel_model(): void
    {
        $e = $this->event();
        $order = $this->paid($e);
        $mail = new EventHotelCodeEmail($order->fresh(['event', 'tickets']));
        $html = $mail->render();
        $text = $this->text($html);

        $at = -1;
        foreach (['Confirmación de registro de participante', 'Oscar Alejandro Cota', 'oscar@example.com', '4772918443', 'Clave de confirmación: ' . $order->hotel_code,
            'Paquete: Espacio para 2 personas', 'Personas registradas: 2', 'Fechas: ' . $e->datesLabel(), 'Asistentes: Asistente 1, Asistente 2'] as $needle) {
            $pos = mb_strpos($text, $needle);
            $this->assertNotFalse($pos, 'falta en el correo al hotel: ' . $needle);
            $this->assertGreaterThan($at, $pos, 'fuera de orden: ' . $needle);
            $at = $pos;
        }
        $this->assertStringContainsString('alt="QR ' . $order->hotel_code . '"', $html, 'lleva el QR de la clave');
        $this->assertStringContainsString('Confirmación de registro ' . $order->hotel_code, $mail->envelope()->subject);
        $this->assertStringNotContainsString('Código de reservación', $text);
    }

    // ================= Página de confirmación y panel =================

    public function test_thanks_page_and_panel_show_the_confirmation_key(): void
    {
        $e = $this->event();
        $order = $this->paid($e);

        $text = $this->text($this->get($e->url('gracias') . '?order=' . $order->code)->assertOk()->getContent());
        $this->assertStringContainsString('Tu clave de confirmación ' . $order->hotel_code, $text);
        $this->assertStringContainsString('Espacio para 2 personas · 2 personas registradas', $text);
        $this->assertStringContainsString('¿Prefieres habitación?', $text);
        $this->assertStringContainsString('+52 55 4766 8637', $text);
        $this->assertStringContainsString('comparte tu clave de confirmación', $text);

        $this->actingAs($this->admin());
        $html = Livewire::test(\App\Livewire\Pages\Admin\Events\EventOrders::class, ['eventId' => $e->id])->html();
        $this->assertStringContainsString('Clave:', $html);
        $this->assertStringContainsString($order->hotel_code, $html);
        $this->assertStringNotContainsString('Hotel: <b>', $html);
    }

    public function test_resending_tickets_of_an_older_order_without_key_gives_it_one(): void
    {
        $e = $this->event();
        $order = $this->paid($e);
        \App\Models\EventOrder::where('id', $order->id)->update(['hotel_code' => null]);   // compra de antes de las claves
        $this->assertNull($order->fresh()->confirmationKey());

        $order = $order->fresh();
        app(EventSales::class)->sendConfirmationNow($order);

        $this->assertMatchesRegularExpression('/^QR-[A-Z2-9]{4}-[A-Z2-9]{4}$/', (string) $order->fresh()->confirmationKey());
        Mail::assertSent(EventTicketsEmail::class, fn ($m) => $m->hasTo('oscar@example.com') && str_contains($m->render(), (string) $order->fresh()->confirmationKey()));
    }
}
