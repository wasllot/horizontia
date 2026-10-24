<?php

namespace App\Livewire\Pages\Student;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Like;

/** «Mis favoritos» en modo academia: los cursos a los que el estudiante les dio corazón en el catálogo o en Mis cursos. */
class FavouriteCourses extends Component
{
    public function remove(int $courseId): void
    {
        Like::where('likeable_type', Course::class)->where('likeable_id', $courseId)->where('user_id', Auth::id())->get()->each->delete();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $ids = Like::where('likeable_type', Course::class)->where('user_id', Auth::id())->latest('id')->pluck('likeable_id');

        $courses = Course::query()->with(['thumbnail', 'pricing', 'instructor.profile', 'category'])
            ->whereIn('id', $ids)->where('status', Course::STATUSES['active'])->limit(60)->get()
            ->sortBy(fn ($c) => $ids->search($c->id))->values();

        return view('livewire.pages.student.favourite-courses', ['courses' => $courses]);
    }
}
