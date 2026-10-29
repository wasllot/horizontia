<?php

namespace Tests\Feature;

use App\Livewire\Frontend\RequestAccess;
use App\Models\AccessRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * Primera etapa de lanzamiento (config/academy.php): el catálogo, «Cómo funciona», las preguntas frecuentes y el acceso de instructores
 * están ocultos al público «por lo pronto». Cada interruptor los vuelve a mostrar.
 */
class FirstStageVisibilityTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        Cache::flush();
        Mail::fake();
        config(['academy.private_mode' => true, 'academy.access_requests' => true]);
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function firstStage(): void
    {
        config(['academy.public_courses' => false, 'academy.public_how_it_works' => false, 'academy.public_faq' => false, 'academy.hero_buttons' => false, 'academy.instructor_requests' => false]);
    }

    private function fullPlatform(): void
    {
        config(['academy.public_courses' => true, 'academy.public_how_it_works' => true, 'academy.public_faq' => true, 'academy.hero_buttons' => true, 'academy.instructor_requests' => true]);
    }

    public function test_the_home_hides_courses_buttons_stats_steps_and_questions(): void
    {
        $this->firstStage();

        $html = $this->get('/')->assertOk()->getContent();

        foreach (['VER CURSOS', 'Cómo funciona', 'cursos</li>', 'instructores</li>', 'Cursos Horizontia</h2>', 'ht-cc__track', 'id="pasos"', 'Desbloquea tu potencial', 'class="ht-faq__head', 'Explorar cursos'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $html, $hidden);
        }
        $this->assertStringContainsString('100&nbsp;%', $html, 'the 100 % a tu ritmo stat stays');
        $this->assertStringContainsString('Temática Horizontia', $html);
        foreach (['Bienestar y estilo de vida', 'Desarrollo Profesional y Laboral', 'Tecnología con aplicación práctica', 'Emprendimiento y negocios', 'Ciencias y artes'] as $theme) {
            $this->assertStringContainsString($theme, $html);
        }
        $this->assertStringContainsString('Cursos autogestionables', $html, "the client's own wording for what the platform offers");
        $this->assertStringContainsString('href="#que-ofrece"', $html, 'the scroll cue points to a section that exists');
    }

    public function test_the_full_platform_shows_everything_again(): void
    {
        $this->fullPlatform();

        $html = $this->get('/')->assertOk()->getContent();

        foreach (['VER CURSOS', 'Cómo funciona', 'ht-cc__track', 'id="pasos"', 'class="ht-faq__head', 'Explorar cursos'] as $shown) {
            $this->assertStringContainsString($shown, $html, $shown);
        }
    }

    public function test_menus_drop_the_hidden_links_in_header_and_footer(): void
    {
        $this->firstStage();
        $html = $this->get('/nosotros')->assertOk()->getContent();
        $nav = substr($html, strpos($html, 'ht-nav'), 3500);
        foreach (['/cursos"', '/como-funciona"', '/preguntas-frecuentes"'] as $dead) {
            $this->assertStringNotContainsString('href="' . url($dead), $html, $dead);
        }
        $this->assertStringContainsString('Eventos', $nav);
        $this->assertStringContainsString('Contacto', $nav);

        $this->fullPlatform();
        Cache::flush();
        $this->assertStringContainsString('href="' . url('/cursos') . '"', $this->get('/nosotros')->getContent());
    }

    public function test_hidden_pages_answer_404_and_leave_the_sitemap_until_their_switch_is_on(): void
    {
        $this->firstStage();
        $this->get('/como-funciona')->assertNotFound();
        $this->get('/preguntas-frecuentes')->assertNotFound();
        $map = $this->get('/sitemap.xml')->getContent();
        foreach (['/como-funciona', '/preguntas-frecuentes', '/cursos<'] as $gone) {
            $this->assertStringNotContainsString($gone, $map, $gone);
        }
        $this->assertStringContainsString('/nosotros', $map, 'the rest of the pages stay listed');

        $this->fullPlatform();
        $this->get('/como-funciona')->assertOk();
        $this->get('/preguntas-frecuentes')->assertOk();
        $this->assertStringContainsString('/preguntas-frecuentes', $this->get('/sitemap.xml')->getContent());
    }

    public function test_nosotros_uses_the_platform_name_and_wording_and_hides_the_unfinished_parts(): void
    {
        $this->firstStage();

        $html = $this->get('/nosotros')->assertOk()->getContent();

        $this->assertStringContainsString('Horizontia</strong> es una plataforma de aprendizaje en línea privada', $html);
        $this->assertStringNotContainsString('Laravel', $html);
        foreach (['Así funciona', 'Preguntas frecuentes', 'Quiero ser instructor', 'cursos disponibles', 'Ver cursos'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $html, $hidden);
        }
        $this->assertStringContainsString('por invitación', $html, 'instructors join by invitation');
    }

    public function test_the_default_site_name_is_never_laravel(): void
    {
        $this->assertSame('Horizontia', config('app.name'));
        $this->assertSame('Horizontia', config('academy.legal_name'));
    }

    public function test_access_requests_offer_only_students_in_the_first_stage_and_the_server_enforces_it(): void
    {
        $this->firstStage();

        $this->get('/solicitar-acceso?como=instructor')->assertOk()->assertDontSee('Quiero enseñar')->assertDontSee('type-tutor', false)->assertSee('Pide tu acceso a la plataforma')->assertSee('Pide tu cuenta para aprender');

        Livewire::withQueryParams(['como' => 'instructor'])->test(RequestAccess::class)->assertSet('type', 'student')->call('choose', 'tutor')->assertSet('type', 'student');

        Livewire::test(RequestAccess::class)->set('type', 'tutor')->set('name', 'Ana Instructora')->set('email', 'ana.tutor@ejemplo.mx')->set('message', str_repeat('Tengo experiencia enseñando. ', 3))->set('consent', true)
            ->call('submit')->assertHasErrors('type');
        $this->assertSame(0, AccessRequest::where('email', 'ana.tutor@ejemplo.mx')->count());

        Livewire::test(RequestAccess::class)->set('name', 'Luis Alumno')->set('email', 'luis.alumno@ejemplo.mx')->set('consent', true)->call('submit')->assertHasNoErrors()->assertSet('sent', true);
        $this->assertSame('student', AccessRequest::where('email', 'luis.alumno@ejemplo.mx')->value('type'));
    }

    public function test_the_instructor_option_returns_when_its_switch_is_on(): void
    {
        $this->fullPlatform();

        $this->get('/solicitar-acceso')->assertOk()->assertSee('Quiero enseñar')->assertSee('type-tutor', false);
        Livewire::withQueryParams(['como' => 'instructor'])->test(RequestAccess::class)->assertSet('type', 'tutor');
    }

    public function test_the_contact_page_does_not_point_to_the_hidden_faq(): void
    {
        $this->firstStage();
        $this->get('/contacto')->assertOk()->assertDontSee('preguntas frecuentes')->assertSee('te respondemos en menos de 24 horas');

        $this->fullPlatform();
        $this->get('/contacto')->assertSee('preguntas frecuentes');
    }
}
