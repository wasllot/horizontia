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

    public function test_any_address_can_be_tested_and_only_those_receive_it(): void
    {
        Mail::fake();
        $this->actingAs($this->admin())->post('/admin/prueba-de-correo', ['mode' => 'directo', 'emails' => 'Prueba@Ejemplo.com, otra@ejemplo.com; prueba@ejemplo.com'])->assertRedirect();
        Mail::assertSentCount(2);
        Mail::assertSent(MailTestEmail::class, fn ($m) => $m->hasTo('prueba@ejemplo.com'));
        Mail::assertSent(MailTestEmail::class, fn ($m) => $m->hasTo('otra@ejemplo.com'));
        Mail::assertNotSent(MailTestEmail::class, fn ($m) => $m->hasTo('rei.vzl@gmail.com'));
        $this->get('/admin/prueba-de-correo')->assertSee('value="prueba@ejemplo.com, otra@ejemplo.com"', false);
    }

    public function test_invalid_or_too_many_addresses_are_rejected_without_sending_anything(): void
    {
        Mail::fake();
        $this->actingAs($this->admin());
        $this->post('/admin/prueba-de-correo', ['mode' => 'directo', 'emails' => 'bien@ejemplo.com, esto-no-es-correo'])->assertRedirect();
        $this->get('/admin/prueba-de-correo')->assertSee('Correo no válido: esto-no-es-correo.');
        $this->post('/admin/prueba-de-correo', ['mode' => 'cola', 'emails' => 'a@e.com, b@e.com, c@e.com, d@e.com, e@e.com, f@e.com'])->assertRedirect();
        $this->get('/admin/prueba-de-correo')->assertSee('Máximo 5 correos por prueba.');
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }

    public function test_a_failure_shows_the_technical_detail_a_probable_cause_and_never_the_password(): void
    {
        $this->actingAs($this->admin());
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.username' => 'cuenta@horizontia.com', 'mail.mailers.smtp.password' => 'clave-super-secreta-123']);
        app('mail.manager')->forgetMailers();
        $this->post('/admin/prueba-de-correo', ['mode' => 'directo', 'emails' => 'destino@ejemplo.com'])->assertRedirect();
        $page = $this->get('/admin/prueba-de-correo')->assertOk()->getContent();
        $this->assertStringContainsString('No se pudo enviar (Symfony\Component\Mailer\Exception\TransportException)', $page);
        $this->assertStringContainsString('Causa probable:', $page);
        $this->assertStringContainsString('No se pudo conectar al servidor', $page);
        $this->assertStringContainsString('Detalle técnico', $page);
        $this->assertStringContainsString('Excepción: Symfony\Component\Mailer\Exception\TransportException', $page);
        $this->assertStringNotContainsString('clave-super-secreta-123', $page);
    }

    public function test_diagnostics_hide_credentials_and_explain_the_usual_causes(): void
    {
        $dialog = "< 220 mail.horizontia.com ESMTP\n> EHLO localhost\n> AUTH LOGIN\n< 334 VXNlcm5hbWU6\n> dXN1YXJpb0BleGFtcGxlLmNvbQ==\n< 334 UGFzc3dvcmQ6\n> Y2xhdmUtc3VwZXItc2VjcmV0YS0xMjM=\n< 535 5.7.8 Authentication failed with clave-super-secreta-123";
        $clean = \App\Support\MailDiagnostics::scrub($dialog . "\nAUTH PLAIN AHVzdWFyaW8AY2xhdmU=", ['clave-super-secreta-123', 'usuario@example.com']);
        foreach (['clave-super-secreta-123', 'dXN1YXJpb0BleGFtcGxlLmNvbQ==', 'Y2xhdmUtc3VwZXItc2VjcmV0YS0xMjM=', 'AHVzdWFyaW8AY2xhdmU='] as $secret) {
            $this->assertStringNotContainsString($secret, $clean);
        }
        $this->assertStringContainsString('220 mail.horizontia.com ESMTP', $clean, 'lo demás del diálogo se conserva');

        $hints = [
            '535 5.7.8 Authentication failed' => 'usuario o la contraseña',
            'Connection could not be established with host "mail.x.com:587": Connection timed out' => 'No se pudo conectar',
            'Unable to connect with STARTTLS: SSL routines certificate verify failed' => 'Falló el cifrado',
            '554 5.7.1 Relay access denied' => 'remitente o el destino',
            '550 5.1.1 User unknown' => 'buzón no existe',
            '451 4.7.1 Too many connections, try again later' => 'Rechazo temporal',
            'Connection refused [tcp://127.0.0.1:6379] redis' => 'Redis',
        ];
        foreach ($hints as $message => $expected) {
            $this->assertStringContainsString($expected, (string) \App\Support\MailDiagnostics::hint($message), $message);
        }
        $this->assertNull(\App\Support\MailDiagnostics::hint('algo totalmente distinto'));
    }

    public function test_recent_failed_queue_jobs_are_listed_with_credentials_hidden(): void
    {
        config(['mail.mailers.smtp.password' => 'clave-super-secreta-123']);
        \Illuminate\Support\Facades\DB::table('failed_jobs')->insert(['uuid' => (string) \Illuminate\Support\Str::uuid(), 'connection' => 'redis', 'queue' => 'default', 'payload' => '{}',
            'exception' => "Symfony\\Component\\Mailer\\Exception\\TransportException: Failed to authenticate with clave-super-secreta-123\n#0 trace", 'failed_at' => now()]);
        $page = $this->actingAs($this->admin())->get('/admin/prueba-de-correo')->assertOk()->getContent();
        $this->assertStringContainsString('Últimos trabajos fallidos de la cola', $page);
        $this->assertStringContainsString('Failed to authenticate with [oculto]', $page);
        $this->assertStringNotContainsString('clave-super-secreta-123', $page);
    }
}
