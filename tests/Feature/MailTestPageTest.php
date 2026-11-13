<?php

namespace Tests\Feature;

use App\Mail\MailTestEmail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Admin → Prueba de correo (/admin/prueba-de-correo): solo admins, envío directo o por la cola, sin mostrar contraseñas. */
class MailTestPageTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    public function test_only_admins_can_open_or_use_the_mail_test(): void
    {
        $this->get('/admin/prueba-de-correo')->assertRedirect();
        $this->actingAs($this->student())->get('/admin/prueba-de-correo')->assertForbidden();
        $this->actingAs($this->student())->post('/admin/prueba-de-correo', ['mode' => 'directo'])->assertForbidden();
    }

    public function test_page_shows_the_configuration_without_secrets_and_the_two_recipients(): void
    {
        config(['mail.mailers.smtp.username' => 'usuario-secreto@horizontia.com', 'mail.mailers.smtp.password' => 'clave-super-secreta-123']);
        $html = $this->actingAs($this->admin())->get('/admin/prueba-de-correo')->assertOk()->getContent();
        foreach (['Prueba de correo', 'rei.vzl@gmail.com', 'reinaldo@cuantica.agency', 'usuario capturado', 'contraseña capturada', 'Enviar directo (SMTP)', 'Enviar por la cola'] as $needle) {
            $this->assertStringContainsString($needle, $html, "falta: $needle");
        }
        $this->assertStringNotContainsString('clave-super-secreta-123', $html);
        $this->assertStringNotContainsString('usuario-secreto@horizontia.com', $html);
        $this->assertStringContainsString('noindex', $html);
    }

    public function test_direct_send_goes_to_both_addresses_and_reports_each(): void
    {
        Mail::fake();
        $this->actingAs($this->admin())->post('/admin/prueba-de-correo', ['mode' => 'directo'])->assertRedirect(route('admin.mail-test'));
        Mail::assertSentCount(2);
        Mail::assertSent(MailTestEmail::class, fn ($m) => $m->hasTo('rei.vzl@gmail.com') && $m->mode === 'directo');
        Mail::assertSent(MailTestEmail::class, fn ($m) => $m->hasTo('reinaldo@cuantica.agency'));

        $page = $this->get('/admin/prueba-de-correo')->assertOk()->getContent();
        $this->assertStringContainsString('directo por SMTP', $page);
        $this->assertSame(2, substr_count($page, 'El servidor SMTP aceptó el mensaje.'));
    }

    public function test_queue_send_queues_one_message_per_address(): void
    {
        Mail::fake();
        $this->actingAs($this->admin())->post('/admin/prueba-de-correo', ['mode' => 'cola'])->assertRedirect();
        Mail::assertQueuedCount(2);
        Mail::assertQueued(MailTestEmail::class, fn ($m) => $m->hasTo('rei.vzl@gmail.com') && $m->mode === 'cola');
        $this->get('/admin/prueba-de-correo')->assertSee('por la cola')->assertSee('Puesto en la cola');
    }

    public function test_a_failing_mail_server_is_reported_per_address_instead_of_crashing(): void
    {
        $this->actingAs($this->admin());
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.username' => null, 'mail.mailers.smtp.password' => null]);
        app('mail.manager')->forgetMailers();
        $this->post('/admin/prueba-de-correo', ['mode' => 'directo'])->assertRedirect();
        $page = $this->get('/admin/prueba-de-correo')->assertOk()->getContent();
        $this->assertSame(2, substr_count($page, '❌'), 'cada destinatario muestra su error');
        $this->assertStringContainsString('TransportException', $page);
    }

    public function test_the_test_mail_itself_says_where_it_came_from(): void
    {
        $mail = new MailTestEmail('directo');
        $mail->assertSeeInHtml('Si lees esto, el correo funciona');
        $mail->assertSeeInHtml('directo');
        $this->assertStringContainsString('Prueba de correo', $mail->envelope()->subject);
    }
}
