<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Courses\Livewire\Pages\Admin\CommissionSettings;
use Illuminate\Support\Facades\Response;
use Modules\Courses\Livewire\Pages\Admin\CourseEnrollments;
use Modules\Courses\Livewire\Pages\Admin\CourseListing as AdminCourseListing;
use Modules\Courses\Livewire\Pages\Course\CourseDetails;
use Modules\Courses\Livewire\Pages\Search\SearchCourses;
use Modules\Courses\Livewire\Pages\Student\CourseList\CourseList;
use Modules\Courses\Livewire\Pages\Student\CourseTaking\CourseTaking;
use Modules\Courses\Livewire\Pages\Tutor\CourseCreation\CreateCourse;
use Modules\Courses\Livewire\Pages\Tutor\CourseListing\CourseListing;
use Modules\Courses\Livewire\Pages\Tutor\CourseAssignments\CourseAssignments;
use Modules\Courses\Livewire\Pages\Admin\Categories;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Modules\Courses\Http\Controllers\VideoController;
use Modules\Courses\Http\Controllers\ScormController;
use Symfony\Component\HttpFoundation\StreamedResponse;

Route::middleware(['locale', 'maintenance', 'enabled:courses'])->as('courses.')->prefix(config('courses.url_prefix'))->group(function () {
    Route::get('/search-courses', SearchCourses::class)->name('search-courses');
    Route::get('/course/{slug}', CourseDetails::class)->name('course-detail');

    Route::middleware(['auth', 'verified', 'onlineUser', 'role:tutor'])->name('tutor.')->group(function () {
        Route::get('/create-course', CreateCourse::class)->name('create-course');
        Route::get('/courses', CourseListing::class)->name('courses');
        Route::get('/course/edit/{tab}/{id}', CreateCourse::class)->name('edit-course');
        Route::get('/tutor/schedule-live-stream', \Modules\Courses\Livewire\Pages\Tutor\LiveStreams\ScheduleLiveStream::class)->name('schedule-live-stream');
        Route::get('/tutor/manage-live-streams', \Modules\Courses\Livewire\Pages\Tutor\LiveStreams\ManageLiveStreams::class)->name('manage-live-streams');
        Route::get('/tutor/assignments', CourseAssignments::class)->name('assignments');
    });

    $middleware = ['auth', 'verified', 'role:admin|sub_admin'];
    if(class_exists('App\Http\Middleware\PermitOfMiddleware')){
        $middleware[] = 'permit-of:can-manage-courses';
    }
    Route::middleware($middleware)->name('admin.')->group(function () {
        Route::get('/admin/courses', AdminCourseListing::class)->name('courses');
        Route::get('/admin/course-enrollments', CourseEnrollments::class)->name('course-enrollments');
        Route::get('/admin/categories', Categories::class)->name('categories');
        Route::get('/admin/commission-setting', CommissionSettings::class)->name('commission-setting');
        Route::get('/admin/create-course', CreateCourse::class)->name('create-course');
        Route::get('/admin/course/edit/{tab}/{id}', CreateCourse::class)->name('edit-course');
    });

    Route::get('/course-taking/{slug}', CourseTaking::class)->middleware(['auth', 'verified'])->name('course-taking');
    Route::get('/course-list', CourseList::class)->middleware(['auth', 'verified', 'role:student'])->name('course-list');

    // SCORM API Routes
    Route::middleware(['auth', 'verified'])->prefix('scorm')->name('scorm.')->group(function () {
        Route::get('/progress/{curriculumId}', [ScormController::class, 'getProgress'])->name('get-progress');
        Route::post('/progress/{curriculumId}', [ScormController::class, 'saveProgress'])->name('save-progress');
    });

    Route::get('secure-video/{path}', [VideoController::class, 'play'])->middleware('auth')->name('secure.video'); 
});
