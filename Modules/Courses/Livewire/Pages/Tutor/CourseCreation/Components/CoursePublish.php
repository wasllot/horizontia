<?php


namespace Modules\Courses\Livewire\Pages\Tutor\CourseCreation\Components;

use Livewire\Component;
use Modules\Courses\Livewire\Traits\AdminAwareCourseRouting;

class CoursePublish extends Component {
    use AdminAwareCourseRouting;

    public $title;
    public $description;

    public function mount($title = '', $description = '') {
        $this->title = $title;
        $this->description = $description;
    }

    public function render() {
        return view('courses::livewire.tutor.course-creation.components.course-publish', [
            'dashboardUrl' => $this->courseListingRoute(),
        ]);
    }
}
