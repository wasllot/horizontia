<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Courses\Models\Category;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Models\Media;
use Modules\Courses\Models\Pricing;
use Modules\Courses\Models\Section;
use Modules\Courses\Models\Watchtime;
use Modules\Courses\Services\ScormService;
use ZipArchive;

/**
 * Demo academy: realistic courses (paid, discounted, free, draft) covering every
 * lesson type, plus students in every purchase state, so each feature can be tried by hand.
 *
 *   php artisan db:seed --class=DemoAcademySeeder
 *
 * Idempotent (slugs / e-mails are unique keys) and NON-destructive: it never deletes files
 * or rows it did not create. All demo accounts use the password "Password123!".
 *
 * Accounts
 *   instructora@horizontia.test   instructor of every demo course
 *   alumno.pagado@horizontia.test enrolled through a completed MXN order (course + progress + review)
 *   alumno.gratis@horizontia.test enrolled in the free course only
 *   alumno.nuevo@horizontia.test  no enrollments, has a PENDING order and a cart item (to test paying)
 */
class DemoAcademySeeder extends Seeder
{
    private const PASSWORD = 'Password123!';

    public function run(): void
    {
        $instructor = $this->user('instructora@horizontia.test', 'tutor', 'Valeria', 'Ortega');
        $paid       = $this->user('alumno.pagado@horizontia.test', 'student', 'Andrés', 'Pagado');
        $free       = $this->user('alumno.gratis@horizontia.test', 'student', 'Camila', 'Gratis');
        $newcomer   = $this->user('alumno.nuevo@horizontia.test', 'student', 'Diego', 'Nuevo');

        $business = $this->category('Negocios y Marketing', 'negocios-marketing');
        $tech     = $this->category('Tecnología y Productividad', 'tecnologia-productividad');
        $lang     = $this->category('Idiomas', 'idiomas');

        $marketing = $this->course($instructor, $business, 'demo-marketing-digital', 'Marketing Digital desde Cero',
            'Diseña y lanza tu primera estrategia en 4 semanas', 1499, 20, 'active', 'beginner', 1);
        $excel = $this->course($instructor, $tech, 'demo-excel-gratis', 'Excel Esencial (curso gratuito)',
            'Fórmulas, tablas dinámicas y gráficos para tu día a día', 0, 0, 'active', 'beginner', 2);
        $scorm = $this->course($instructor, $tech, 'demo-seguridad-scorm', 'Seguridad en el Trabajo (SCORM)',
            'Paquete SCORM interactivo con seguimiento de avance', 799, 0, 'active', 'intermediate', 3);
        $english = $this->course($instructor, $lang, 'demo-ingles-a1', 'Inglés Conversacional A1',
            'Primeras conversaciones con confianza', 899, 10, 'active', 'beginner', 1);
        $draft = $this->course($instructor, $business, 'demo-liderazgo-borrador', 'Liderazgo y Comunicación (borrador)',
            'Curso en revisión: no aparece en el catálogo público', 2999, 0, 'under_review', 'expert', 2);

        $this->events($instructor, $business, $tech);
        $this->refreshCovers();
        $this->retireThemeCourses();

        $this->marketingContent($marketing);
        $this->excelContent($excel);
        $this->scormContent($scorm);
        $this->englishContent($english);
        $this->draftContent($draft);

        // Student that bought the marketing course (order completed, access granted, some progress, a review)
        $this->completedOrder($paid, $marketing, 1199.20);
        $this->enroll($paid, $marketing);
        $this->progress($paid, $marketing, 0.5);
        $this->review($paid, $marketing, 5, 'Muy claro y práctico, lo recomiendo.');

        // Free-course student
        $this->enroll($free, $excel);

        // Newcomer: pending Mercado Pago order + cart, nothing unlocked
        $this->pendingOrder($newcomer, $english, 809.10);
        $this->addToCart($newcomer, $scorm);

        // The academy charges in Mexican pesos (Mercado Pago MX): align the platform currency with the demo orders.
        \Larabuild\Optionbuilder\Facades\Settings::set('_general', 'currency', 'MXN');
        \Larabuild\Optionbuilder\Facades\Settings::set('_general', 'date_format', 'd/m/Y');   // numeric dates read the same in Spanish

        $this->command?->info('Demo academy ready. Password for every demo account: ' . self::PASSWORD);
    }

    // ---------------------------------------------------------------- people / catalog

    protected function user(string $email, string $role, string $first, string $last): User
    {
        $user = User::firstOrCreate(['email' => $email], ['password' => Hash::make(self::PASSWORD), 'email_verified_at' => now()]);
        if (!$user->hasRole($role)) {
            $user->assignRole($role);
        }
        $user->profile()->firstOrCreate(['user_id' => $user->id], ['first_name' => $first, 'last_name' => $last, 'verified_at' => now()]);

        return $user;
    }

    private function spanishLanguageId(): int
    {
        // `languages` has no fixed ids: id 1 is Afrikaans, so look Spanish up by name (fall back to the first row).
        return (int) (\Illuminate\Support\Facades\DB::table('languages')->whereIn('name', ['Spanish', 'Español'])->value('id')
            ?? \Illuminate\Support\Facades\DB::table('languages')->value('id'));
    }

    protected function category(string $name, string $slug): Category
    {
        $parent = Category::firstOrCreate(['slug' => $slug], ['name' => $name, 'description' => $name, 'status' => 'active']);
        Category::firstOrCreate(['slug' => $slug . '-general'], ['name' => $name . ' · General', 'description' => $name, 'parent_id' => $parent->id, 'status' => 'active']);

        return $parent;
    }

    protected function course(User $instructor, Category $category, string $slug, string $title, string $subtitle, float $price, int $discount, string $status, string $level, int $thumb): Course
    {
        $sub = Category::where('parent_id', $category->id)->first() ?? $category;

        $course = Course::where('slug', $slug)->first() ?? Course::create([
            'instructor_id'       => $instructor->id,
            'title'               => $title,
            'subtitle'            => $subtitle,
            'description'         => "<p>{$subtitle}.</p> <p>Curso de demostración de Horizontia con contenido, ejercicios y evaluación.</p>",
            'category_id'         => $category->id,
            'sub_category_id'     => $sub->id,
            'tags'                => ['demo', $category->name],
            'type'                => 'all',
            'level'               => $level,
            'discussion_forum'    => true,
            'language_id'         => $this->spanishLanguageId(),
            'learning_objectives' => ['Aplicar lo aprendido en casos reales', 'Completar un proyecto final', 'Obtener tu certificado'],
            'prerequisites'       => 'Ninguno',
            'status'              => $status,
            'content_length'      => 3600,
        ]);
        if ((int) $course->language_id !== $this->spanishLanguageId()) {
            $course->forceFill(['language_id' => $this->spanishLanguageId()])->saveQuietly();
        }
        // `slug` is not mass-assignable (the model derives it from the title): pin the demo slug explicitly.
        if ($course->slug !== $slug) {
            $course->forceFill(['slug' => $slug])->saveQuietly();
        }

        Pricing::firstOrCreate(['course_id' => $course->id], [
            'price' => $price, 'discount' => $discount, 'final_price' => round($price * (100 - $discount) / 100, 2),
        ]);

        $this->thumbnail($course, $thumb);

        return $course;
    }

    protected function thumbnail(Course $course, int $n): void
    {
        $source = public_path("modules/courses/demo-content/images/{$n}.png");
        if (!File::exists($source) || Media::where('mediable_id', $course->id)->where('mediable_type', Course::class)->where('type', 'thumbnail')->exists()) {
            return;
        }
        $path = "media/thumbnails/demo-v2-{$course->slug}.png";   // v2 = branded covers (the theme's "Find out more about" images are gone)
        Storage::disk(getStorageDisk())->put($path, File::get($source));
        Media::create(['mediable_id' => $course->id, 'mediable_type' => Course::class, 'type' => 'thumbnail', 'path' => $path]);
    }

    /**
     * Replaces the demo/theme covers ("Find out more about…") by the branded ones. Only touches demo data: seeder thumbnails
     * and the template courses of the original theme (their instructors are @amentotech.com); a real upload is never replaced.
     */
    protected function refreshCovers(): void
    {
        foreach (Media::where('mediable_type', Course::class)->where('type', 'thumbnail')->get() as $m) {
            $course = Course::withTrashed()->with('instructor')->find($m->mediable_id);
            $isDemo = str_starts_with($m->path, 'media/thumbnails/demo-') && !str_contains($m->path, 'demo-v2-');
            $isTemplate = str_starts_with($m->path, 'courses/') && str_ends_with((string) $course?->instructor?->email, '@amentotech.com');
            if (!$course || !($isDemo || $isTemplate)) {
                continue;
            }
            $source = public_path('modules/courses/demo-content/images/' . (($course->id % 12) + 1) . '.png');
            if (!File::exists($source)) {
                continue;
            }
            $path = "media/thumbnails/demo-v2-{$course->slug}.png";
            Storage::disk(getStorageDisk())->put($path, File::get($source));
            $m->update(['path' => $path]);
        }
    }

    /**
     * The original theme ships 12 English sample courses (instructors @amentotech.com). They are not part of this academy:
     * unpublish them so the catalog only shows real (Spanish) content. Nothing is deleted.
     */
    protected function retireThemeCourses(): void
    {
        $ids = Course::whereHas('instructor', fn ($q) => $q->where('email', 'like', '%@amentotech.com')->where('email', '!=', 'admin@amentotech.com'))
            ->where('status', Course::STATUSES['active'])->pluck('id');
        if ($ids->isNotEmpty()) {
            Course::whereIn('id', $ids)->update(['status' => Course::STATUSES['under_review']]);
        }
    }

    /** In-person / hybrid demo courses with several dates each (the public section is /eventos). */
    private function events(User $instructor, Category $business, Category $tech): void
    {
        $tz = (string) config('academy.timezone', 'America/Mexico_City');
        $when = fn (int $days, string $time = '09:00') => \Carbon\Carbon::parse(now($tz)->addDays($days)->format('Y-m-d') . ' ' . $time, $tz)->utc();
        $rows = [
            ['demo-taller-excel-presencial', $tech, 'Taller presencial de Excel para tu negocio', 'Un sábado intensivo para dominar fórmulas y tablas dinámicas', 1200, 'in_person', 1, [
                [$when(21), 4, 'Casa Horizontia', 'Av. Insurgentes Sur 1234, Col. Del Valle', 'Ciudad de México', 'CDMX', 25],
                [$when(35), 4, 'Casa Horizontia', 'Av. Insurgentes Sur 1234, Col. Del Valle', 'Ciudad de México', 'CDMX', 25],
                [$when(49, '16:00'), 4, 'Coworking Andares', 'Blvd. Puerta de Hierro 4965', 'Guadalajara', 'Jalisco', 20],
            ]],
            ['demo-bootcamp-liderazgo', $business, 'Bootcamp de liderazgo y comunicación', 'Dos jornadas para liderar equipos con confianza', 2800, 'in_person', 2, [
                [$when(28, '10:00'), 8, 'Hotel Fiesta Americana', 'Av. Chapultepec Sur 500', 'Guadalajara', 'Jalisco', 12],
            ]],
            ['demo-masterclass-ventas', $business, 'Masterclass de ventas consultivas', 'Cierra más ventas escuchando mejor a tu cliente', 650, 'in_person', 3, [
                [$when(14, '18:00'), 3, 'Centro de Negocios Valle', 'Av. Gómez Morín 100, San Pedro', 'Monterrey', 'Nuevo León', 40],
                [$when(60, '18:00'), 3, 'Centro de Negocios Valle', 'Av. Gómez Morín 100, San Pedro', 'Monterrey', 'Nuevo León', 40],
            ]],
            ['demo-marketing-hibrido', $business, 'Marketing digital: edición presencial + en línea', 'Clases en vivo en sede y lecciones en línea para practicar', 1999, 'hybrid', 1, [
                [$when(42, '09:30'), 6, 'Casa Horizontia', 'Av. Insurgentes Sur 1234, Col. Del Valle', 'Ciudad de México', 'CDMX', 30],
            ]],
        ];
        foreach ($rows as [$slug, $category, $title, $subtitle, $price, $delivery, $thumb, $dates]) {
            $course = $this->course($instructor, $category, $slug, $title, $subtitle, $price, 0, 'active', 'beginner', $thumb);
            $course->forceFill(['delivery' => $delivery])->saveQuietly();
            foreach ($dates as $i => [$start, $hours, $venue, $address, $city, $state, $capacity]) {
                $course->sessions()->firstOrCreate(['sort' => $i + 1], [
                    'starts_at' => $start, 'ends_at' => $start->copy()->addHours($hours), 'timezone' => $tz,
                    'venue_name' => $venue, 'address' => $address, 'city' => $city, 'state' => $state, 'capacity' => $capacity, 'status' => 'scheduled',
                ]);
            }
        }
    }

    // ---------------------------------------------------------------- content

    protected function section(Course $course, string $title, int $order): Section
    {
        return Section::firstOrCreate(['course_id' => $course->id, 'title' => $title], ['description' => $title]);
    }

    protected function lesson(Section $section, string $title, string $type, int $order, array $extra = []): Curriculum
    {
        return Curriculum::firstOrCreate(['section_id' => $section->id, 'title' => $title], array_merge([
            'description'    => $title,
            'type'           => $type,
            'content_length' => 300,
            'sort_order'     => $order,
            'is_preview'     => false,
        ], $extra));
    }

    protected function quiz(): string
    {
        return json_encode(['questions' => [
            ['question' => '¿Qué significa KPI?', 'options' => ['Key Performance Indicator', 'Know Your Product Intro', 'Keep People Informed'], 'correct' => 0],
            ['question' => '¿Cuál es un canal de pago?', 'options' => ['SEO orgánico', 'Google Ads', 'Boca a boca'], 'correct' => 1],
            ['question' => '¿Qué mide la tasa de conversión?', 'options' => ['Visitas totales', 'Porcentaje que completa una acción', 'Tiempo en página'], 'correct' => 1],
        ]], JSON_UNESCAPED_UNICODE);
    }

    protected function demoVideo(int $n): ?string
    {
        $source = public_path("modules/courses/demo-content/videos/{$n}.mp4");
        if (!File::exists($source)) {
            return null;
        }
        $path = "curriculum_videos/demo-{$n}.mp4";
        $disk = Storage::disk(getPrivateDisk());
        if (!$disk->exists($path)) {
            $disk->put($path, File::get($source));
        }

        return $path;
    }

    private function marketingContent(Course $course): void
    {
        $s1 = $this->section($course, 'Bienvenida', 1);
        $this->lesson($s1, 'Cómo aprovechar este curso', 'article', 1, ['article_content' => '<h3>Bienvenido</h3><p>Resumen del programa y cómo obtener tu certificado.</p>', 'is_preview' => true]);
        $this->lesson($s1, 'Qué es el marketing digital (video de YouTube)', 'yt_link', 2, ['media_path' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'is_preview' => true]);

        $s2 = $this->section($course, 'Estrategia', 2);
        $this->lesson($s2, 'Define a tu cliente ideal (video)', 'video', 1, ['media_path' => $this->demoVideo(1)]);
        $this->lesson($s2, 'Embudo de ventas', 'article', 2, ['article_content' => '<p>Atención, interés, decisión y acción.</p>']);
        $this->lesson($s2, 'Evaluación de la sección', 'assignment', 3, ['article_content' => $this->quiz(), 'content_length' => 180]);

        $s3 = $this->section($course, 'Cierre', 3);
        $this->lesson($s3, 'Plan de acción a 30 días', 'article', 1, ['article_content' => '<p>Descarga la plantilla y define tus metas.</p>']);
    }

    private function excelContent(Course $course): void
    {
        $s = $this->section($course, 'Primeros pasos', 1);
        $this->lesson($s, 'Interfaz y atajos', 'article', 1, ['article_content' => '<p>Atajos esenciales: Ctrl+C, Ctrl+V, F4.</p>', 'is_preview' => true]);
        $this->lesson($s, 'Fórmulas básicas (Vimeo)', 'vm_link', 2, ['media_path' => 'https://vimeo.com/76979871']);
        $this->lesson($s, 'Práctica guiada (video)', 'video', 3, ['media_path' => $this->demoVideo(2)]);
        $this->lesson($s, 'Mini evaluación', 'assignment', 4, ['article_content' => $this->quiz(), 'content_length' => 180]);
    }

    private function scormContent(Course $course): void
    {
        $s = $this->section($course, 'Módulo interactivo', 1);
        $lesson = $this->lesson($s, 'Paquete SCORM: Seguridad básica', 'scorm', 1, ['is_preview' => false]);
        $this->lesson($s, 'Resumen en texto', 'article', 2, ['article_content' => '<p>Puntos clave del módulo.</p>', 'is_preview' => true]);

        if (!empty($lesson->media_path)) {
            return;
        }

        $zip = tempnam(sys_get_temp_dir(), 'demo_scorm') . '.zip';
        $archive = new ZipArchive();
        $archive->open($zip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $manifest = base_path('demo_scorm/imsmanifest.xml');
        $index = base_path('demo_scorm/index.html');
        $archive->addFromString('imsmanifest.xml', File::exists($manifest) ? File::get($manifest) : $this->fallbackManifest());
        $archive->addFromString('index.html', File::exists($index) ? File::get($index) : '<!doctype html><html><body><h1>SCORM demo</h1></body></html>');
        $archive->close();

        $disk = isRemoteDisk(getPrivateDisk()) ? getPrivateDisk() : getStorageDisk();
        Storage::disk($disk)->put('scorm_temp/demo-scorm.zip', File::get($zip));
        @unlink($zip);

        $entry = (new ScormService())->processScormZip('scorm_temp/demo-scorm.zip', $lesson->id);
        if ($entry) {
            $lesson->update(['media_path' => $entry]);
        }
    }

    private function fallbackManifest(): string
    {
        return '<?xml version="1.0"?><manifest identifier="demo" xmlns="http://www.imsproject.org/xsd/imscp_rootv1p1p2"><organizations default="o"><organization identifier="o"><item identifier="i" identifierref="r"><title>Demo</title></item></organization></organizations><resources><resource identifier="r" type="webcontent" href="index.html"><file href="index.html"/></resource></resources></manifest>';
    }

    private function englishContent(Course $course): void
    {
        $s = $this->section($course, 'Greetings', 1);
        $this->lesson($s, 'Hello! Presentaciones', 'article', 1, ['article_content' => '<p><strong>Hello, my name is…</strong></p>', 'is_preview' => true]);
        $this->lesson($s, 'Pronunciación (video)', 'video', 2, ['media_path' => $this->demoVideo(3)]);
        $this->lesson($s, 'Quiz de vocabulario', 'assignment', 3, ['article_content' => $this->quiz(), 'content_length' => 180]);
    }

    private function draftContent(Course $course): void
    {
        $s = $this->section($course, 'Introducción', 1);
        $this->lesson($s, 'Estilos de liderazgo', 'article', 1, ['article_content' => '<p>Contenido en preparación.</p>']);
    }

    // ---------------------------------------------------------------- commerce

    private function order(User $user, Course $course, float $amount, string $status, string $method): Order
    {
        // Deterministic key = idempotent (the `status` column is cast, so it can't be used for lookups).
        $key = 'demo-' . $user->id . '-' . $course->id . '-' . $status;
        $existing = Order::where('unique_payment_id', $key)->first();
        if ($existing) {
            return $existing;
        }

        $order = Order::create([
            'user_id' => $user->id, 'unique_payment_id' => $key, 'amount' => $amount, 'used_wallet_amt' => 0,
            'currency' => 'MXN', 'first_name' => $user->profile?->first_name ?? 'Demo', 'last_name' => $user->profile?->last_name ?? 'Alumno',
            'company' => '', 'country' => 'México', 'state' => 'CDMX', 'postal_code' => '06600', 'city' => 'Ciudad de México',
            'phone' => '5555555555', 'email' => $user->email, 'payment_method' => $method, 'status' => $status,
            'transaction_id' => $status === 'complete' ? 'demo-' . Str::random(10) : null,
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'title' => $course->title, 'quantity' => 1, 'options' => [], 'price' => $amount, 'total' => $amount,
            'orderable_type' => Course::class, 'orderable_id' => $course->id,
        ]);

        return $order;
    }

    private function completedOrder(User $user, Course $course, float $amount): Order
    {
        return $this->order($user, $course, $amount, 'complete', 'mercadopago');
    }

    private function pendingOrder(User $user, Course $course, float $amount): Order
    {
        return $this->order($user, $course, $amount, 'pending', 'mercadopago');
    }

    protected function enroll(User $student, Course $course): void
    {
        Enrollment::firstOrCreate(['student_id' => $student->id, 'course_id' => $course->id], [
            'tutor_id' => $course->instructor_id, 'course_price' => $course->pricing?->price ?? 0,
            'course_discount' => $course->pricing?->discount ?? 0, 'status' => 'active',
        ]);
    }

    protected function progress(User $student, Course $course, float $fraction): void
    {
        $lessons = $course->sections()->with('curriculums')->get()->flatMap->curriculums->values();
        foreach ($lessons->take((int) ceil($lessons->count() * $fraction)) as $lesson) {
            Watchtime::firstOrCreate(['user_id' => $student->id, 'curriculum_id' => $lesson->id], [
                'course_id' => $course->id, 'section_id' => $lesson->section_id, 'duration' => $lesson->content_length,
            ]);
        }
    }

    private function review(User $student, Course $course, int $rating, string $comment): void
    {
        if ($course->ratings()->where('student_id', $student->id)->exists()) {
            return;
        }
        $course->ratings()->create(['student_id' => $student->id, 'tutor_id' => $course->instructor_id, 'rating' => $rating, 'comment' => $comment]);
    }

    private function addToCart(User $student, Course $course): void
    {
        \App\Models\CartItem::firstOrCreate(
            ['user_id' => $student->id, 'cartable_id' => $course->id, 'cartable_type' => Course::class],
            ['name' => $course->title, 'qty' => 1, 'price' => $course->pricing?->final_price ?? 0, 'options' => []]
        );
    }
}
