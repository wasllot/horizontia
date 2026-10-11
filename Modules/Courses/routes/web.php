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
    Route::get('/cursos', SearchCourses::class)->name('search-courses');
    Route::get('/curso/{slug}', CourseDetails::class)->name('course-detail');
    Route::get('/paquetes', function () {
        $service = app(\Modules\Courses\Services\BundleService::class);
        $bundles = \Modules\Courses\Models\Bundle::active()->with('courses.pricing', 'courses.thumbnail')->orderBy('sort')->orderByDesc('id')->get()
            ->filter(fn ($b) => $service->isSellable($b))
            ->each(function ($b) use ($service) { $b->list = $service->listPrice($b); $b->percent = $service->savingsPercent($b); })->values();

        return view('courses::bundles-index', compact('bundles'));
    })->name('bundles.index');
    Route::get('/paquete/{slug}', \Modules\Courses\Livewire\Pages\Bundle\BundleDetails::class)->name('bundles.show');

    Route::middleware(['auth', 'verified', 'onlineUser', 'role:tutor'])->name('tutor.')->group(function () {
        Route::get('/crear-curso', CreateCourse::class)->name('create-course');
        Route::get('/gestionar-cursos', CourseListing::class)->name('courses');
        Route::get('/curso/editar/{tab}/{id}', CreateCourse::class)->name('edit-course');
        Route::get('/tutor/programar-transmision', \Modules\Courses\Livewire\Pages\Tutor\LiveStreams\ScheduleLiveStream::class)->name('schedule-live-stream');
        Route::get('/tutor/transmisiones', \Modules\Courses\Livewire\Pages\Tutor\LiveStreams\ManageLiveStreams::class)->name('manage-live-streams');
        Route::get('/tutor/tareas', CourseAssignments::class)->name('assignments');
        Route::get('/tutor/estudio-de-contenido', \App\Livewire\Pages\Common\ContentStudio\ContentStudio::class)->name('content-studio');
        Route::get('/curso/{course}/inscritos', \Modules\Courses\Livewire\Pages\Common\CourseEnrollees::class)->whereNumber('course')->name('course-enrollees');
    });

    $middleware = ['auth', 'verified', 'role:admin|sub_admin'];
    if(class_exists('App\Http\Middleware\PermitOfMiddleware')){
        $middleware[] = 'permit-of:can-manage-courses';
    }
    Route::middleware($middleware)->name('admin.')->group(function () {
        Route::get('/admin/cursos', AdminCourseListing::class)->name('courses');
        Route::get('/admin/inscripciones', CourseEnrollments::class)->name('course-enrollments');
        Route::get('/admin/curso/{course}/inscritos', \Modules\Courses\Livewire\Pages\Common\CourseEnrollees::class)->whereNumber('course')->name('course-enrollees');
        Route::get('/admin/categorias', Categories::class)->name('categories');
        Route::get('/admin/paquetes-de-cursos', \Modules\Courses\Livewire\Pages\Admin\Bundles::class)->name('bundles');
        Route::get('/admin/comision-de-cursos', CommissionSettings::class)->name('commission-setting');
        Route::get('/admin/crear-curso', CreateCourse::class)->name('create-course');
        Route::get('/admin/curso/editar/{tab}/{id}', CreateCourse::class)->name('edit-course');
    });

    Route::get('/aula/{slug}', CourseTaking::class)->middleware(['auth', 'verified'])->name('course-taking');
    Route::get('/mis-cursos', CourseList::class)->middleware(['auth', 'verified', 'role:student'])->name('course-list');

    Route::get('curso/{course}/inscritos.csv', [\Modules\Courses\Http\Controllers\EnrolleesExportController::class, 'csv'])->middleware(['auth', 'verified'])->whereNumber('course')->name('enrollees.csv');
    Route::get('fecha-presencial/{session}/calendario.ics', [\Modules\Courses\Http\Controllers\OnsiteDateController::class, 'ics'])->middleware(['auth', 'verified'])->name('onsite.ics');

    Route::get('sesion-en-vivo/{stream}/calendario.ics', [\Modules\Courses\Http\Controllers\LiveSessionController::class, 'ics'])->middleware(['auth', 'verified'])->name('live.ics');

    // SCORM API Routes
    Route::middleware(['auth', 'verified'])->prefix('scorm')->name('scorm.')->group(function () {
        Route::get('/progress/{curriculumId}', [ScormController::class, 'getProgress'])->name('get-progress');
        Route::post('/progress/{curriculumId}', [ScormController::class, 'saveProgress'])->name('save-progress');
    });

    Route::get('contenido-scorm/{curriculumId}/{path}', [\Modules\Courses\Http\Controllers\ScormAssetController::class, 'show'])
        ->where('path', '.*')->middleware('auth')->name('scorm.asset');

    Route::get('video-seguro/{path}', [VideoController::class, 'play'])->where('path', '.*')->name('secure.video');

    Route::middleware(['auth', 'verified'])->post('/editor/upload-image', function (Request $request) {
        $request->validate(['image' => 'required|image|max:5120']);
        $path = $request->file('image')->store('editor-images', getStorageDisk());
        return response()->json(['url' => mediaUrl($path)]);
    })->name('editor.upload-image');
});
