<?php

namespace Modules\Courses\Livewire\Pages\Tutor\LiveStreams;

use Modules\Courses\Models\Course;
use Modules\Courses\Models\CourseLiveStream;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Modules\Courses\Livewire\Traits\GuardsCourseAccess;
use Modules\Courses\Services\CourseAccess;
use Modules\Courses\Services\LiveSessionService;

class ScheduleLiveStream extends Component
{
    use GuardsCourseAccess;

    public $courses = [];
    public $course_id = '';
    public $title = '';
    public $description = '';
    public $meeting_link = '';
    public $date_time = '';
    public $duration_minutes = 60;
    public $notify_hours_before = 24;

    protected $rules = [
        'course_id' => 'required|exists:courses_courses,id',
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'meeting_link' => 'nullable|url',
        'date_time' => 'required|date|after:now',
        'duration_minutes' => 'nullable|integer|min:1',
        'notify_hours_before' => 'nullable|integer|min:0',
    ];

    public function mount()
    {
        $this->courses = Course::when(!CourseAccess::isAdmin(), fn ($q) => $q->where('instructor_id', auth()->id()))
            ->orderBy('title')
            ->get()
            ->unique(fn($c) => mb_strtolower(trim($c->title)));
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view()->file(
            base_path('Modules/Courses/resources/views/livewire/tutor/live-streams/schedule-live-stream.blade.php')
        );
    }

    public function save()
    {
        $this->validate();
        $course = $this->guardedCourse($this->course_id);   // a posted id must be a course the user manages

        (new LiveSessionService())->create($course, [
            'title' => $this->title,
            'description' => $this->description,
            'meeting_link' => $this->meeting_link,
            'date_time' => $this->date_time,
            'duration_minutes' => $this->duration_minutes,
            'notify_hours_before' => $this->notify_hours_before,
        ]);

        // Let the user know it was saved
        session()->flash('success', 'En Vivo programado con éxito.');
        $this->dispatch('showAlertMessage', type: 'success', title: 'Éxito', message: 'La sesión en vivo ha sido programada.');

        $this->reset(['course_id', 'title', 'description', 'meeting_link', 'date_time']);
    }
}
