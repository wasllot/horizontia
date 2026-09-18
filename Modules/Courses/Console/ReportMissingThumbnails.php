<?php

namespace Modules\Courses\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Modules\Courses\Models\Course;

class ReportMissingThumbnails extends Command
{
    protected $signature   = 'courses:report-missing-thumbnails {--notify : Send email to each affected tutor}';
    protected $description = 'List courses with missing or unregistered thumbnail files, optionally notify tutors.';

    public function handle(): int
    {
        $courses = Course::with(['thumbnail', 'instructor'])->get();
        $disk    = getStorageDisk();

        $affected = $courses->filter(function (Course $course) use ($disk) {
            if (!$course->thumbnail || !$course->thumbnail->path) {
                return true;
            }
            return !Storage::disk($disk)->exists($course->thumbnail->path);
        });

        if ($affected->isEmpty()) {
            $this->info('✓ All courses have valid thumbnails.');
            return self::SUCCESS;
        }

        $this->warn("Found {$affected->count()} course(s) without a valid thumbnail:\n");

        $rows = $affected->map(fn(Course $c) => [
            $c->id,
            mb_strimwidth($c->title, 0, 40, '…'),
            $c->instructor?->name ?? '—',
            $c->instructor?->email ?? '—',
            $c->thumbnail?->path ? 'FILE MISSING' : 'NOT REGISTERED',
        ])->values()->toArray();

        $this->table(['ID', 'Title', 'Instructor', 'Email', 'Issue'], $rows);

        if ($this->option('notify')) {
            $byInstructor = $affected->groupBy('instructor_id');

            foreach ($byInstructor as $instructorId => $courses) {
                $instructor = $courses->first()->instructor;
                if (!$instructor) {
                    continue;
                }

                $courseList = $courses->pluck('title')->implode(', ');

                Mail::raw(
                    "Hola {$instructor->name},\n\n"
                    . "Notamos que los siguientes cursos no tienen imagen de portada:\n\n"
                    . $courses->map(fn($c) => "  • {$c->title}")->implode("\n")
                    . "\n\nPor favor sube una imagen en: Mis Cursos → Editar → Media.\n\n"
                    . "Gracias,\nEquipo Horizontia",
                    fn($message) => $message
                        ->to($instructor->email, $instructor->name)
                        ->subject('Acción requerida: imagen de portada faltante en tus cursos')
                );

                $this->line("  ✉ Notified {$instructor->email} ({$courses->count()} course(s))");
            }
        } else {
            $this->line("\nRun with <comment>--notify</comment> to email each affected tutor.");
        }

        return self::SUCCESS;
    }
}
