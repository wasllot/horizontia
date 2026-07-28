<?php

namespace Modules\Courses\Livewire\Pages\Tutor\CourseAssignments;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Courses\Models\AssignmentSubmission;
use Modules\Courses\Models\Course;

class CourseAssignments extends Component
{
    use WithPagination;

    public $filterStatus = '';
    public $search = '';
    public $gradingSubmissionId = null;
    public $gradeScore = '';
    public $gradeFeedback = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function openGradingModal($submissionId)
    {
        $this->gradingSubmissionId = $submissionId;
        $submission = AssignmentSubmission::find($submissionId);
        if ($submission) {
            $this->gradeScore = $submission->score ?? '';
            $this->gradeFeedback = $submission->tutor_feedback ?? '';
        }
        $this->dispatch('open-grading-modal');
    }

    public function closeGradingModal()
    {
        $this->gradingSubmissionId = null;
        $this->gradeScore = '';
        $this->gradeFeedback = '';
        $this->dispatch('close-grading-modal');
    }

    public function saveGrade()
    {
        $this->validate([
            'gradeScore' => 'required|numeric|min:0|max:100',
            'gradeFeedback' => 'required|string|max:1000',
        ]);

        $submission = AssignmentSubmission::find($this->gradingSubmissionId);
        if ($submission) {
            $submission->update([
                'score' => $this->gradeScore,
                'tutor_feedback' => $this->gradeFeedback,
                'status' => 'graded'
            ]);
            $this->dispatch('showAlertMessage', type: 'success', title: 'Éxito', message: 'Calificación guardada correctamente.');
        }

        $this->closeGradingModal();
    }

    public function render()
    {
        $tutorId = auth()->id();
        $courseIds = Course::where('instructor_id', $tutorId)->pluck('id');

        $submissionsQuery = AssignmentSubmission::with(['curriculum.section.course', 'user.profile'])
            ->whereHas('curriculum.section', function ($q) use ($courseIds) {
                $q->whereIn('course_id', $courseIds);
            })
            ->latest();

        if ($this->filterStatus) {
            $submissionsQuery->where('status', $this->filterStatus);
        }

        if ($this->search) {
            $submissionsQuery->whereHas('user.profile', function ($q) {
                $q->where('first_name', 'like', '%' . $this->search . '%')
                  ->orWhere('last_name', 'like', '%' . $this->search . '%');
            })->orWhereHas('curriculum', function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%');
            });
        }

        $submissions = $submissionsQuery->paginate(10);

        return view('courses::livewire.tutor.course-assignments.course-assignments', [
            'submissions' => $submissions
        ])->extends('layouts.tutor-app');
    }
}
