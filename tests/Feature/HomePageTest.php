<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Courses\Models\Course;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
    }

    public function test_carousel_shows_active_courses_and_hides_drafts(): void
    {
        // Regression: the carousel filtered by status = 'published', a value that does not exist (active = 4).
        $draft = Course::create([
            'instructor_id' => $this->tutor()->id, 'title' => 'Borrador Invisible ' . uniqid(), 'description' => 'x',
            'category_id' => $this->category()->id, 'sub_category_id' => $this->category()->id, 'type' => 'article',
            'level' => 'beginner', 'language_id' => 1, 'status' => 'draft',
        ]);

        $home = $this->get('/')->assertOk();

        $home->assertSee($this->course()->title);
        $home->assertSee(route('courses.course-detail', $this->course()->slug), false);
        $home->assertDontSee($draft->title);
        $home->assertSee('Ver todos los cursos');
    }

    public function test_copy_follows_the_academy_mode_flag(): void
    {
        config(['academy.private_mode' => true]);
        $this->get('/')->assertSee('VER CURSOS')->assertSee('Evaluaciones y certificado')->assertDontSee('Tutorías 1 a 1');

        config(['academy.private_mode' => false]);
        $this->get('/')->assertSee('CONTÁCTANOS')->assertSee('Tutorías 1 a 1')->assertDontSee('Evaluaciones y certificado');
    }
}
