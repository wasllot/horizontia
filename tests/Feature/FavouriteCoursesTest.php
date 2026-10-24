<?php

namespace Tests\Feature;

use App\Livewire\Pages\Student\FavouriteCourses;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Like;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** «Mis favoritos» en modo academia: los cursos con corazón, que antes no tenían dónde verse. */
class FavouriteCoursesTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        Like::where('user_id', $this->student()->id)->get()->each->delete();
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function like(Course $c, $user = null): void
    {
        Like::create(['likeable_id' => $c->id, 'likeable_type' => Course::class, 'user_id' => ($user ?? $this->student())->id]);
    }

    public function test_an_empty_list_invites_to_explore_the_catalog(): void
    {
        Livewire::actingAs($this->student())->test(FavouriteCourses::class)->assertSee('Aún no tienes favoritos')->assertSee('Explorar el catálogo');
    }

    public function test_it_lists_only_my_liked_active_courses_and_links_to_each(): void
    {
        $mine = $this->course();
        $other = $this->course2();
        $this->like($mine);
        $this->like($other, $this->demoStudent());   // el corazón de otra persona no cuenta

        $page = Livewire::actingAs($this->student())->test(FavouriteCourses::class)->assertSee(html_entity_decode($mine->title))->assertSee(route('courses.course-detail', ['slug' => $mine->slug]), false);
        $page->assertDontSee('Aún no tienes favoritos');
        $this->assertCount(1, $page->viewData('courses'));

        $mine->update(['status' => 'inactive']);
        Livewire::actingAs($this->student())->test(FavouriteCourses::class)->assertSee('Aún no tienes favoritos');
    }

    public function test_removing_a_favourite_only_touches_mine(): void
    {
        $course = $this->course();
        $this->like($course);
        $this->like($course, $this->demoStudent());

        Livewire::actingAs($this->student())->test(FavouriteCourses::class)->call('remove', $course->id)->assertSee('Aún no tienes favoritos');

        $this->assertSame(0, Like::where('user_id', $this->student()->id)->where('likeable_id', $course->id)->count());
        $this->assertSame(1, Like::where('user_id', $this->demoStudent()->id)->where('likeable_id', $course->id)->count(), 'someone else keeps theirs');
    }

    public function test_the_page_is_reachable_by_students_in_academy_mode_and_listed_in_the_menu(): void
    {
        config(['academy.private_mode' => true]);
        $this->actingAs($this->student())->get(route('student.favourite-courses'))->assertOk()->assertSee('Mis favoritos');
        $this->actingAs($this->student())->get(route('courses.course-list'))->assertOk()->assertSee(route('student.favourite-courses'), false)->assertDontSee(route('student.favourites'), false);
        auth()->logout();
        $this->get(route('student.favourite-courses'))->assertRedirect();   // sin sesión
    }
}
