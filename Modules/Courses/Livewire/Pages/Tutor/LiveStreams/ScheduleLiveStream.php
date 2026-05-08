<?php

namespace Modules\Courses\Livewire\Pages\Tutor\LiveStreams;

use Modules\Courses\Models\Course;
use Modules\Courses\Models\CourseLiveStream;
use Livewire\Component;
use Livewire\Attributes\Layout;

class ScheduleLiveStream extends Component
{
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
        $this->courses = Course::where('instructor_id', auth()->id())
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

        $liveStream = CourseLiveStream::create([
            'course_id' => $this->course_id,
            'title' => $this->title,
            'description' => $this->description,
            'meeting_link' => $this->meeting_link,
            'date_time' => $this->date_time,
            'duration_minutes' => $this->duration_minutes,
            'status' => CourseLiveStream::STATUS_SCHEDULED,
            'notify_hours_before' => $this->notify_hours_before,
        ]);

        // Dispatch email notification here later...
        // event(new LiveStreamScheduledEvent($liveStream));
        $course = Course::with('enrollments.student.profile')->find($this->course_id);
        if ($course) {
            foreach ($course->enrollments as $enrollment) {
                if ($enrollment->student && $enrollment->student->email) {
                    $studentName = $enrollment->student->profile?->full_name ?? $enrollment->student->name ?? 'Estudiante';
                    \Illuminate\Support\Facades\Mail::to($enrollment->student->email)
                        ->queue(new \App\Mail\LiveStreamScheduledEmail($liveStream, $studentName));
                }
            }
        }

        // Let the user know it was saved
        session()->flash('success', 'En Vivo programado con éxito.');
        $this->dispatch('showAlertMessage', type: 'success', title: 'Éxito', message: 'La sesión en vivo ha sido programada.');

        $this->reset(['course_id', 'title', 'description', 'meeting_link', 'date_time']);
    }
}
