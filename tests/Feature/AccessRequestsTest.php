<?php

namespace Tests\Feature;

use App\Livewire\Frontend\RequestAccess;
use App\Livewire\Pages\Admin\AccessRequests\AccessRequests;
use App\Mail\AccessApprovedEmail;
use App\Mail\AccessExistingAccountEmail;
use App\Mail\AccessRejectedEmail;
use App\Mail\AccessRequestAdminEmail;
use App\Mail\AccessRequestReceivedEmail;
use App\Models\AccessRequest;
use App\Models\User;
use App\Services\AccessRequestService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Nadie se registra solo: se pide acceso (estudiante o instructor), el equipo aprueba y la persona activa su cuenta con un enlace de un solo uso. */
class AccessRequestsTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.access_requests' => true, 'academy.private_mode' => true]);
        Mail::fake();
        Cache::flush();
        RateLimiter::clear('access-request:' . sha1('127.0.0.1'));
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function fill(string $type = 'student', array $over = [])
    {
        $data = $over + ['name' => 'Laura Gómez Ruiz', 'email' => 'laura.nueva@ejemplo.mx', 'consent' => true, 'message' => 'Quiero aprender ventas y marketing digital para mi negocio.'];

        return Livewire::test(RequestAccess::class)->call('choose', $type)->set('name', $data['name'])->set('email', $data['email'])->set('phone', $data['phone'] ?? '')->set('message', $data['message'])->set('link', $data['link'] ?? '')->set('consent', $data['consent']);
    }

    public function test_the_public_page_exists_and_registration_now_points_to_it(): void
    {
        $this->get('/solicitar-acceso')->assertOk()->assertSee('Pide tu acceso a la academia')->assertSee('Quiero aprender')->assertSee('Quiero enseñar');
        $this->get('/registro')->assertRedirect('/solicitar-acceso');
        $this->get('/iniciar-sesion')->assertOk()->assertSee('Solicita acceso');
    }

    public function test_the_home_steps_are_coherent_with_a_closed_academy(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Solicita tu acceso', $html);
        $this->assertStringContainsString(route('access.request'), $html);
        $this->assertStringContainsString('¿Cómo entro a la academia?', $html);
        $this->assertStringNotContainsString('Crea tu cuenta rápidamente', $html);
        $this->assertStringNotContainsString('>Regístrate<', $html);
    }

    public function test_a_student_request_is_saved_and_everyone_is_notified(): void
    {
        $this->fill('student', ['phone' => '55 1234 5678'])->call('submit')->assertHasNoErrors()->assertSet('sent', true);

        $r = AccessRequest::firstOrFail();
        $this->assertSame(['student', 'laura.nueva@ejemplo.mx', 'pending'], [$r->type, $r->email, $r->status]);
        $this->assertNull($r->user_id, 'no account is created before approval');
        $this->assertNull(User::where('email', 'laura.nueva@ejemplo.mx')->first());
        Mail::assertQueued(AccessRequestReceivedEmail::class, fn ($m) => $m->hasTo('laura.nueva@ejemplo.mx'));
        Mail::assertQueued(AccessRequestAdminEmail::class, fn ($m) => $m->hasTo($this->admin()->email));
    }

    public function test_an_instructor_request_needs_the_experience_and_a_valid_link(): void
    {
        $this->fill('tutor', ['message' => 'poco'])->call('submit')->assertHasErrors('message');
        $this->fill('tutor', ['link' => 'javascript:alert(1)'])->call('submit')->assertHasErrors('link');
        $this->assertSame(0, AccessRequest::count());

        $this->fill('tutor', ['message' => str_repeat('Tengo diez años enseñando ventas. ', 3), 'link' => 'https://linkedin.com/in/laura'])->call('submit')->assertHasNoErrors();
        $this->assertSame('tutor', AccessRequest::firstOrFail()->type);
    }

    public function test_the_form_validates_name_email_and_consent(): void
    {
        Livewire::test(RequestAccess::class)->call('submit')->assertHasErrors(['name', 'email', 'consent']);
        $this->fill('student', ['email' => 'no-es-correo'])->call('submit')->assertHasErrors('email');
        $this->fill('student', ['consent' => false])->call('submit')->assertHasErrors('consent');
        $this->assertSame(0, AccessRequest::count());
    }

    public function test_html_is_stripped_and_the_honeypot_silently_drops_bots(): void
    {
        $this->fill('student', ['name' => '<b>Laura</b> <script>x</script>Gómez', 'message' => '<img src=x onerror=alert(1)>Quiero aprender'])->call('submit');
        $r = AccessRequest::firstOrFail();
        $this->assertStringNotContainsString('<', $r->name . $r->message);

        AccessRequest::query()->delete();
        Mail::fake();
        $this->fill('student', ['email' => 'bot@spam.mx'])->set('website', 'http://spam')->call('submit')->assertSet('sent', true);
        $this->assertSame(0, AccessRequest::count(), 'looks successful to the bot but nothing is stored');
        Mail::assertNotQueued(AccessRequestAdminEmail::class);
    }

    public function test_the_same_email_is_not_duplicated_while_pending(): void
    {
        $this->fill('student')->call('submit');
        $this->fill('tutor', ['message' => str_repeat('Experiencia enseñando muchos años. ', 2)])->call('submit');

        $this->assertSame(1, AccessRequest::count());
        $this->assertSame('tutor', AccessRequest::first()->type, 'the latest answer wins');
        $admins = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->count();
        Mail::assertQueued(AccessRequestAdminEmail::class, $admins);   // one per admin, and only for the first submission
    }

    public function test_an_email_that_already_has_an_account_gets_the_same_page_and_a_private_hint(): void
    {
        $this->fill('student', ['email' => $this->student()->email])->call('submit')->assertSet('sent', true);

        $this->assertSame(0, AccessRequest::count());
        Mail::assertQueued(AccessExistingAccountEmail::class, fn ($m) => $m->hasTo($this->student()->email));
    }

    public function test_requests_are_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < AccessRequestService::PER_HOUR; $i++) {
            $this->fill('student', ['email' => "p$i@ejemplo.mx"])->call('submit')->assertHasNoErrors();
        }

        $this->fill('student', ['email' => 'otro@ejemplo.mx'])->call('submit')->assertHasErrors('email');
        $this->assertSame(AccessRequestService::PER_HOUR, AccessRequest::count());
    }

    public function test_approving_creates_the_account_with_the_right_role_and_sends_a_one_time_link(): void
    {
        $this->fill('tutor', ['message' => str_repeat('Doy cursos de ventas desde hace años. ', 2)])->call('submit');
        $r = AccessRequest::firstOrFail();

        Livewire::actingAs($this->admin())->test(AccessRequests::class)->call('approve', $r->id);

        $user = User::where('email', 'laura.nueva@ejemplo.mx')->firstOrFail();
        $this->assertTrue($user->hasRole('tutor'));
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('Laura', $user->profile->first_name);
        $this->assertSame('Gómez Ruiz', $user->profile->last_name);
        $this->assertSame(['approved', $user->id], [$r->fresh()->status, $r->fresh()->user_id]);

        $url = null;
        Mail::assertQueued(AccessApprovedEmail::class, function ($m) use (&$url) {
            $url = $m->activationUrl;

            return true;
        });
        $this->assertStringContainsString('signature=', $url);

        $first = $this->get($url);
        $first->assertRedirect();
        $this->assertStringContainsString('restablecer-contrasena/', $first->headers->get('Location'));
        $this->assertNotNull($r->fresh()->activated_at);
        $this->get($url)->assertRedirect(route('password.request'));   // second use: refused
    }

    public function test_the_activation_link_cannot_be_forged_or_reused_for_pending_requests(): void
    {
        $this->fill('student')->call('submit');
        $r = AccessRequest::firstOrFail();

        $this->get(route('account.activate', ['request' => $r->id]))->assertForbidden();   // unsigned
        $signed = \Illuminate\Support\Facades\URL::temporarySignedRoute('account.activate', now()->addDay(), ['request' => $r->id]);
        $this->get($signed)->assertNotFound();   // still pending: no account yet
    }

    public function test_rejecting_notifies_the_person_with_the_reason_and_never_creates_an_account(): void
    {
        $this->fill('student')->call('submit');
        $r = AccessRequest::firstOrFail();

        Livewire::actingAs($this->admin())->test(AccessRequests::class)->call('askReject', $r->id)->set('note', 'Por ahora solo atendemos empresas.')->call('reject');

        $this->assertSame(['rejected', 'Por ahora solo atendemos empresas.'], [$r->fresh()->status, $r->fresh()->review_note]);
        $this->assertNull(User::where('email', 'laura.nueva@ejemplo.mx')->first());
        Mail::assertQueued(AccessRejectedEmail::class, fn ($m) => $m->hasTo('laura.nueva@ejemplo.mx') && $m->request->review_note === 'Por ahora solo atendemos empresas.');
    }

    public function test_a_processed_request_cannot_be_approved_twice(): void
    {
        $this->fill('student')->call('submit');
        $r = AccessRequest::firstOrFail();
        $page = Livewire::actingAs($this->admin())->test(AccessRequests::class);
        $page->call('approve', $r->id)->call('approve', $r->id);

        Mail::assertQueued(AccessApprovedEmail::class, 1);
        $this->assertSame(1, User::where('email', 'laura.nueva@ejemplo.mx')->count());
    }

    public function test_the_admin_screen_filters_searches_and_is_admin_only(): void
    {
        AccessRequest::create(['type' => 'student', 'name' => 'Pendiente Uno', 'email' => 'p1@x.mx', 'status' => 'pending']);
        AccessRequest::create(['type' => 'tutor', 'name' => 'Aprobada Dos', 'email' => 'p2@x.mx', 'status' => 'approved']);

        $page = Livewire::actingAs($this->admin())->test(AccessRequests::class);
        $page->assertSee('Pendiente Uno')->assertDontSee('Aprobada Dos');
        $page->call('setFilter', 'approved')->assertSee('Aprobada Dos')->assertDontSee('Pendiente Uno');
        $page->call('setFilter', 'all')->set('search', 'p2@x')->assertSee('Aprobada Dos')->assertDontSee('Pendiente Uno');

        $this->assertNotSame(200, $this->actingAs($this->student())->get(route('admin.access-requests'))->getStatusCode());
        Livewire::actingAs($this->student())->test(AccessRequests::class)->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.access-requests'))->assertOk()->assertSee('Solicitudes de acceso');
    }

    public function test_the_page_can_be_hidden_and_the_dashboard_counts_pending_requests(): void
    {
        AccessRequest::create(['type' => 'student', 'name' => 'X', 'email' => 'x@x.mx', 'status' => 'pending']);
        $this->assertSame(1, collect((new \App\Services\AcademyDashboardService())->attention())->firstWhere('key', 'access')['count']);

        config(['academy.access_requests' => false]);
        $this->get('/solicitar-acceso')->assertNotFound();
        $this->get('/iniciar-sesion')->assertOk()->assertDontSee('Solicita acceso');
    }

    public function test_the_banner_of_the_page_can_be_customized_like_the_other_static_pages(): void
    {
        $this->assertArrayHasKey('solicitar-acceso', \App\Services\PageBannerService::SYSTEM);
    }
}
