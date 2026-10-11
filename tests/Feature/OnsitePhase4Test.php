<?php

namespace Tests\Feature;

use App\Mail\OnsiteDateEmail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Modules\Courses\Livewire\Pages\Common\CourseEnrollees;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\CourseSession;
use Modules\Courses\Models\Enrollment;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Cursos presenciales, fase 4: pantalla «Inscritos» por fecha, CSV, mover, avisar cambio y cancelar fecha. */
class OnsitePhase4Test extends TestCase
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

    /** @return array{0: Course, 1: CourseSession, 2: CourseSession, 3: Enrollment, 4: Enrollment} course, date A (cap 2), date B (cap 1), student in A, demo student in B */
    private function setUpCourse(): array
    {
        $c = $this->course2()->replicate();
        $c->title = 'Taller Fase Cuatro';
        $c->slug = 'f4-' . uniqid();
        $c->delivery = 'in_person';
        $c->status = 'active';
        $c->save();
        $c->pricing()->create(['price' => 500, 'discount' => 0, 'final_price' => 500]);
        $mk = fn ($days, $city, $cap) => $c->sessions()->create([
            'starts_at' => now()->addDays($days), 'ends_at' => now()->addDays($days)->addHours(4), 'timezone' => 'America/Mexico_City',
            'venue_name' => 'Sede ' . $city, 'address' => 'Calle 1', 'city' => $city, 'capacity' => $cap, 'status' => 'scheduled', 'sort' => $days,
        ]);
        [$a, $b] = [$mk(10, 'Guadalajara', 2), $mk(20, 'Monterrey', 1)];
        Enrollment::whereIn('student_id', [$this->student()->id, $this->demoStudent()->id])->where('course_id', $c->id)->delete();
        $e1 = Enrollment::create(['student_id' => $this->student()->id, 'course_id' => $c->id, 'session_id' => $a->id, 'tutor_id' => $c->instructor_id, 'status' => 'active']);
        $e2 = Enrollment::create(['student_id' => $this->demoStudent()->id, 'course_id' => $c->id, 'session_id' => $b->id, 'tutor_id' => $c->instructor_id, 'status' => 'active']);

        return [$c, $a, $b, $e1, $e2];
    }

    private function screen(Course $course, ?\App\Models\User $user = null, array $query = [])
    {
        return Livewire::withQueryParams($query)->actingAs($user ?? $course->instructor)->test(CourseEnrollees::class, ['course' => $course->id]);
    }

    // ------------------------------------------------------------ access

    public function test_only_the_course_instructor_and_admins_can_open_the_screen_and_the_csv(): void
    {
        [$course] = $this->setUpCourse();
        $other = $this->tutor()->id === $course->instructor_id ? $this->demoTutor() : $this->tutor();

        $this->actingAs($course->instructor)->get(route('courses.tutor.course-enrollees', $course->id))->assertOk()->assertSee('Inscritos')->assertSee('Taller Fase Cuatro');
        $this->actingAs($this->admin())->get(route('courses.admin.course-enrollees', $course->id))->assertOk();
        $this->actingAs($this->student())->get(route('courses.tutor.course-enrollees', $course->id))->assertForbidden();
        $this->actingAs($other)->get(route('courses.tutor.course-enrollees', $course->id))->assertForbidden();
        $this->actingAs($other)->get(route('courses.enrollees.csv', $course->id))->assertForbidden();
        $this->actingAs($this->student())->get(route('courses.enrollees.csv', $course->id))->assertForbidden();
    }

    public function test_guests_are_sent_to_login(): void
    {
        [$course] = $this->setUpCourse();

        $this->get(route('courses.enrollees.csv', $course->id))->assertRedirect();
        $this->get(route('courses.tutor.course-enrollees', $course->id))->assertRedirect();
    }

    // ------------------------------------------------------------ list, filters, csv

    public function test_it_lists_enrollees_by_date_with_filter_and_search(): void
    {
        [$course, $a, $b] = $this->setUpCourse();
        $student = $this->student();

        $this->screen($course)->assertSee($student->email)->assertSee($this->demoStudent()->email)
            ->assertSee('Guadalajara')->assertSee('Monterrey')->assertSee('Agotada')
            ->set('dateFilter', (string) $a->id)->assertSee($student->email)->assertDontSee($this->demoStudent()->email)
            ->set('dateFilter', '')->set('search', $this->demoStudent()->email)->assertSee($this->demoStudent()->email)->assertDontSee($student->email);

        // The date can also come from the URL (the link of the dates step).
        $this->screen($course, null, ['fecha' => $b->id])->assertSee($this->demoStudent()->email)->assertDontSee($student->email);
    }

    public function test_the_csv_has_the_enrollees_of_the_date_and_neutralises_formulas(): void
    {
        [$course, $a] = $this->setUpCourse();
        $student = $this->student();
        $student->profile->update(['first_name' => '=HYPERLINK("http://x")', 'last_name' => 'Pérez']);

        $res = $this->actingAs($course->instructor)->get(route('courses.enrollees.csv', ['course' => $course->id, 'fecha' => $a->id]))->assertOk();
        $this->assertStringStartsWith('text/csv', $res->headers->get('Content-Type'));
        $csv = $res->streamedContent();
        $this->assertStringContainsString('Nombre,Correo', $csv);
        $this->assertStringContainsString($student->email, $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv, 'a spreadsheet formula must not be executed');
        $this->assertStringNotContainsString($this->demoStudent()->email, $csv, 'only the filtered date');
        $this->assertStringContainsString('Guadalajara', $csv);
    }

    // ------------------------------------------------------------ move

    public function test_an_enrollee_can_be_moved_to_a_date_with_room_and_is_notified(): void
    {
        Mail::fake();
        [$course, $a, $b, $e1] = $this->setUpCourse();
        $third = $course->sessions()->create([
            'starts_at' => now()->addDays(30), 'ends_at' => now()->addDays(30)->addHours(4), 'timezone' => 'America/Mexico_City',
            'venue_name' => 'Sede Puebla', 'address' => 'Calle 3', 'city' => 'Puebla', 'capacity' => null, 'status' => 'scheduled', 'sort' => 30,
        ]);
        $e1->update(['reminded_at' => now()]);

        $this->screen($course)->call('move', $e1->id, $b->id);   // Monterrey is full (1/1)
        $this->assertSame($a->id, $e1->fresh()->session_id, 'cannot move into a full date');

        $this->screen($course)->call('move', $e1->id, $third->id);
        $this->assertSame($third->id, $e1->fresh()->session_id);
        $this->assertNull($e1->fresh()->reminded_at, 'the 24 h reminder follows the new date');
        Mail::assertQueued(OnsiteDateEmail::class, fn (OnsiteDateEmail $m) => $m->kind === 'changed' && $m->session->id === $third->id && $m->hasTo($this->student()->email));
    }

    public function test_moving_rejects_dates_of_other_courses_cancelled_dates_and_enrollments_of_other_courses(): void
    {
        Mail::fake();
        [$course, $a, $b, $e1] = $this->setUpCourse();
        [$other, $oa] = $this->setUpCourse();
        $cancelled = $course->sessions()->create([
            'starts_at' => now()->addDays(40), 'timezone' => 'America/Mexico_City', 'venue_name' => 'X', 'address' => 'X', 'city' => 'X', 'status' => 'cancelled', 'sort' => 40,
        ]);

        $screen = $this->screen($course);
        $screen->call('move', $e1->id, $oa->id);
        $screen->call('move', $e1->id, $cancelled->id);
        $this->assertSame($a->id, $e1->fresh()->session_id);

        $foreignEnrollment = Enrollment::where('course_id', $other->id)->first();
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $screen->call('move', $foreignEnrollment->id, $a->id);
    }

    // ------------------------------------------------------------ notice and cancel

    public function test_a_change_notice_goes_to_the_enrollees_of_that_date_only(): void
    {
        Mail::fake();
        [$course, $a] = $this->setUpCourse();

        $this->screen($course)->call('openNotice', $a->id)->set('notice', '')->call('sendNotice')->assertHasErrors(['notice']);
        $this->screen($course)->call('openNotice', $a->id)->set('notice', 'Cambió la sala: ahora es la B.')->call('sendNotice')->assertHasNoErrors();

        Mail::assertQueued(OnsiteDateEmail::class, 1);
        Mail::assertQueued(OnsiteDateEmail::class, function (OnsiteDateEmail $m) {
            $this->assertSame('changed', $m->kind);
            $this->assertStringContainsString('sala: ahora es la B', $m->render());

            return $m->hasTo($this->student()->email);
        });
    }

    public function test_cancelling_a_date_stops_the_sale_and_warns_its_enrollees(): void
    {
        Mail::fake();
        [$course, $a, $b] = $this->setUpCourse();

        $this->screen($course)->call('openCancel', $b->id)->set('cancelNote', 'Reprogramaremos pronto.')->call('cancelDate');

        $this->assertTrue($b->fresh()->isCancelled());
        $this->assertSame('cancelled', $b->fresh()->unavailableReason());
        Mail::assertQueued(OnsiteDateEmail::class, 1);
        Mail::assertQueued(OnsiteDateEmail::class, fn (OnsiteDateEmail $m) => $m->kind === 'cancelled' && $m->hasTo($this->demoStudent()->email) && $m->attachments() === []);
        $this->assertFalse($a->fresh()->isCancelled());
    }

    public function test_the_actions_cannot_target_a_date_of_another_course(): void
    {
        Mail::fake();
        [$course] = $this->setUpCourse();
        [, $foreign] = $this->setUpCourse();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->screen($course)->call('openCancel', $foreign->id);
    }

    public function test_an_instructor_cannot_run_actions_on_somebody_elses_course(): void
    {
        [$course] = $this->setUpCourse();
        $other = $this->tutor()->id === $course->instructor_id ? $this->demoTutor() : $this->tutor();

        Livewire::actingAs($other)->test(CourseEnrollees::class, ['course' => $course->id])->assertForbidden();
    }

    // ------------------------------------------------------------ entry points

    public function test_the_course_listings_link_to_the_screen(): void
    {
        [$course] = $this->setUpCourse();

        $this->assertStringContainsString("/curso/{$course->id}/inscritos", $this->actingAsInstructorUrl($course));
        $this->actingAs($this->admin());
        $this->assertStringContainsString("/admin/curso/{$course->id}/inscritos", $course->enrolleesUrl(5));
        $this->assertStringContainsString('fecha=5', $course->enrolleesUrl(5));
    }

    private function actingAsInstructorUrl(Course $course): string
    {
        $this->actingAs($course->instructor);

        return $course->enrolleesUrl();
    }
}
