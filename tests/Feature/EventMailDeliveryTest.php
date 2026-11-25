<?php

namespace Tests\Feature;

use App\Mail\EventHotelCodeEmail;
use App\Mail\EventTicketsEmail;
use App\Models\Event;
use App\Models\EventOrder;
use App\Services\Events\EventSales;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * Correos de una compra de evento: lo que pasa cuando el worker de la cola NO logra enviarlos (antes la compra seguía marcada como
 * «confirmación enviada» y nadie se enteraba) y la prueba del correo real de entradas desde Admin → Prueba de correo.
 */
class EventMailDeliveryTest extends TestCase
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

    private function event(): Event
    {
        return Event::create([
            'slug' => 'mail-' . Str::lower(Str::random(6)), 'name' => 'Evento de correo', 'status' => Event::PUBLISHED,
            'start_at' => now()->addMonths(2), 'end_at' => now()->addMonths(2)->addDay(), 'venue_name' => 'Sede', 'venue_city' => 'Estado de México',
            'price' => 10, 'packages' => [['key' => 'p1', 'name' => '1 persona', 'persons' => 1, 'price' => 10, 'active' => true]],
            'iva_rate' => 16, 'max_per_order' => 2, 'sales_enabled' => true, 'hold_minutes' => 45, 'offline_hold_hours' => 72,
            'policies_html' => '<p>Política.</p>', 'hotel_email' => 'hotel@example.com', 'contact_email' => 'contacto@example.com',
        ]);
    }

    private function paidOrder(Event $e): EventOrder
    {
        $sales = app(EventSales::class);
        $order = $sales->createOrder($e, ['name' => 'Oscar Cota', 'email' => 'oscar@cuantica.agency', 'phone' => '4772918443', 'items' => ['p1' => 1], 'attendees' => ['Oscar Cota']]);

        return $sales->markPaid($order, 'PAY-' . Str::random(6), 'credit_card');
    }

    /** SMTP que rechaza la conexión al instante (nada escucha en el puerto 1). */
    private function brokenSmtp(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.username' => null, 'mail.mailers.smtp.password' => null, 'mail.mailers.smtp.encryption' => null]);
        app('mail.manager')->forgetMailers();
    }

    public function test_a_confirmation_the_worker_could_not_send_is_unmarked_so_the_panel_and_resend_can_see_it(): void
    {
        config(['queue.default' => 'database']);
        DB::table('jobs')->delete();
        $this->brokenSmtp();
        $order = $this->paidOrder($this->event());

        $this->assertNotNull($order->fresh()->confirmation_sent_at, 'al encolar queda marcada');
        $this->assertNotNull($order->fresh()->hotel_notified_at);
        $this->assertSame(2, DB::table('jobs')->count(), 'comprador y hotel van a la cola');

        Artisan::call('queue:work', ['--stop-when-empty' => true, '--tries' => 1, '--max-time' => 20]);

        $this->assertNull($order->fresh()->confirmation_sent_at, 'el worker agotó los intentos: ya no figura como enviada');
        $this->assertNull($order->fresh()->hotel_notified_at, 'igual con el aviso al hotel');
    }

    public function test_the_orders_panel_shows_whether_the_buyer_got_the_email(): void
    {
        Mail::fake();
        $e = $this->event();
        $order = $this->paidOrder($e);
        $this->actingAs($this->admin());

        Livewire::test(\App\Livewire\Pages\Admin\Events\EventOrders::class, ['eventId' => $e->id])
            ->assertSeeHtml('data-testid="mail-ok"')
            ->assertDontSeeHtml('data-testid="mail-missing"');

        EventOrder::where('id', $order->id)->update(['confirmation_sent_at' => null]);   // el modelo en memoria no sabe de la marca: update() no escribiría nada
        Livewire::test(\App\Livewire\Pages\Admin\Events\EventOrders::class, ['eventId' => $e->id])
            ->assertSeeHtml('data-testid="mail-missing"')
            ->assertSee('SIN ENVIAR')
            ->call('resend', $order->id);

        Mail::assertSent(EventTicketsEmail::class, fn ($m) => $m->hasTo('oscar@cuantica.agency'));
        $this->assertNotNull($order->fresh()->confirmation_sent_at, 'el reenvío vuelve a marcarla como enviada');
    }

    /** Compra pagada SIN pasar por los correos automáticos (para probar el reenvío con el SMTP caído). */
    private function paidQuietly(Event $e): EventOrder
    {
        $order = app(EventSales::class)->createOrder($e, ['name' => 'Oscar Cota', 'email' => 'oscar@cuantica.agency', 'phone' => '4772918443', 'items' => ['p1' => 1], 'attendees' => ['Oscar Cota']]);
        EventOrder::where('id', $order->id)->update(['status' => EventOrder::PAID, 'paid_at' => now(), 'payment_id' => 'PAY-Q', 'hotel_code' => 'QR-QUIE-TOS1']);

        return $order->fresh();
    }

    public function test_resend_goes_out_immediately_not_through_the_queue_and_can_use_another_address(): void
    {
        Mail::fake();
        $e = $this->event();
        $order = $this->paidOrder($e);
        Mail::assertQueued(EventTicketsEmail::class, 1);   // la del pago
        $this->actingAs($this->admin());

        $c = Livewire::test(\App\Livewire\Pages\Admin\Events\EventOrders::class, ['eventId' => $e->id]);
        $c->call('resend', $order->id);
        Mail::assertSent(EventTicketsEmail::class, fn ($m) => $m->hasTo('oscar@cuantica.agency'));
        Mail::assertQueued(EventTicketsEmail::class, 1);   // el reenvío no fue a la cola

        $c->call('resend', $order->id, '  Otro.Correo@Ejemplo.COM ');
        Mail::assertSent(EventTicketsEmail::class, fn ($m) => $m->hasTo('otro.correo@ejemplo.com'));
        $c->assertDispatched('showAlertMessage', fn ($event, $params) => ($params['type'] ?? null) === 'success' && str_contains($params['message'], 'otro.correo@ejemplo.com'));
        $this->assertSame('oscar@cuantica.agency', $order->fresh()->email, 'mandarlo a otro correo no cambia el de la compra');
    }

    public function test_resend_rejects_an_invalid_address_and_unpaid_orders_without_sending(): void
    {
        Mail::fake();
        $e = $this->event();
        $order = $this->paidOrder($e);
        $pending = app(EventSales::class)->createOrder($e, ['name' => 'Sin pagar', 'email' => 'sp@example.com', 'phone' => '1', 'items' => ['p1' => 1], 'attendees' => ['X']]);
        $this->actingAs($this->admin());

        $c = Livewire::test(\App\Livewire\Pages\Admin\Events\EventOrders::class, ['eventId' => $e->id]);
        $c->call('resend', $order->id, 'no-es-un-correo')
            ->assertDispatched('showAlertMessage', fn ($event, $params) => ($params['type'] ?? null) === 'error' && str_contains($params['message'], 'no es un correo válido'));
        $c->call('resend', $pending->id)
            ->assertDispatched('showAlertMessage', fn ($event, $params) => ($params['type'] ?? null) === 'error' && str_contains($params['message'], 'compras pagadas'));
        Mail::assertNotSent(EventTicketsEmail::class);
    }

    public function test_resend_shows_the_real_smtp_error_instead_of_a_fake_success(): void
    {
        $e = $this->event();
        $order = $this->paidQuietly($e);
        $this->brokenSmtp();
        $this->actingAs($this->admin());

        Livewire::test(\App\Livewire\Pages\Admin\Events\EventOrders::class, ['eventId' => $e->id])
            ->call('resend', $order->id)
            ->assertDispatched('showAlertMessage', fn ($event, $params) => ($params['type'] ?? null) === 'error'
                && str_contains($params['message'], 'No se pudo enviar a oscar@cuantica.agency')
                && str_contains($params['message'], 'Copiar enlace de los boletos')
                && ($params['autoclose'] ?? 0) >= 10000);
        $this->assertNull($order->fresh()->confirmation_sent_at, 'si no salió, no queda marcada como enviada');
    }

    public function test_the_copy_link_opens_the_tickets_page_with_every_qr_and_works_without_logging_in(): void
    {
        Mail::fake();
        $e = $this->event();
        $order = $this->paidOrder($e);
        $this->actingAs($this->admin());

        $html = Livewire::test(\App\Livewire\Pages\Admin\Events\EventOrders::class, ['eventId' => $e->id])->html();
        $this->assertStringContainsString('data-testid="copy-link-' . $order->id . '"', $html);
        $this->assertStringContainsString($order->code, $html, 'el enlace lleva el código no adivinable de la compra');
        $this->assertStringNotContainsString('@js(', $html, '@js no se evalúa dentro de atributos de componentes Blade: saldría tal cual y el botón no funcionaría');
        $this->assertStringContainsString('window.prompt', $html);
        $this->assertStringContainsString('oscar@cuantica.agency', $html, 'el aviso de reenvío ofrece el correo del comprador');

        auth()->logout();
        $page = $this->get($e->url('gracias') . '?order=' . $order->code)->assertOk()->getContent();
        $this->assertStringContainsString($order->tickets()->first()->code, $page);
    }

    public function test_failed_hooks_only_touch_their_own_order_and_flag(): void
    {
        Mail::fake();
        $e = $this->event();
        $a = $this->paidOrder($e);
        $b = $this->paidOrder($e);

        (new EventTicketsEmail($a->fresh(['event', 'tickets'])))->failed(new \RuntimeException('smtp caído'));
        $this->assertNull($a->fresh()->confirmation_sent_at);
        $this->assertNotNull($a->fresh()->hotel_notified_at, 'el correo del comprador no desmarca el del hotel');
        $this->assertNotNull($b->fresh()->confirmation_sent_at, 'otra compra no se toca');

        (new EventHotelCodeEmail($b->fresh(['event', 'tickets'])))->failed(new \RuntimeException('smtp caído'));
        $this->assertNull($b->fresh()->hotel_notified_at);
        $this->assertNotNull($b->fresh()->confirmation_sent_at, 'el aviso al hotel no desmarca el del comprador');
    }

    public function test_mail_test_can_send_the_real_buyer_template_with_logo_and_one_qr_per_ticket(): void
    {
        $this->event();   // el más reciente por fecha de inicio es el que se usa; aseguramos uno con rooms visible
        $this->actingAs($this->admin());
        $transport = app('mail.manager')->mailer('array')->getSymfonyTransport();
        $before = count($transport->messages());

        $this->post('/admin/prueba-de-correo', ['mode' => 'entradas', 'emails' => 'prueba@ejemplo.com'])->assertRedirect(route('admin.mail-test'));

        $sent = $transport->messages();
        $this->assertCount($before + 1, $sent);
        $email = $sent->last()->getOriginalMessage();
        $html = (string) $email->getHtmlBody();
        $this->assertStringContainsString('AF-EJEM-PLO1', $html);
        $this->assertStringContainsString('AF-EJEM-PLO2', $html);
        $this->assertStringContainsString('Tus entradas', (string) $email->getSubject());
        $inline = array_filter($email->getAttachments(), fn ($p) => $p->getMediaType() === 'image');
        $this->assertCount(4, $inline, 'el banner, la mascota y un QR por entrada, todos incrustados');

        $page = $this->get('/admin/prueba-de-correo')->assertOk()->getContent();
        $this->assertStringContainsString('correo de entradas de ejemplo', $page);
        $this->assertStringContainsString('prueba@ejemplo.com', $page);
        $this->assertSame(0, EventOrder::where('payment_id', 'EJEMPLO')->count(), 'la compra de ejemplo no se guarda');
    }

    public function test_mail_test_of_tickets_needs_an_event_and_reports_smtp_failures_per_address(): void
    {
        $this->actingAs($this->admin());
        Event::query()->delete();
        $this->post('/admin/prueba-de-correo', ['mode' => 'entradas', 'emails' => 'prueba@ejemplo.com'])->assertRedirect();
        $this->get('/admin/prueba-de-correo')->assertSee('primero crea un evento');

        $this->event();
        $this->brokenSmtp();
        $this->post('/admin/prueba-de-correo', ['mode' => 'entradas', 'emails' => 'uno@ejemplo.com, dos@ejemplo.com'])->assertRedirect();
        $page = $this->get('/admin/prueba-de-correo')->assertOk()->getContent();
        $this->assertSame(2, substr_count($page, '❌'));
        $this->assertStringContainsString('TransportException', $page);
    }
}
