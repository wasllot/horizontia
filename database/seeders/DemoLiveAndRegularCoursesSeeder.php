<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Storage;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\CourseLiveStream;
use Modules\Courses\Models\Curriculum;

/**
 * Extra demo courses for manual testing: regular self-paced courses and courses with live sessions
 * (upcoming + one already held with its recording). Idempotent. Run after DemoAcademySeeder:
 *   php artisan db:seed --class=DemoLiveAndRegularCoursesSeeder
 */
class DemoLiveAndRegularCoursesSeeder extends DemoAcademySeeder
{
    public function run(): void
    {
        $instructor = $this->user('instructora@horizontia.test', 'tutor', 'Valeria', 'Ortega');
        $student = $this->user('alumno.pagado@horizontia.test', 'student', 'Andrés', 'Pagado');
        $free = $this->user('alumno.gratis@horizontia.test', 'student', 'Camila', 'Gratis');
        $business = $this->category('Negocios y Marketing', 'negocios-marketing');
        $tech = $this->category('Tecnología y Productividad', 'tecnologia-productividad');
        $creative = $this->category('Diseño y Creatividad', 'diseno-creatividad');

        // ---- Regular (self-paced) courses
        $finanzas = $this->course($instructor, $business, 'prueba-finanzas-emprendedores', 'Finanzas Personales para Emprendedores',
            'Ordena tu dinero y el de tu negocio en 3 módulos', 999, 15, 'active', 'beginner', 2);
        $python = $this->course($instructor, $tech, 'prueba-python-gratis', 'Introducción a Python (curso gratuito)',
            'Escribe tu primer programa y automatiza tareas', 0, 0, 'active', 'beginner', 3);
        $foto = $this->course($instructor, $creative, 'prueba-fotografia-celular', 'Fotografía con tu Celular',
            'Composición, luz y edición para fotos que destacan', 649, 0, 'active', 'beginner', 1);

        $s = $this->section($finanzas, 'Tu punto de partida', 1);
        $this->lesson($s, 'Bienvenida y objetivos', 'article', 1, ['article_content' => '<h3>Bienvenido</h3><p>Al final tendrás un presupuesto y un fondo de emergencia.</p>', 'is_preview' => true]);
        $this->lesson($s, 'Por qué importa el flujo de efectivo', 'yt_link', 2, ['media_path' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'is_preview' => true]);
        $s = $this->section($finanzas, 'Presupuesto', 2);
        $this->lesson($s, 'La regla 50/30/20 (video)', 'video', 1, ['media_path' => $this->demoVideo(1)]);
        $this->lesson($s, 'Plantilla de presupuesto', 'article', 2, ['article_content' => '<p>Descarga la plantilla y registra tus gastos del último mes.</p>']);
        $this->lesson($s, 'Evaluación del módulo', 'assignment', 3, ['article_content' => $this->quiz(), 'content_length' => 180]);

        $s = $this->section($python, 'Primeros pasos', 1);
        $this->lesson($s, 'Instala Python', 'article', 1, ['article_content' => '<p>Descarga Python desde python.org y verifica con <code>python --version</code>.</p>', 'is_preview' => true]);
        $this->lesson($s, 'Variables y tipos (video)', 'video', 2, ['media_path' => $this->demoVideo(2)]);
        $this->lesson($s, 'Quiz rápido', 'assignment', 3, ['article_content' => $this->quiz(), 'content_length' => 180]);

        $s = $this->section($foto, 'Composición', 1);
        $this->lesson($s, 'La regla de los tercios', 'article', 1, ['article_content' => '<p>Coloca el sujeto en las intersecciones de la cuadrícula.</p>', 'is_preview' => true]);
        $this->lesson($s, 'Luz natural (video)', 'video', 2, ['media_path' => $this->demoVideo(3)]);
        $this->lesson($s, 'Edición en el celular', 'yt_link', 3, ['media_path' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);

        // ---- Courses with live sessions
        $ventas = $this->course($instructor, $business, 'prueba-taller-vivo-ventas', 'Taller en Vivo: Ventas Consultivas',
            'Tres sesiones en vivo con práctica y retroalimentación', 1299, 0, 'active', 'intermediate', 1);
        $s = $this->section($ventas, 'Antes de empezar', 1);
        $this->lesson($s, 'Cómo funcionan las sesiones', 'article', 1, ['article_content' => '<p>Entra 15 minutos antes con el enlace de cada sesión. Quedan grabadas.</p>', 'is_preview' => true]);
        $s = $this->section($ventas, 'Sesiones en vivo', 2);
        $this->live($ventas, $s, 'Sesión 1: Diagnóstico del cliente', 1, now()->addDays(3)->setTime(19, 0), 90, 'https://meet.google.com/abc-defg-hij');
        $this->live($ventas, $s, 'Sesión 2: Manejo de objeciones', 2, now()->addDays(10)->setTime(19, 0), 90, 'https://meet.google.com/abc-defg-hij');
        $this->live($ventas, $s, 'Sesión 3: Cierre y seguimiento', 3, now()->addDays(17)->setTime(19, 0), 90, 'https://meet.google.com/abc-defg-hij');
        $s = $this->section($ventas, 'Material de apoyo', 3);
        $this->lesson($s, 'Guion de llamada', 'article', 1, ['article_content' => '<p>Preguntas clave para descubrir la necesidad del cliente.</p>']);

        $excel = $this->course($instructor, $tech, 'prueba-mentoria-vivo-excel', 'Mentoría en Vivo: Excel Avanzado',
            'Resuelve tus casos reales con la instructora, en directo', 799, 10, 'active', 'expert', 2);
        $s = $this->section($excel, 'Sesiones', 1);
        $this->live($excel, $s, 'Sesión 0: Repaso de fórmulas (ya realizada)', 1, now()->subDays(5)->setTime(18, 0), 60, 'https://us02web.zoom.us/j/123456789', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');
        $this->live($excel, $s, 'Sesión 1: Tablas dinámicas y Power Query', 2, now()->addDays(2)->setTime(18, 0), 60, 'https://us02web.zoom.us/j/123456789');
        $this->live($excel, $s, 'Sesión 2: Tablero de control', 3, now()->addDays(9)->setTime(18, 0), 60, 'https://us02web.zoom.us/j/123456789');

        $ia = $this->course($instructor, $tech, 'prueba-masterclass-vivo-ia', 'Masterclass en Vivo: IA para tu Negocio (gratis)',
            'Una hora para empezar a usar IA en tu día a día', 0, 0, 'active', 'beginner', 3);
        $s = $this->section($ia, 'Masterclass', 1);
        $this->live($ia, $s, 'Masterclass: IA para tu negocio', 1, now()->addDays(6)->setTime(20, 0), 60, 'https://teams.microsoft.com/l/meetup-join/demo');
        $this->lesson($s, 'Recursos para después', 'article', 2, ['article_content' => '<p>Lista de herramientas y prompts para empezar.</p>']);

        // Access for manual testing: the paid student owns the live sales workshop; the free student the free ones.
        $this->enroll($student, $ventas);
        $this->enroll($student, $python);
        $this->enroll($free, $ia);
        $this->enroll($free, $python);

        // A bundle for manual testing (published online courses with a price).
        $pack = \Modules\Courses\Models\Bundle::firstOrCreate(['slug' => 'prueba-pack-negocio-digital'], [
            'title' => 'Pack Negocio Digital', 'subtitle' => 'Marketing y finanzas para arrancar tu negocio', 'status' => 'active', 'price' => 1699, 'sort' => 1,
            'description' => "Dos cursos que se complementan: aprende a atraer clientes y a cuidar tu dinero.\nAcceso inmediato y garantía de reembolso.",
        ]);
        $pack->courses()->syncWithoutDetaching(array_filter([
            \Modules\Courses\Models\Course::where('slug', 'demo-marketing-digital')->value('id') => ['sort' => 1],
            $finanzas->id => ['sort' => 2],
        ], fn ($v, $k) => (bool) $k, ARRAY_FILTER_USE_BOTH));

        $this->refreshCovers();
        $this->retireThemeCourses();

        $this->command?->info('Cursos de prueba listos: 3 normales y 3 con sesiones en vivo (alumno.pagado tiene el taller de ventas).');
    }

    /** A live lesson plus its session (past dates are stored as already held, with the recording when given). */
    private function live(Course $course, $section, string $title, int $order, $when, int $minutes, string $link, ?string $recording = null): void
    {
        $lesson = $this->lesson($section, $title, 'live', $order, ['content_length' => $minutes * 60]);
        if (!Curriculum::whereKey($lesson->id)->exists() || $lesson->liveStream()->exists()) {
            return;
        }
        $past = $when->isPast();
        CourseLiveStream::create([
            'course_id' => $course->id,
            'curriculum_id' => $lesson->id,
            'title' => $title,
            'description' => 'Trae tus dudas y casos reales: la sesión es práctica.',
            'meeting_link' => $link,
            'provider' => \Modules\Courses\Services\LiveSessionService::providerFor($link),
            'recording_url' => $past ? $recording : null,
            'date_time' => $when,
            'duration_minutes' => $minutes,
            'notify_hours_before' => 24,
            'status' => $past ? CourseLiveStream::STATUS_COMPLETED : CourseLiveStream::STATUS_SCHEDULED,
        ]);
    }
}
