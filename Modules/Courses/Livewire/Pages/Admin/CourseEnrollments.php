<?php

namespace Modules\Courses\Livewire\Pages\Admin;

use Modules\Courses\Services\CourseService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class CourseEnrollments extends Component
{
    use WithPagination;

   
    public $search = '';
    public $sortby = 'desc';
    public $status = '';
    private ?CourseService $courseService = null;

    // Manual enrollment form (cortesía, transferencia, corrección de cobro)
    public bool $manualOpen = false;
    public string $manualEmail = '';
    public $manualCourseId = '';
    public $manualSessionId = '';


    public function boot()
    {
        abort_unless(auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('sub_admin'), 403);   // also on every Livewire action
        $this->courseService = new CourseService();
    }

    private function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('showAlertMessage', type: $type, title: $type === 'success' ? __('general.success_title') : 'Atención', message: $message);
    }

    /** Refund of a paid order: through Mercado Pago when possible, otherwise just marks it (the money is returned by hand). */
    public function refund(int $orderId): void
    {
        $order = \App\Models\Order::find($orderId);
        if (!$order) {
            return;
        }
        $result = app(\App\Services\OrderRefundService::class)->refund($order);
        $this->toast($result['message'], $result['ok'] ? 'success' : 'error');
    }

    public function updatedManualCourseId(): void
    {
        $this->manualSessionId = '';
    }

    public function enrollManually(): void
    {
        $this->validate([
            'manualEmail' => 'required|email|exists:users,email',
            'manualCourseId' => 'required|integer|exists:' . (new \Modules\Courses\Models\Course())->getTable() . ',id',
        ], ['manualEmail.exists' => 'No hay ninguna cuenta con ese correo.', 'manualEmail.required' => 'Escribe el correo del alumno.', 'manualCourseId.required' => 'Elige el curso.'], ['manualEmail' => 'correo']);

        $student = \App\Models\User::where('email', $this->manualEmail)->firstOrFail();
        if (!$student->hasRole('student')) {
            $this->addError('manualEmail', 'Esa cuenta no es de un alumno.');

            return;
        }
        $course = \Modules\Courses\Models\Course::findOrFail($this->manualCourseId);
        $date = null;
        if ($course->isOnsite()) {
            $date = $course->sessions()->upcoming()->find((int) $this->manualSessionId);
            if (!$date) {
                $this->addError('manualSessionId', 'Elige la fecha a la que asistirá.');

                return;
            }
        }

        app(\App\Services\OrderRefundService::class)->enrollManually($student, $course, $date);
        $this->reset('manualOpen', 'manualEmail', 'manualCourseId', 'manualSessionId');
        $this->toast('Alumno inscrito en «' . html_entity_decode($course->title) . '».');
    }

    public function mount()
    {
        $this->dispatch('initSelect2', target: '.am-select2');
    }

    #[Layout('layouts.admin-app')]
    public function render()
    {
        $courses = $this->courseService->getCourseEnrollments($this->search, $this->status, $this->sortby);

        $manualCourse = $this->manualCourseId ? \Modules\Courses\Models\Course::find($this->manualCourseId) : null;

        return view('courses::livewire.admin.course-enrollments', [
            'orders' => $courses,
            'courseOptions' => $this->manualOpen ? \Modules\Courses\Models\Course::where('status', \Modules\Courses\Models\Course::STATUSES['active'])->orderBy('title')->get(['id', 'title', 'delivery']) : collect(),
            'manualDates' => $manualCourse?->isOnsite() ? $manualCourse->sessions()->upcoming()->get() : collect(),
        ]);
    }

    public function updated($propertyName)
    {
        $response = isDemoSite();
        if( $response ){
            $this->dispatch('showAlertMessage', type: 'error', title:  __('general.demosite_res_title') , message: __('general.demosite_res_txt'));
            return;
        }
        if (in_array($propertyName, ['status', 'search', 'sortby'])) {
            $this->resetPage();
        }
    }
}
