<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Larabuild\Optionbuilder\Facades\Settings;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Ajustes › Mantenimiento: aviso en español para visitantes; admins y login siguen funcionando. */
class MaintenanceModeTest extends TestCase
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

    private function on(array $extra = []): void
    {
        foreach (array_merge(['maintenance_mode' => 'Yes', 'maintenance_title' => 'Volvemos pronto', 'maintenance_description' => 'Estamos mejorando la plataforma.', 'maintenance_logo' => []], $extra) as $key => $value) {
            Settings::set('_maintenance', $key, $value);
        }
    }

    public function test_visitors_see_a_spanish_notice_with_the_brand_logo_and_a_real_503(): void
    {
        $this->on(['maintenance_email' => 'hola@academia.test']);

        $res = $this->get('/')->assertStatus(503)->assertHeader('Retry-After');
        $res->assertSee('Volvemos pronto')->assertSee('Estamos mejorando la plataforma.')->assertSee('images/nuevo/logo-horizontia.png', false)->assertSee('hola@academia.test')->assertSee('lang="es"', false);
        $res->assertDontSee('Scheduled Maintenance')->assertDontSee('Please contact');
    }

    public function test_an_empty_notice_still_says_something_in_spanish(): void
    {
        $this->on(['maintenance_title' => '', 'maintenance_description' => '']);

        $this->get('/')->assertStatus(503)->assertSee('Estamos en mantenimiento');
    }

    public function test_the_login_page_and_admins_keep_working_during_maintenance(): void
    {
        $this->on();

        $this->get(route('login'))->assertOk();
        $this->actingAs($this->admin())->get('/')->assertOk()->assertDontSee('Volvemos pronto');
        $this->actingAs($this->admin())->get('/admin/ajustes')->assertOk();
    }

    public function test_students_are_kept_out_of_the_public_site_during_maintenance(): void
    {
        $this->on();

        $this->actingAs($this->student())->get('/')->assertStatus(503);
    }

    public function test_switching_it_off_reopens_the_site(): void
    {
        $this->on();
        Settings::set('_maintenance', 'maintenance_mode', 'No');

        $this->get('/')->assertOk()->assertDontSee('Volvemos pronto');
    }
}
