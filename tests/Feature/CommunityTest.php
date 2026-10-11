<?php

namespace Tests\Feature;

use App\Livewire\Frontend\CourseCommunity;
use App\Livewire\Pages\Admin\Community\Moderation;
use App\Mail\CommunityQuestionEmail;
use App\Mail\CommunityReplyEmail;
use App\Models\CommunityPost;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Enrollment;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Comunidad del curso: preguntas y respuestas solo para quien tiene acceso, con moderación del instructor. */
class CommunityTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.community' => true]);
        Mail::fake();
        $this->course = $this->course();   // instructor: qa-tutor; alumna inscrita: qa-student
        foreach ([$this->student(), $this->demoStudent(), $this->tutor(), $this->admin()] as $u) {
            RateLimiter::clear('community:' . $u->id);
            Cache::forget('community_daily:' . $u->id . ':' . now()->format('Ymd'));
        }
        Cache::forget('community_notify:' . $this->course->id);
        Enrollment::updateOrCreate(['student_id' => $this->demoStudent()->id, 'course_id' => $this->course->id], ['tutor_id' => $this->course->instructor_id, 'status' => 'active']);
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function as($user)
    {
        return Livewire::actingAs($user)->test(CourseCommunity::class, ['courseId' => $this->course->id]);
    }

    private function question(string $title = '¿Cómo aplico el método?', $by = null): CommunityPost
    {
        return CommunityPost::create(['course_id' => $this->course->id, 'user_id' => ($by ?? $this->student())->id, 'title' => $title, 'body' => 'No me quedó claro el paso 3 de la lección.', 'last_activity_at' => now()]);
    }

    public function test_a_student_asks_and_the_instructor_is_notified_at_most_once_an_hour(): void
    {
        $this->as($this->student())->call('startNew')->set('title', 'Mi duda sobre el curso')->set('body', 'Explico con detalle mi duda aquí')->call('ask')->assertHasNoErrors()->assertSet('mode', 'thread');
        $this->as($this->student())->call('startNew')->set('title', 'Otra duda distinta')->set('body', 'Otra explicación suficientemente larga')->call('ask')->assertHasNoErrors();

        $this->assertSame(2, CommunityPost::whereNull('parent_id')->where('course_id', $this->course->id)->count());
        Mail::assertQueued(CommunityQuestionEmail::class, 1);
        Mail::assertQueued(CommunityQuestionEmail::class, fn ($m) => $m->hasTo($this->tutor()->email));
    }

    public function test_the_question_is_validated_and_html_is_stripped(): void
    {
        $page = $this->as($this->student())->call('startNew');
        $page->set('title', 'abc')->set('body', 'corto')->call('ask')->assertHasErrors(['title', 'body']);

        $page->set('title', '<b>Título válido</b>')->set('body', '<script>alert(1)</script>Texto suficientemente largo')->call('ask')->assertHasNoErrors();
        $post = CommunityPost::latest('id')->first();
        $this->assertSame('Título válido', $post->title);
        $this->assertStringNotContainsString('<script>', $post->body);
    }

    public function test_replies_notify_the_author_but_not_yourself_and_mark_the_instructor(): void
    {
        $q = $this->question();
        $this->as($this->tutor())->call('open', $q->id)->set('replyBody', 'Mira, el paso 3 se resuelve así…')->call('reply')->assertHasNoErrors()->assertSee('Instructor');
        $this->as($this->student())->call('open', $q->id)->set('replyBody', 'Gracias, ya entendí')->call('reply');

        Mail::assertQueued(CommunityReplyEmail::class, 1);   // solo la del instructor; la propia no se avisa
        Mail::assertQueued(CommunityReplyEmail::class, fn ($m) => $m->hasTo($this->student()->email) && $m->byInstructor === true);
        $this->assertSame(2, CommunityPost::where('parent_id', $q->id)->count());
    }

    public function test_the_asker_or_the_instructor_marks_one_solution_and_others_cannot(): void
    {
        $q = $this->question();
        $a = CommunityPost::create(['course_id' => $this->course->id, 'user_id' => $this->tutor()->id, 'parent_id' => $q->id, 'body' => 'Respuesta A', 'last_activity_at' => now()]);
        $b = CommunityPost::create(['course_id' => $this->course->id, 'user_id' => $this->demoStudent()->id, 'parent_id' => $q->id, 'body' => 'Respuesta B', 'last_activity_at' => now()]);

        $this->as($this->demoStudent())->call('markAnswer', $a->id)->assertForbidden();
        $this->as($this->student())->call('markAnswer', $a->id);
        $this->as($this->tutor())->call('markAnswer', $b->id);

        $this->assertFalse($a->fresh()->is_answer);
        $this->assertTrue($b->fresh()->is_answer, 'only one solution per question');
        $this->as($this->student())->call('setFilter', 'solved')->assertSee($q->title);
        $this->as($this->student())->call('setFilter', 'unanswered')->assertDontSee($q->title);
    }

    public function test_only_students_with_access_and_the_course_instructor_get_in(): void
    {
        Livewire::actingAs($this->demoTutor())->test(CourseCommunity::class, ['courseId' => $this->course->id])->assertForbidden();   // otro instructor
        $outsider = $this->course2()->replicate();
        $outsider->title = 'Sin acceso';
        $outsider->slug = 'sa-' . uniqid();
        $outsider->save();
        Enrollment::where('course_id', $outsider->id)->delete();
        Livewire::actingAs($this->student())->test(CourseCommunity::class, ['courseId' => $outsider->id])->assertForbidden();

        $this->as($this->student())->assertOk();
        $this->as($this->tutor())->assertOk();
        $this->as($this->admin())->assertOk();
    }

    public function test_posts_of_other_courses_cannot_be_opened_or_touched(): void
    {
        $other = $this->course2();
        $foreign = CommunityPost::create(['course_id' => $other->id, 'user_id' => $this->tutor()->id, 'title' => 'De otro curso', 'body' => 'Mensaje ajeno', 'last_activity_at' => now()]);

        $page = $this->as($this->student());
        $page->assertDontSee('De otro curso');
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $page->call('open', $foreign->id);
    }

    public function test_authors_and_moderators_can_delete_others_cannot_and_pinning_is_for_moderators(): void
    {
        $q = $this->question();
        $this->as($this->demoStudent())->call('delete', $q->id)->assertForbidden();
        $this->as($this->student())->call('pin', $q->id)->assertForbidden();

        $this->as($this->tutor())->call('open', $q->id)->call('pin', $q->id);
        $this->assertTrue($q->fresh()->pinned);

        CommunityPost::create(['course_id' => $this->course->id, 'user_id' => $this->demoStudent()->id, 'parent_id' => $q->id, 'body' => 'resp', 'last_activity_at' => now()]);
        $this->as($this->student())->call('open', $q->id)->call('delete', $q->id);
        $this->assertNull(CommunityPost::find($q->id));
        $this->assertSame(0, CommunityPost::where('parent_id', $q->id)->count(), 'replies go with the question');
    }

    public function test_pinned_questions_come_first_and_search_filters(): void
    {
        $old = $this->question('Pregunta antigua fijada');
        $old->update(['pinned' => true, 'last_activity_at' => now()->subDays(5)]);
        $this->question('Pregunta reciente sobre excel');

        $this->as($this->student())->assertSeeInOrder(['Pregunta antigua fijada', 'Pregunta reciente sobre excel'])
            ->set('search', 'excel')->assertSee('Pregunta reciente sobre excel')->assertDontSee('Pregunta antigua fijada');
    }

    public function test_likes_toggle_once_per_person_and_never_on_your_own_post(): void
    {
        $q = $this->question();

        $page = $this->as($this->demoStudent())->call('like', $q->id);
        $this->assertSame(1, $q->fresh()->likes_count);
        $page->call('like', $q->id);
        $this->assertSame(0, $q->fresh()->likes_count, 'second click removes it');

        $this->as($this->student())->call('like', $q->id);
        $this->assertSame(0, $q->fresh()->likes_count, 'own post');
    }

    public function test_reports_count_once_per_person_and_reach_the_admin_screen(): void
    {
        $q = $this->question('Mensaje problemático');
        $this->as($this->demoStudent())->call('report', $q->id)->call('report', $q->id);
        $this->as($this->tutor())->call('report', $q->id);
        $this->assertSame(2, $q->fresh()->reports_count);

        $admin = fn () => Livewire::actingAs($this->admin())->test(Moderation::class);
        $admin()->assertSee('Mensaje problemático')->call('hide', $q->id);
        $this->as($this->student())->assertDontSee('Mensaje problemático');
        $admin()->call('restore', $q->id);
        $this->assertNull($q->fresh()->hidden_at);
        $this->as($this->student())->assertSee('Mensaje problemático');
        $q->update(['reports_count' => 2]);
        $admin()->call('dismiss', $q->id);
        $this->assertSame(0, $q->fresh()->reports_count);
        $admin()->call('delete', $q->id);
        $this->assertNull(CommunityPost::find($q->id));
    }

    public function test_posting_is_rate_limited(): void
    {
        $page = $this->as($this->student());
        for ($i = 1; $i <= 4; $i++) {
            $page->call('startNew')->set('title', "Pregunta número $i")->set('body', "Texto suficientemente largo número $i")->call('ask')->assertHasNoErrors();
        }

        $page->call('startNew')->set('title', 'Una más')->set('body', 'Demasiado rápido para publicar')->call('ask')->assertHasErrors('body');
        $this->assertSame(4, CommunityPost::where('user_id', $this->student()->id)->count());
    }

    public function test_the_classroom_has_the_community_tab_and_it_can_be_switched_off(): void
    {
        $this->actingAs($this->student())->get(route('courses.course-taking', ['slug' => $this->course->slug]))->assertOk()->assertSee('data-testid="community-tab"', false)->assertSee('Comunidad del curso');

        config(['academy.community' => false]);
        $this->actingAs($this->student())->get(route('courses.course-taking', ['slug' => $this->course->slug]))->assertOk()->assertDontSee('data-testid="community-tab"', false);
        Livewire::actingAs($this->student())->test(CourseCommunity::class, ['courseId' => $this->course->id])->assertForbidden();
    }

    public function test_only_admins_reach_the_moderation_screen(): void
    {
        $this->assertNotSame(200, $this->actingAs($this->student())->get(route('admin.community'))->getStatusCode());
        Livewire::actingAs($this->student())->test(Moderation::class)->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.community'))->assertOk()->assertSee('Comunidad');
    }
}
