<?php

namespace Modules\Courses\Livewire\Pages\Tutor\CourseCreation\Components;

use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Courses\Livewire\Traits\AdminAwareCourseRouting;
use Modules\Courses\Livewire\Traits\GuardsCourseAccess;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\CourseSession;

/**
 * Wizard step "Fechas y sede" of in-person / hybrid courses: the dates a student can choose from when buying.
 * Times are typed in the venue's timezone and stored in UTC.
 */
class CourseSessions extends Component
{
    use AdminAwareCourseRouting;
    use GuardsCourseAccess;

    #[Locked]
    public $courseId;

    #[Locked]
    public ?int $editingId = null;

    public bool $formOpen = false;

    public array $form = [];

    public function mount($id = null): void
    {
        $this->courseId = $id ?? request()->route('id');
        $course = $this->guardedCourse($this->courseId);
        abort_unless($course->isOnsite(), 404);   // online courses have no dates step
        $this->form = $this->blank();
        $this->formOpen = !$course->sessions()->exists();   // first visit: go straight to adding the first date
    }

    private function blank(array $from = []): array
    {
        return $from + [
            'starts_at' => '', 'ends_at' => '', 'schedule_note' => '',
            'venue_name' => '', 'address' => '', 'city' => '', 'state' => '', 'maps_url' => '', 'venue_notes' => '',
            'capacity' => '', 'sales_close_at' => '',
        ];
    }

    private function tz(): string
    {
        return (string) config('academy.timezone', 'America/Mexico_City');
    }

    private function toUtc(?string $local): ?Carbon
    {
        return filled($local) ? Carbon::parse($local, $this->tz())->utc() : null;
    }

    private function toLocalInput(?Carbon $utc): string
    {
        return $utc ? $utc->copy()->setTimezone($this->tz())->format('Y-m-d\TH:i') : '';
    }

    private function sessionOf(int $id): CourseSession
    {
        return $this->guardedCourse($this->courseId)->sessions()->findOrFail($id);
    }

    public function add(): void
    {
        $this->resetErrorBag();
        $last = $this->guardedCourse($this->courseId)->sessions()->latest('starts_at')->first();
        // Reuse the last venue: most editions of a course happen in the same place.
        $this->form = $this->blank($last ? $last->only(['venue_name', 'address', 'city', 'state', 'maps_url', 'venue_notes', 'schedule_note', 'capacity']) + [] : []);
        $this->form['capacity'] = $last?->capacity ?? '';
        $this->editingId = null;
        $this->formOpen = true;
    }

    public function edit(int $id): void
    {
        $s = $this->sessionOf($id);
        $this->resetErrorBag();
        $this->form = $this->blank([
            'starts_at' => $this->toLocalInput($s->starts_at),
            'ends_at' => $this->toLocalInput($s->ends_at),
            'schedule_note' => (string) $s->schedule_note,
            'venue_name' => $s->venue_name, 'address' => $s->address, 'city' => $s->city,
            'state' => (string) $s->state, 'maps_url' => (string) $s->maps_url, 'venue_notes' => (string) $s->venue_notes,
            'capacity' => $s->capacity ?? '', 'sales_close_at' => $this->toLocalInput($s->sales_close_at),
        ]);
        $this->editingId = $s->id;
        $this->formOpen = true;
    }

    public function closeForm(): void
    {
        $this->resetErrorBag();
        $this->formOpen = false;
        $this->editingId = null;
    }

    public function save(): void
    {
        $course = $this->guardedCourse($this->courseId);
        $existing = $this->editingId ? $this->sessionOf($this->editingId) : null;

        $f = $this->form;
        $this->validate([
            'form.starts_at' => 'required|date',
            'form.ends_at' => 'nullable|date',
            'form.schedule_note' => 'nullable|string|max:160',
            'form.venue_name' => 'required|string|max:160',
            'form.address' => 'required|string|max:255',
            'form.city' => 'required|string|max:120',
            'form.state' => 'nullable|string|max:120',
            'form.maps_url' => 'nullable|url:https|max:500',
            'form.venue_notes' => 'nullable|string|max:2000',
            'form.capacity' => 'nullable|integer|min:1|max:100000',
            'form.sales_close_at' => 'nullable|date',
        ], [
            'form.starts_at.required' => 'Indica cuándo empieza.',
            'form.venue_name.required' => 'Escribe el nombre de la sede.',
            'form.address.required' => 'Escribe la dirección.',
            'form.city.required' => 'Escribe la ciudad.',
            'form.maps_url.url' => 'El enlace del mapa debe empezar por https://',
            'form.capacity.min' => 'El cupo debe ser al menos 1 (o déjalo vacío para no limitarlo).',
        ]);

        $starts = $this->toUtc($f['starts_at']);
        $ends = $this->toUtc($f['ends_at']);
        $closes = $this->toUtc($f['sales_close_at']);
        $errors = [];
        $unchanged = $existing && $existing->starts_at->equalTo($starts);
        if ($starts->isPast() && !$unchanged) {
            $errors['form.starts_at'] = 'La fecha de inicio debe ser futura.';
        }
        if ($ends && $ends->lessThanOrEqualTo($starts)) {
            $errors['form.ends_at'] = 'El fin debe ser posterior al inicio.';
        }
        if ($closes && $closes->greaterThan($starts)) {
            $errors['form.sales_close_at'] = 'Las ventas deben cerrar antes de que empiece.';
        }
        if ($existing && filled($f['capacity']) && (int) $f['capacity'] < $existing->seatsTaken()) {
            $errors['form.capacity'] = 'Ya hay ' . $existing->seatsTaken() . ' inscritos: el cupo no puede ser menor.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $data = [
            'starts_at' => $starts, 'ends_at' => $ends, 'timezone' => $this->tz(),
            'schedule_note' => trim(strip_tags((string) $f['schedule_note'])) ?: null,
            'venue_name' => trim(strip_tags($f['venue_name'])), 'address' => trim(strip_tags($f['address'])),
            'city' => trim(strip_tags($f['city'])), 'state' => trim(strip_tags((string) $f['state'])) ?: null,
            'maps_url' => $f['maps_url'] ?: null, 'venue_notes' => trim(strip_tags((string) $f['venue_notes'])) ?: null,
            'capacity' => filled($f['capacity']) ? (int) $f['capacity'] : null, 'sales_close_at' => $closes,
        ];

        $notify = false;
        if ($existing) {
            $existing->fill($data);
            $notify = $existing->seatsTaken() > 0 && $existing->isDirty(['starts_at', 'ends_at', 'venue_name', 'address', 'city', 'maps_url']);
            if ($existing->isDirty('starts_at')) {
                \Modules\Courses\Models\Enrollment::where('session_id', $existing->id)->update(['reminded_at' => null]);   // the 24 h reminder must follow the new time
            }
            $existing->save();
        } else {
            $course->sessions()->create($data + ['status' => CourseSession::SCHEDULED, 'sort' => (int) $course->sessions()->max('sort') + 1]);
        }

        $this->closeForm();
        $this->toast($notify ? 'Fecha guardada. Cambió el horario o la sede y ya hay inscritos: avísales con «Avisar cambio» en Inscritos.' : 'Fecha guardada');
    }

    /** A copy one week later, opened for editing: most editions only change the date. */
    public function duplicate(int $id): void
    {
        $s = $this->sessionOf($id);
        $copy = $s->replicate(['sales_close_at']);
        $copy->starts_at = $s->starts_at->copy()->addWeek();
        $copy->ends_at = $s->ends_at?->copy()->addWeek();
        $copy->status = CourseSession::SCHEDULED;
        $copy->save();
        $this->edit($copy->id);
    }

    public function cancelSession(int $id): void
    {
        $s = $this->sessionOf($id);
        $s->update(['status' => CourseSession::CANCELLED]);
        $n = $s->seatsTaken();
        $sent = $n > 0 ? \Modules\Courses\Services\OnsiteNotifier::mailEnrollees($s->loadMissing('course'), 'cancelled') : 0;
        $this->toast($n > 0 ? "Fecha cancelada. Avisamos por correo a $sent de $n inscritos: gestiona sus reembolsos o muévelos a otra fecha." : 'Fecha cancelada');
    }

    public function restoreSession(int $id): void
    {
        $this->sessionOf($id)->update(['status' => CourseSession::SCHEDULED]);
        $this->toast('Fecha reactivada');
    }

    public function delete(int $id): void
    {
        $s = $this->sessionOf($id);
        if ($s->enrollments()->exists()) {
            $this->toast('Esta fecha tiene inscritos: cancélala en lugar de eliminarla.', 'error');

            return;
        }
        $s->delete();
        if ($this->editingId === $id) {
            $this->closeForm();
        }
        $this->toast('Fecha eliminada');
    }

    public function next()
    {
        $this->guardedCourse($this->courseId);

        return redirect($this->editCourseRoute(isPaidSystem() ? 'pricing' : 'content', $this->courseId));
    }

    private function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('showAlertMessage', type: $type, title: '', message: $message);
    }

    public function render()
    {
        $course = $this->guardedCourse($this->courseId);

        return view('courses::livewire.tutor.course-creation.components.course-sessions', [
            'course' => $course,
            'sessions' => $course->sessions()->withCount(['enrollments as taken' => fn ($q) => $q->where('status', \Modules\Courses\Models\Enrollment::STATUSES['active'])])->get(),
            'tzLabel' => 'hora de ' . trim(str_replace('_', ' ', \Illuminate\Support\Str::afterLast($this->tz(), '/'))),
        ]);
    }
}
