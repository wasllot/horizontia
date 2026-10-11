<?php

namespace Modules\Courses\Livewire\Pages\Tutor\CourseCreation\Components;

use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Courses\Livewire\Traits\AdminAwareCourseRouting;
use Modules\Courses\Livewire\Traits\GuardsCourseAccess;
use Modules\Courses\Models\Course;
use Modules\Courses\Services\CourseAccess;
use Modules\Courses\Services\CourseReadiness;

/**
 * Last step. It shows what is missing and performs the only status change of the wizard:
 * admins publish directly, instructors send the course to review (draft / needs-revision → under review).
 */
class CoursePublish extends Component
{
    use AdminAwareCourseRouting;
    use GuardsCourseAccess;

    #[Locked]
    public $courseId;

    public ?string $done = null;   // published | review | draft

    public function mount(): void
    {
        $this->courseId = request()->route('id');
        $this->guardedCourse($this->courseId);
    }

    public function render()
    {
        $course = Course::findOrFail($this->courseId);
        $checks = (new CourseReadiness())->checks($course);

        return view('courses::livewire.tutor.course-creation.components.course-publish', [
            'course' => $course,
            'checks' => $checks,
            'ready' => !collect($checks)->contains(fn ($c) => $c['required'] && !$c['ok']),
            'isAdmin' => CourseAccess::isAdmin(),
            'dashboardUrl' => $this->courseListingRoute(),
            'publicUrl' => route('courses.course-detail', ['slug' => $course->slug]),
        ]);
    }

    public function publish(): void
    {
        $this->move(CourseAccess::isAdmin() ? 'active' : 'under_review', CourseAccess::isAdmin() ? 'published' : 'review');
    }

    public function backToDraft(): void
    {
        $this->move('draft', 'draft');
    }

    private function move(string $status, string $done): void
    {
        if (isDemoSite()) {
            $this->dispatch('showAlertMessage', type: 'error', title: __('general.demosite_res_title'), message: __('general.demosite_res_txt'));
            return;
        }
        $course = $this->guardedCourse($this->courseId);
        if ($status !== 'draft' && !(new CourseReadiness())->ready($course)) {
            $this->dispatch('showAlertMessage', type: 'error', title: '', message: __('Completa los puntos obligatorios antes de publicar.'));
            return;
        }
        // Instructors cannot publish themselves, and a course already live is not sent back to review.
        if ($status === 'under_review' && in_array($course->status, ['active', 'under_review'], true)) {
            $status = $course->status;
        }
        $course->status = $status;
        $course->save();
        $this->done = $done;
    }
}
