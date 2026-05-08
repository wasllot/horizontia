<?php

namespace Modules\Courses\Livewire\Pages\Tutor\LiveStreams;

use Modules\Courses\Models\Course;
use Modules\Courses\Models\CourseLiveStream;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

class ManageLiveStreams extends Component
{
    public $liveStreams = [];
    public $editingId = null;

    // Edit form fields
    public $edit_course_id = '';
    public $edit_title = '';
    public $edit_description = '';
    public $edit_meeting_link = '';
    public $edit_date_time = '';
    public $edit_duration_minutes = 60;
    public $edit_notify_hours_before = 24;
    public $edit_status = '';

    public $courses = [];

    protected $rules = [
        'edit_course_id'           => 'required|exists:courses_courses,id',
        'edit_title'               => 'required|string|max:255',
        'edit_description'         => 'nullable|string',
        'edit_meeting_link'        => 'nullable|url',
        'edit_date_time'           => 'required|date',
        'edit_duration_minutes'    => 'nullable|integer|min:1',
        'edit_notify_hours_before' => 'nullable|integer|min:0',
        'edit_status'              => 'required|integer',
    ];

    public function mount()
    {
        $this->courses = Course::where('instructor_id', auth()->id())
            ->orderBy('title')->get()
            ->unique(fn($c) => mb_strtolower(trim($c->title)));

        $this->loadList();
    }

    public function loadList()
    {
        $tutorCourseIds = Course::where('instructor_id', auth()->id())->pluck('id');
        $this->liveStreams = CourseLiveStream::with('course')
            ->whereIn('course_id', $tutorCourseIds)
            ->orderByDesc('date_time')
            ->get();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view()->file(
            base_path('Modules/Courses/resources/views/livewire/tutor/live-streams/manage-live-streams.blade.php')
        );
    }

    public function startEdit(int $id)
    {
        $stream = CourseLiveStream::findOrFail($id);
        $this->editingId              = $stream->id;
        $this->edit_course_id         = $stream->course_id;
        $this->edit_title             = $stream->title;
        $this->edit_description       = $stream->description;
        $this->edit_meeting_link      = $stream->meeting_link;
        $this->edit_date_time         = $stream->date_time?->format('Y-m-d\TH:i');
        $this->edit_duration_minutes  = $stream->duration_minutes;
        $this->edit_notify_hours_before = $stream->notify_hours_before;
        $this->edit_status            = $stream->status;
    }

    public function cancelEdit()
    {
        $this->editingId = null;
        $this->reset([
            'edit_course_id','edit_title','edit_description',
            'edit_meeting_link','edit_date_time','edit_duration_minutes',
            'edit_notify_hours_before','edit_status',
        ]);
    }

    public function saveEdit()
    {
        $this->validate();

        $stream = CourseLiveStream::findOrFail($this->editingId);
        $stream->update([
            'course_id'           => $this->edit_course_id,
            'title'               => $this->edit_title,
            'description'         => $this->edit_description,
            'meeting_link'        => $this->edit_meeting_link,
            'date_time'           => $this->edit_date_time,
            'duration_minutes'    => $this->edit_duration_minutes,
            'notify_hours_before' => $this->edit_notify_hours_before,
            'status'              => $this->edit_status,
        ]);

        $this->cancelEdit();
        $this->loadList();
        $this->dispatch('showAlertMessage', type: 'success', title: 'Actualizado', message: 'La sesión en vivo fue actualizada correctamente.');
    }

    public function delete(int $id)
    {
        CourseLiveStream::findOrFail($id)->delete();
        $this->loadList();
        $this->dispatch('showAlertMessage', type: 'success', title: 'Eliminado', message: 'La sesión en vivo fue eliminada.');
    }
}
