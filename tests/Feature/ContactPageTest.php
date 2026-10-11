<?php

namespace Tests\Feature;

use App\Livewire\Frontend\ContactForm;
use App\Mail\ContactMessage;
use App\Models\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Página de contacto: banner con imagen, formas de contacto, temas rápidos y un formulario que se defiende solo. */
class ContactPageTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        Mail::fake();
        Cache::flush();
        RateLimiter::clear('contact-form:' . sha1('127.0.0.1'));
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function fill(array $over = [])
    {
        $d = $over + ['name' => 'Ana Torres', 'email' => 'ana@ejemplo.mx', 'subject' => 'Duda sobre un pago', 'message' => 'Hola, pagué con OXXO y aún no veo mi curso.'];

        return Livewire::test(ContactForm::class)->set('name', $d['name'])->set('email', $d['email'])->set('subject', $d['subject'])->set('message', $d['message']);
    }

    public function test_the_page_has_a_banner_with_an_image_and_the_ways_to_reach_us(): void
    {
        $html = $this->get('/contacto')->assertOk()->getContent();

        $this->assertStringContainsString('data-testid="page-banner"', $html);
        $this->assertStringContainsString('hz-banner--image', $html);
        $this->assertStringContainsString('banner-contacto', $html);
        $this->assertStringContainsString('Hablemos', $html);
        $this->assertStringContainsString('Tiempo de respuesta', $html);
        $this->assertStringContainsString('mailto:', $html);
        $this->assertStringContainsString(route('legal.faq'), $html);
    }

    public function test_the_contact_email_comes_from_the_settings_not_from_a_hardcoded_address(): void
    {
        config(['academy.contact_email' => 'hola@miacademia.mx']);

        $this->get('/contacto')->assertOk()->assertSee('hola@miacademia.mx')->assertDontSee('soporte@horizontia.test');
    }

    public function test_the_default_banner_image_exists_and_an_admin_banner_overrides_it(): void
    {
        $this->assertFileExists(public_path('images/nuevo/banner-contacto.jpg'));
        $this->assertFileExists(public_path('images/nuevo/banner-acceso.jpg'));

        \App\Models\PageBanner::create(['page_key' => 'system:contacto', 'title' => 'Título propio', 'subtitle' => 'Sub propio', 'overlay' => 40, 'align' => 'center']);
        \App\Services\PageBannerService::forget('system:contacto');
        $this->get('/contacto')->assertSee('Título propio')->assertDontSee('Hablemos');
    }

    public function test_a_topic_chip_fills_the_subject_without_overwriting_what_the_person_typed(): void
    {
        $page = Livewire::test(ContactForm::class)->call('pick', 'payments')->assertSet('topic', 'payments')->assertSet('subject', 'Tengo una duda sobre un pago o factura');
        $page->call('pick', 'courses')->assertSet('subject', 'Quiero información sobre un curso');   // el asunto era el del tema anterior: se actualiza

        $page->set('subject', 'Mi propio asunto')->call('pick', 'access')->assertSet('subject', 'Mi propio asunto')->assertSet('topic', 'access');
        $page->call('pick', 'inventado')->assertSet('topic', 'access');
        Livewire::withQueryParams(['asunto' => 'teach'])->test(ContactForm::class)->assertSet('topic', 'teach')->assertSet('subject', 'Quiero enseñar en la academia');
    }

    public function test_a_valid_message_is_saved_mailed_and_confirmed(): void
    {
        $this->fill()->call('submit')->assertHasNoErrors()->assertSet('sent', true)->assertSee('¡Mensaje enviado!');

        $this->assertSame('ana@ejemplo.mx', Contact::latest('id')->first()->email);
        Mail::assertSent(ContactMessage::class, 1);
    }

    public function test_it_explains_what_is_wrong_and_sends_nothing(): void
    {
        $page = Livewire::test(ContactForm::class)->call('submit')->assertHasErrors(['name', 'email', 'subject', 'message']);
        $page->assertSee('Escribe tu correo para poder responderte.');
        $this->fill(['email' => 'no-es-correo'])->call('submit')->assertHasErrors('email');
        $this->fill(['message' => 'corto'])->call('submit')->assertHasErrors('message');
        $this->assertSame(0, Contact::count());
        Mail::assertNothingSent();
    }

    public function test_html_is_stripped_and_the_honeypot_silently_drops_bots(): void
    {
        $this->fill(['name' => '<b>Ana</b><script>x</script>', 'message' => '<img src=x onerror=alert(1)>Mensaje con suficiente longitud'])->call('submit');
        $c = Contact::latest('id')->first();
        $this->assertStringNotContainsString('<', $c->name . $c->message);

        Contact::query()->delete();
        Mail::fake();
        $this->fill(['email' => 'bot@spam.mx'])->set('website', 'http://spam')->call('submit')->assertSet('sent', true);
        $this->assertSame(0, Contact::count());
        Mail::assertNothingSent();
    }

    public function test_it_is_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < ContactForm::PER_HOUR; $i++) {
            $this->fill(['email' => "p$i@ejemplo.mx"])->call('submit')->assertHasNoErrors();
        }

        $this->fill(['email' => 'otro@ejemplo.mx'])->call('submit')->assertHasErrors('message');
        $this->assertSame(ContactForm::PER_HOUR, Contact::count());
    }
}
