<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Modules\Courses\Livewire\Pages\Tutor\CourseCreation\Components\CourseSessions;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\CourseSession;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Services\CourseReadiness;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Cursos presenciales, fase 1: modalidad, fechas y sede en el asistente, y reglas de disponibilidad de cada fecha. */
class OnsiteCoursesTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
        config(['academy.timezone' => 'America/Mexico_City']);
    }

    protected function tearDown(): void
    {
        $this->tearDownSeedsPlatformData();
        parent::tearDown();
    }

    private function onsite(string $delivery = 'in_person'): Course
    {
        $course = $this->course()->replicate();
        $course->slug = 'presencial-' . uniqid();
        $course->delivery = $delivery;
        $course->save();

        return $course;
    }

    private function valid(array $over = []): array
    {
        return array_merge([
            'starts_at' => now()->addDays(20)->format('Y-m-d') . 'T09:00', 'ends_at' => now()->addDays(20)->format('Y-m-d') . 'T13:00',
            'schedule_note' => '', 'venue_name' => 'Casa Horizontia', 'address' => 'Calle 1 #23, Col. Centro', 'city' => 'Guadalajara',
            'state' => 'Jalisco', 'maps_url' => '', 'venue_notes' => '', 'capacity' => '', 'sales_close_at' => '',
        ], $over);
    }

    private function datesStep(Course $course, $user = null)
    {
        return Livewire::actingAs($user ?? $this->tutor())->test(CourseSessions::class, ['id' => $course->id]);
    }

    public function test_the_dates_step_only_exists_for_in_person_and_hybrid_courses(): void
    {
        $tutor = $this->tutor();
        $online = $this->course();
        $online->update(['delivery' => 'online']);

        $this->actingAs($tutor)->get(route('courses.tutor.edit-course', ['tab' => 'sessions', 'id' => $online->id]))->assertNotFound();
        $this->actingAs($tutor)->get(route('courses.tutor.edit-course', ['tab' => 'details', 'id' => $online->id]))->assertOk()->assertDontSee('Fechas y sede');

        foreach (['in_person', 'hybrid'] as $delivery) {
            $course = $this->onsite($delivery);
            $this->actingAs($tutor)->get(route('courses.tutor.edit-course', ['tab' => 'sessions', 'id' => $course->id]))->assertOk()->assertSee('Fechas y sede');
        }
    }

    public function test_a_foreign_instructor_cannot_open_or_change_the_dates(): void
    {
        $course = $this->onsite();

        $status = $this->actingAs($this->demoTutor())->get(route('courses.tutor.edit-course', ['tab' => 'sessions', 'id' => $course->id]))->getStatusCode();
        $this->assertContains($status, [403, 404], 'a foreign instructor must not see the dates step');
        $this->datesStep($course, $this->demoTutor())->assertForbidden();
    }

    public function test_dates_are_saved_in_utc_and_shown_in_the_venue_timezone(): void
    {
        $course = $this->onsite();
        $this->datesStep($course)->call('add')->set('form', $this->valid(['starts_at' => '2030-11-16T09:00', 'ends_at' => '2030-11-16T13:00']))->call('save')->assertHasNoErrors();

        $s = $course->sessions()->firstOrFail();
        $this->assertSame('2030-11-16 15:00:00', $s->starts_at->format('Y-m-d H:i:s'), 'Mexico City is UTC-6');
        $this->assertStringContainsString('09:00–13:00', $s->whenLabel());
        $this->assertStringContainsString('sábado 16 de noviembre de 2030', $s->whenLabel());
    }

    public function test_validation_rejects_bad_dates_and_missing_venue(): void
    {
        $course = $this->onsite();
        $c = $this->datesStep($course)->call('add');

        $c->set('form', $this->valid(['starts_at' => now()->subDay()->format('Y-m-d\TH:i')]))->call('save')->assertHasErrors('form.starts_at');
        $c->set('form', $this->valid(['ends_at' => now()->addDays(20)->format('Y-m-d') . 'T08:00']))->call('save')->assertHasErrors('form.ends_at');
        $c->set('form', $this->valid(['venue_name' => '', 'address' => '', 'city' => '']))->call('save')->assertHasErrors(['form.venue_name', 'form.address', 'form.city']);
        $c->set('form', $this->valid(['maps_url' => 'http://insecure.test']))->call('save')->assertHasErrors('form.maps_url');
        $c->set('form', $this->valid(['sales_close_at' => now()->addDays(40)->format('Y-m-d\TH:i')]))->call('save')->assertHasErrors('form.sales_close_at');
        $c->set('form', $this->valid(['capacity' => '0']))->call('save')->assertHasErrors('form.capacity');

        $this->assertSame(0, $course->sessions()->count());
    }

    public function test_dates_can_be_edited_duplicated_cancelled_and_deleted(): void
    {
        $course = $this->onsite();
        $c = $this->datesStep($course)->call('add')->set('form', $this->valid(['capacity' => '10']))->call('save')->assertHasNoErrors();
        $s = $course->sessions()->firstOrFail();

        $c->call('edit', $s->id)->set('form.venue_name', 'Otra sede')->call('save');
        $this->assertSame('Otra sede', $s->fresh()->venue_name);

        $c->call('duplicate', $s->id);
        $this->assertSame(2, $course->sessions()->count());
        $copy = $course->sessions()->reorder()->latest('id')->first();
        $this->assertTrue($copy->starts_at->equalTo($s->starts_at->copy()->addWeek()));

        $c->call('cancelSession', $s->id);
        $this->assertTrue($s->fresh()->isCancelled());
        $c->call('restoreSession', $s->id);
        $this->assertFalse($s->fresh()->isCancelled());

        $c->call('delete', $copy->id);
        $this->assertSame(1, $course->sessions()->count());
    }

    public function test_a_date_with_students_cannot_be_deleted_or_shrunk_below_its_enrolments(): void
    {
        $course = $this->onsite();
        $s = $course->sessions()->create($this->row(['capacity' => 5]));
        foreach ([$this->student(), $this->demoStudent()] as $student) {
            Enrollment::create(['student_id' => $student->id, 'course_id' => $course->id, 'session_id' => $s->id, 'tutor_id' => $course->instructor_id, 'status' => 'active']);
        }

        $c = $this->datesStep($course);
        $c->call('delete', $s->id);
        $this->assertTrue(CourseSession::whereKey($s->id)->exists());

        $c->call('edit', $s->id)->set('form.capacity', '1')->call('save')->assertHasErrors('form.capacity');
        $this->assertSame(5, $s->fresh()->capacity);
    }

    private function row(array $over = []): array
    {
        return array_merge([
            'starts_at' => now()->addDays(20), 'ends_at' => now()->addDays(20)->addHours(4), 'timezone' => 'America/Mexico_City',
            'venue_name' => 'Sede', 'address' => 'Calle 1', 'city' => 'CDMX', 'status' => 'scheduled', 'sort' => 1,
        ], $over);
    }

    public function test_availability_of_a_date(): void
    {
        $course = $this->onsite();

        $open = $course->sessions()->create($this->row(['capacity' => 2]));
        $this->assertTrue($open->isBookable());
        $this->assertSame(2, $open->seatsLeft());

        $unlimited = $course->sessions()->create($this->row());
        $this->assertNull($unlimited->seatsLeft());
        $this->assertFalse($unlimited->isSoldOut());

        foreach ([$this->student(), $this->demoStudent()] as $student) {
            Enrollment::create(['student_id' => $student->id, 'course_id' => $course->id, 'session_id' => $open->id, 'tutor_id' => $course->instructor_id, 'status' => 'active']);
        }
        $this->assertSame('sold_out', $open->fresh()->unavailableReason());

        $closed = $course->sessions()->create($this->row(['sales_close_at' => now()->subHour(), 'starts_at' => now()->addDay()]));
        $this->assertSame('closed', $closed->unavailableReason());

        $past = $course->sessions()->create($this->row(['starts_at' => now()->subDays(2), 'ends_at' => now()->subDays(2)->addHours(3)]));
        $this->assertSame('closed', $past->unavailableReason());
        $this->assertFalse($course->sessions()->upcoming()->get()->contains($past));

        $cancelled = $course->sessions()->create($this->row(['status' => 'cancelled']));
        $this->assertSame('cancelled', $cancelled->unavailableReason());
    }

    public function test_readiness_needs_a_future_date_with_venue_and_no_lessons_for_in_person(): void
    {
        $course = $this->onsite('in_person');
        foreach ($course->sections as $section) {
            $section->curriculums()->delete();
        }
        $readiness = new CourseReadiness();
        $by = fn () => collect($readiness->checks($course->fresh()))->keyBy('key');

        $this->assertFalse($by()['sessions']['ok'], 'no dates yet');
        $this->assertFalse($by()['lessons']['required'], 'an in-person course needs no online lessons');

        $course->sessions()->create($this->row());
        $this->assertTrue($by()['sessions']['ok']);

        $course->update(['delivery' => 'hybrid']);
        $this->assertFalse($by()['lessons']['required'], 'a hybrid course can also be just a ticket: online lessons are optional');

        $online = $this->course();
        $online->update(['delivery' => 'online']);
        $this->assertArrayNotHasKey('sessions', collect($readiness->checks($online->fresh()))->keyBy('key')->all());
    }
}
