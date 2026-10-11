<?php

namespace Modules\Courses\Livewire\Pages\Common;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\CourseSession;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Services\OnsiteNotifier;
use Modules\Courses\Services\OnsiteSeatsService;

/**
 * "Inscritos" of a course, grouped by date for in-person/hybrid courses: who bought which date, move someone to
 * another date, warn a date's attendees about a change, cancel a date (with an automatic notice) and export to CSV.
 * Shared by the instructor (own courses) and admins; authorised again on every Livewire request.
 */
class CourseEnrollees extends Component
{
    #[Locked]
    public int $courseId = 0;

    #[Url(as: 'fecha')]
    public string $dateFilter = '';   // '' = every date, 'sin' = enrolled without a date, or a session id

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $noticeFor = null;     // session id whose "aviso de cambio" form is open
    public string $notice = '';
    public ?int $cancelFor = null;     // session id whose cancellation form is open
    public string $cancelNote = '';

    public function mount($course = null): void
    {
        $this->courseId = (int) ($course ?? request()->route('course'));
        $this->course();
    }

    public function boot(): void
    {
        if ($this->courseId) {
            $this->course();
        }
    }

    private function course(): Course
    {
        $course = Course::findOrFail($this->courseId);
        $user = Auth::user();
        abort_unless($user && ($user->hasRole('admin') || $user->hasRole('sub_admin') || $course->instructor_id === $user->id), 403);

        return $course;
    }

    private function session(int $id): CourseSession
    {
        return $this->course()->sessions()->findOrFail($id);   // a date of another course is a 404
    }

    private function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('showAlertMessage', type: $type, title: $type === 'success' ? __('general.success_title') : 'Atención', message: $message);
    }

    public function updatedDateFilter(): void
    {
        $this->reset('noticeFor', 'cancelFor');
    }

    // ------------------------------------------------------------ actions

    /** Moves one enrollee to another date of the same course (the target must be sellable-sized: not cancelled, with room). */
    public function move(int $enrollmentId, $toSessionId): void
    {
        $course = $this->course();
        $enrollment = Enrollment::where('course_id', $course->id)->where('status', Enrollment::STATUSES['active'])->findOrFail($enrollmentId);
        $target = $course->sessions()->find((int) $toSessionId);

        if (!$target || $target->isCancelled()) {
            $this->toast('Elige una fecha válida y no cancelada.', 'error');

            return;
        }
        if ($enrollment->session_id === $target->id) {
            return;
        }
        if ($target->isSoldOut()) {
            $this->toast('Esa fecha no tiene lugares libres. Aumenta su cupo en el paso «Fechas y sede» o elige otra.', 'error');

            return;
        }

        $enrollment->update(['session_id' => $target->id, 'reminded_at' => null]);
        $user = $enrollment->student()->first()?->user;
        if ($user) {
            OnsiteNotifier::mail($user, $course, $target, 'changed', 'Te cambiamos a esta fecha.');
        }
        $this->toast('Inscrito movido a ' . $target->startsLocal()->locale('es')->translatedFormat('j \d\e F') . '. Le avisamos por correo.');
    }

    public function openNotice(int $sessionId): void
    {
        $this->session($sessionId);
        $this->noticeFor = $sessionId;
        $this->cancelFor = null;
        $this->notice = '';
        $this->resetErrorBag();
    }

    /** E-mails the attendees of a date (with the current data of the date and the instructor's note). */
    public function sendNotice(): void
    {
        $this->validate(['notice' => 'required|string|min:3|max:500'], [], ['notice' => 'mensaje']);
        $date = $this->session((int) $this->noticeFor);
        $sent = OnsiteNotifier::mailEnrollees($date, 'changed', trim($this->notice));
        $this->reset('noticeFor', 'notice');
        $this->toast($sent ? "Aviso enviado a {$sent} inscritos." : 'Esta fecha aún no tiene inscritos.');
    }

    public function openCancel(int $sessionId): void
    {
        $this->session($sessionId);
        $this->cancelFor = $sessionId;
        $this->noticeFor = null;
        $this->cancelNote = '';
        $this->resetErrorBag();
    }

    /** Cancels the date (stops selling it) and tells its attendees by e-mail; refunds are made by hand in Mercado Pago. */
    public function cancelDate(): void
    {
        $this->validate(['cancelNote' => 'nullable|string|max:500'], [], ['cancelNote' => 'mensaje']);
        $date = $this->session((int) $this->cancelFor);
        $date->update(['status' => CourseSession::CANCELLED]);
        $sent = OnsiteNotifier::mailEnrollees($date, 'cancelled', trim($this->cancelNote) ?: null);
        $this->reset('cancelFor', 'cancelNote');
        $this->toast($sent
            ? "Fecha cancelada. Avisamos a {$sent} inscritos; gestiona sus reembolsos desde Mercado Pago o muévelos a otra fecha."
            : 'Fecha cancelada.');
    }

    public function closeForms(): void
    {
        $this->reset('noticeFor', 'cancelFor', 'notice', 'cancelNote');
    }

    // ------------------------------------------------------------ data

    private function rowsQuery()
    {
        $term = trim($this->search);

        return Enrollment::query()
            ->where('course_id', $this->courseId)->where('status', Enrollment::STATUSES['active'])
            ->when($this->dateFilter === 'sin', fn ($q) => $q->whereNull('session_id'))
            ->when(ctype_digit($this->dateFilter), fn ($q) => $q->where('session_id', (int) $this->dateFilter))
            ->when($term !== '', fn ($q) => $q->whereHas('student', fn ($s) => $s->where(fn ($w) => $w
                ->where('first_name', 'like', "%{$term}%")->orWhere('last_name', 'like', "%{$term}%")
                ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$term}%")))))
            ->with(['student.user', 'session'])->latest('created_at');
    }

    public function render()
    {
        $course = $this->course();
        $dates = $course->sessions()->withCount(['enrollments as taken' => fn ($q) => $q->where('status', Enrollment::STATUSES['active'])])->get();
        $seats = new OnsiteSeatsService();
        $rows = $this->rowsQuery()->limit(500)->get();
        $withoutDate = $course->isOnsite() ? Enrollment::where('course_id', $course->id)->where('status', Enrollment::STATUSES['active'])->whereNull('session_id')->count() : 0;

        return view('courses::livewire.common.course-enrollees', [
            'course' => $course,
            'dates' => $dates,
            'rows' => $rows,
            'total' => $dates->sum('taken') + $withoutDate,
            'withoutDate' => $withoutDate,
            'oversold' => $dates->filter(fn ($d) => $d->capacity !== null && $d->taken > $d->capacity)->pluck('id')->all(),
            'csvUrl' => route('courses.enrollees.csv', ['course' => $course->id, 'fecha' => $this->dateFilter ?: null]),
            'backUrl' => Auth::user()->hasRole('admin') || Auth::user()->hasRole('sub_admin') ? route('courses.admin.courses') : route('courses.tutor.courses'),
        ])->layout(Auth::user()->hasRole('admin') || Auth::user()->hasRole('sub_admin') ? 'layouts.admin-app' : 'layouts.app');
    }
}
