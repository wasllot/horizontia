<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Courses\Models\Course;
use Modules\Courses\Services\CourseReadiness;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Un curso presencial o híbrido puede ser solo un «boleto»: el contenido en línea es opcional y la página no muestra bloques vacíos. */
class OnsiteTicketCoursesTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.timezone' => 'America/Mexico_City', 'academy.private_mode' => true]);
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function ticket(string $delivery): Course
    {
        $c = $this->course2()->replicate();
        $c->title = 'Boleto ' . $delivery;
        $c->slug = 'boleto-' . uniqid();
        $c->delivery = $delivery;
        $c->status = 'active';
        $c->save();
        $c->pricing()->create(['price' => 650, 'discount' => 0, 'final_price' => 650]);
        $c->sessions()->create([
            'starts_at' => now()->addDays(20), 'ends_at' => now()->addDays(20)->addHours(3), 'timezone' => 'America/Mexico_City',
            'venue_name' => 'Centro Valle', 'address' => 'Av. Gómez Morín 100', 'city' => 'Monterrey', 'capacity' => 40, 'status' => 'scheduled', 'sort' => 1,
        ]);

        return $c;
    }

    public function test_online_lessons_are_optional_for_in_person_and_hybrid_courses(): void
    {
        foreach (['in_person', 'hybrid'] as $delivery) {
            $c = $this->ticket($delivery);
            $checks = collect((new CourseReadiness())->checks($c));
            $lessons = $checks->firstWhere('key', 'lessons');
            $this->assertFalse($lessons['required'], "$delivery: sin lecciones no bloquea la publicación");
            $this->assertTrue($checks->firstWhere('key', 'complete')['ok'], "$delivery: sin lecciones no hay nada incompleto");
        }

        $online = $this->course2();
        $this->assertTrue(collect((new CourseReadiness())->checks($online))->firstWhere('key', 'lessons')['required']);
    }

    public function test_a_ticket_course_page_has_no_empty_curriculum_and_lists_the_dates_in_the_includes_box(): void
    {
        $c = $this->ticket('in_person');

        $html = $this->actingAs($this->student())->get(route('courses.course-detail', ['slug' => $c->slug]))->assertOk()->getContent();

        $this->assertStringNotContainsString('id="course-curriculum"', $html);
        $this->assertStringNotContainsString('href="#course-curriculum"', $html);
        $this->assertStringContainsString('fecha disponible', $html);
        $this->assertStringContainsString('Monterrey', $html);
        $this->assertStringContainsString('Fechas y sede', $html);
    }

    public function test_online_courses_keep_their_curriculum_and_staff_get_a_link_to_the_enrollees(): void
    {
        $online = $this->course2();
        $this->actingAs($this->student())->get(route('courses.course-detail', ['slug' => $online->slug]))->assertOk()->assertSee('id="course-curriculum"', false);

        $c = $this->ticket('in_person');
        $this->actingAs($c->instructor)->get(route('courses.course-detail', ['slug' => $c->slug]))->assertOk()
            ->assertSee('data-testid="see-enrollees"', false)->assertDontSee('Ver curso');
    }
}
