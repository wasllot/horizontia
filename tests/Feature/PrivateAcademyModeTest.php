<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/**
 * ACADEMY_PRIVATE_MODE switches the tutor marketplace off while keeping everything a course
 * academy needs. The default (flag off) must keep the legacy marketplace working.
 */
class PrivateAcademyModeTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
    }

    private function academy(bool $on = true): void
    {
        config(['academy.private_mode' => $on]);
    }

    public function test_flag_is_off_by_default_so_the_legacy_marketplace_keeps_working(): void
    {
        $this->assertFalse(isPrivateAcademy());

        $this->get('/buscar-tutores')->assertOk();
        $this->actingAs($this->student())->get('/estudiante/reservas')->assertOk();
        $this->actingAs($this->tutor())->get('/tutor/panel')->assertOk();
        $this->getJson('/api/find-tutors')->assertOk();
    }

    public function test_public_marketplace_pages_are_gone(): void
    {
        $this->academy();

        // Old links/bookmarks land on the catalog instead of a dead end.
        $this->get('/buscar-tutores')->assertRedirect(route('courses.search-courses'));
        $this->get('/tutor/' . ($this->tutor()->profile->slug ?: 'qa-tutor'))->assertRedirect(route('courses.search-courses'));
        $this->getJson('/api/find-tutors')->assertNotFound();
        $this->getJson('/api/recommended-tutors')->assertNotFound();
        $this->getJson('/api/tutor-available-slots')->assertNotFound();
    }

    public function test_student_marketplace_pages_404_but_bookings_landing_redirects_to_my_courses(): void
    {
        $this->academy();
        $student = $this->student();

        $this->actingAs($student)->get('/estudiante/reservas')->assertRedirect(route('courses.course-list'));
        foreach (['/estudiante/favoritos', '/estudiante/disputas'] as $uri) {
            $this->actingAs($student)->get($uri)->assertNotFound();
        }
        $this->actingAs($student, 'sanctum')->getJson('/api/upcoming-bookings')->assertNotFound();
        $this->actingAs($student, 'sanctum')->getJson('/api/dispute-listing')->assertNotFound();
    }

    public function test_instructor_lands_on_the_course_list_and_loses_booking_and_payout_pages(): void
    {
        $this->academy();
        $tutor = $this->tutor();

        $this->actingAs($tutor)->get('/tutor/panel')->assertRedirect(route('courses.tutor.courses'));
        foreach (['/tutor/pagos', '/tutor/reservas/gestionar-materias', '/tutor/reservas/gestionar-sesiones', '/tutor/disputas'] as $uri) {
            $this->actingAs($tutor)->get($uri)->assertNotFound();
        }
        $this->actingAs($tutor, 'sanctum')->getJson('/api/user-withdrawal')->assertNotFound();
        $this->assertSame(route('courses.tutor.courses', absolute: false), $tutor->redirect_after_login);
        $this->assertSame(route('courses.course-list', absolute: false), $this->student()->redirect_after_login);
    }

    public function test_admin_loses_marketplace_modules_but_keeps_course_and_payment_ones(): void
    {
        $this->academy();
        $admin = $this->admin();

        foreach (['/admin/reservas', '/admin/solicitudes-de-retiro', '/admin/comisiones', '/admin/disputas', '/admin/taxonomias/materias'] as $uri) {
            $this->actingAs($admin)->get($uri)->assertNotFound();
        }
        foreach (['/admin/usuarios', '/admin/facturas', '/admin/metodos-de-pago', '/admin/cursos', '/admin/estadisticas'] as $uri) {
            $this->assertLessThan(500, $this->actingAs($admin)->get($uri)->getStatusCode(), $uri);
            $this->actingAs($admin)->get($uri)->assertSuccessful();
        }
    }

    public function test_navigation_hides_tutor_links_in_academy_mode_only(): void
    {
        $this->academy(false);
        $this->actingAs($this->student())->get('/')->assertSee(route('find-tutors'), false);

        $this->academy();
        $home = $this->actingAs($this->student())->get('/')->assertOk();
        $home->assertDontSee(route('find-tutors'), false);
        $home->assertDontSee(route('student.favourites'), false);
        $home->assertSee(route('courses.search-courses'), false);
        $home->assertSee(route('how-it-works'), false);
    }

    public function test_courses_checkout_and_purchase_history_still_work(): void
    {
        $this->academy();
        $student = $this->student();

        $this->get('/cursos')->assertOk();
        $this->get('/curso/' . $this->course()->slug)->assertOk();
        $this->actingAs($student)->get('/aula/' . $this->course()->slug)->assertOk();
        $this->actingAs($student)->get('/estudiante/facturas')->assertOk();
        $this->actingAs($student)->get('/estudiante/datos-de-facturacion')->assertOk();
        $this->actingAs($student, 'sanctum')->getJson('/api/enrolled-courses')->assertOk();
    }

    public function test_wallet_cannot_be_used_in_academy_checkout(): void
    {
        $this->academy();
        $student = $this->student();
        \App\Models\CartItem::where('user_id', $student->id)->delete();
        $this->actingAs($student, 'sanctum')->postJson('/api/course-cart', ['slug' => $this->course2()->slug])->assertOk();

        $component = Livewire::actingAs($student)->test(\App\Livewire\Frontend\Checkout::class)->set('useWalletBalance', true);
        $component->set('form.firstName', 'QA')->set('form.lastName', 'S')->set('form.email', $student->email)
            ->set('form.country', 'México')->set('form.state', 'CDMX')->set('form.city', 'CDMX')->set('form.zipcode', '06600')
            ->set('form.paymentMethod', 'stripe')->call('updateInfo');

        $this->assertFalse($component->get('useWalletBalance'));
    }
}
