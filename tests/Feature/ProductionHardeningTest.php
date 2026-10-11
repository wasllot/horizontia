<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Courses\Models\Curriculum;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;

/** Pasos previos a vender en serio (sección 1 de la hoja de ruta): nada de contenido de pago ni respuestas hacia el navegador. */
class ProductionHardeningTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['cache']->setDefaultDriver('array');   // the running dev scheduler writes heartbeats to the shared redis
        $this->ensurePlatformFixtures();
    }

    private function lesson(string $type, string $content, bool $preview = false): Curriculum
    {
        return Curriculum::create([
            'section_id' => $this->section()->id, 'title' => 'Hardening ' . uniqid(), 'type' => $type, 'article_content' => $content,
            'content_length' => 60, 'sort_order' => 98, 'is_preview' => $preview,
        ]);
    }

    private function quizJson(): string
    {
        return json_encode(['pass_score' => 60, 'questions' => [['id' => 1, 'type' => 'single', 'text' => '¿2+2?', 'options' => ['3', 'CUATRO', '5'], 'correct' => 1, 'explanation' => 'EXPLICACION-SECRETA']]], JSON_UNESCAPED_UNICODE);
    }

    public function test_the_public_course_api_does_not_leak_paid_articles_or_quiz_answers(): void
    {
        $this->lesson('article', '<p>TEXTO-DE-PAGO-SECRETO</p>');
        $this->lesson('assignment', $this->quizJson());

        $body = $this->getJson('/api/course-detail/' . $this->course()->slug)->assertOk()->getContent();

        $this->assertStringNotContainsString('TEXTO-DE-PAGO-SECRETO', $body, 'locked article text');
        $this->assertStringNotContainsString('EXPLICACION-SECRETA', $body, 'quiz explanation');
        $this->assertStringNotContainsString('"correct"', $body, 'quiz answer key');
    }

    public function test_free_preview_articles_stay_public_but_a_preview_quiz_never_shows_its_answers(): void
    {
        $this->lesson('article', '<p>TEXTO-DE-MUESTRA-GRATIS</p>', preview: true);
        $this->lesson('assignment', $this->quizJson(), preview: true);

        $body = $this->getJson('/api/course-detail/' . $this->course()->slug)->assertOk()->getContent();

        $this->assertStringContainsString('TEXTO-DE-MUESTRA-GRATIS', $body);
        $this->assertStringNotContainsString('EXPLICACION-SECRETA', $body);
        $this->assertStringNotContainsString('"correct"', $body);
    }

    public function test_the_enrolled_student_gets_the_quiz_without_answers_through_the_api(): void
    {
        $this->lesson('assignment', $this->quizJson());

        $body = $this->actingAs($this->student(), 'sanctum')->getJson('/api/course-taking/' . $this->course()->slug)->assertOk()->getContent();

        $this->assertStringContainsString('CUATRO', $body, 'the question options are delivered');
        $this->assertStringNotContainsString('EXPLICACION-SECRETA', $body);
        $this->assertStringNotContainsString('\"correct\"', $body);
    }

    public function test_the_public_course_page_does_not_embed_a_preview_quiz_key(): void
    {
        $this->lesson('assignment', $this->quizJson(), preview: true);

        $html = $this->get(route('courses.course-detail', ['slug' => $this->course()->slug]))->assertOk()->getContent();

        $this->assertStringNotContainsString('EXPLICACION-SECRETA', $html);
        $this->assertStringNotContainsString('CUATRO', $html);
    }

    // ------------------------------------------------------------ identity documents

    private function identityRecord(string $path): \App\Models\UserIdentityVerification
    {
        return \App\Models\UserIdentityVerification::create(['user_id' => $this->student()->id, 'name' => 'Ana', 'personal_photo' => $path, 'status' => 'pending']);
    }

    public function test_identity_documents_are_private_and_only_the_owner_and_admins_can_open_them(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        \Illuminate\Support\Facades\Storage::disk('local')->put('identity_photo/doc.png', $png);
        $record = $this->identityRecord('identity_photo/doc.png');
        $url = route('identity.file', ['verification' => $record->id, 'field' => 'personal_photo']);

        $res = $this->actingAs($this->student())->get($url)->assertOk();
        $this->assertSame('image/png', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('no-store', $res->headers->get('Cache-Control'));
        $this->actingAs($this->admin())->get($url)->assertOk();
        $this->actingAs($this->demoStudent())->get($url)->assertForbidden();
        $this->actingAs($this->student())->get(route('identity.file', ['verification' => $record->id, 'field' => 'dob']))->assertNotFound();
    }

    public function test_identity_uploads_use_server_chosen_names_and_a_non_public_disk(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->image('dni-de-otra-persona.jpg');
        $name = identityFileName($file);

        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}\.jpg$/', $name);
        $this->assertNotSame('public', identityDisk());
    }

    public function test_the_identity_api_never_returns_a_public_file_url(): void
    {
        $record = $this->identityRecord('identity_photo/x.png');
        $record->update(['transcript' => 'identity_photo/t.png']);

        $json = (new \App\Http\Resources\IdentityResource($record->fresh()))->toArray(request());

        $this->assertStringContainsString('/identidad/' . $record->id . '/transcript', $json['transcript']);
        $this->assertStringNotContainsString('/storage/', $json['transcript']);
    }

    // ------------------------------------------------------------ preflight / system status

    /** An empty stand-in for public/ (the real one belongs to another user and the dev scheduler shares the cache). */
    private function fakePublicDir(): string
    {
        $dir = sys_get_temp_dir() . '/pf-public-' . uniqid();
        mkdir($dir);
        $this->app->usePublicPath($dir);

        return $dir;
    }

    private function checks(): \Illuminate\Support\Collection
    {
        return collect((new \App\Services\Preflight(checkDns: false))->run())->keyBy('key');
    }

    public function test_preflight_flags_the_factory_admin_password_and_downloadable_archives(): void
    {
        $admin = \App\Models\User::where('email', 'admin@amentotech.com')->first() ?? tap($this->admin())->update(['email' => 'admin@amentotech.com']);
        $admin->update(['password' => \Illuminate\Support\Facades\Hash::make('google')]);
        $public = $this->fakePublicDir();
        file_put_contents($public . '/zz-prueba.zip', 'x');

        $checks = $this->checks();
        $this->assertSame('fail', $checks['admin_password']['status']);
        $this->assertSame('fail', $checks['public_files']['status']);
        $this->assertStringContainsString('zz-prueba.zip', $checks['public_files']['detail']);

        unlink($public . '/zz-prueba.zip');
        $admin->update(['password' => \Illuminate\Support\Facades\Hash::make('una-clave-larga-y-unica-9!')]);
        $this->assertSame('ok', $this->checks()['admin_password']['status']);
        $this->assertSame('ok', $this->checks()['public_files']['status']);
    }

    public function test_production_only_checks_are_warnings_while_developing_and_errors_in_production(): void
    {
        config(['app.debug' => true]);
        $this->assertSame('warn', $this->checks()['debug']['status']);

        $this->app['env'] = 'production';
        $this->assertSame('fail', $this->checks()['debug']['status']);
        $this->app['env'] = 'testing';
    }

    public function test_heartbeats_drive_the_worker_and_scheduler_checks(): void
    {
        \Illuminate\Support\Facades\Cache::forget('heartbeat:scheduler');
        \Illuminate\Support\Facades\Cache::forget('heartbeat:queue');
        $this->assertNotSame('ok', $this->checks()['scheduler']['status']);

        \Illuminate\Support\Facades\Cache::forever('heartbeat:scheduler', now()->toIso8601String());
        \Illuminate\Support\Facades\Cache::forever('heartbeat:queue', now()->subHour()->toIso8601String());
        $this->assertSame('ok', $this->checks()['scheduler']['status']);
        $this->assertNotSame('ok', $this->checks()['queue_worker']['status'], 'a stale worker heartbeat is a problem');
    }

    public function test_the_system_status_page_is_for_admins_only_and_lists_the_checks(): void
    {
        $this->actingAs($this->admin())->get(route('admin.system-status'))->assertOk()->assertSee('Estado del sistema')->assertSee('data-testid="check-debug"', false);
        $this->assertNotSame(200, $this->actingAs($this->student())->get(route('admin.system-status'))->getStatusCode());
        \Livewire\Livewire::actingAs($this->student())->test(\App\Livewire\Pages\Admin\SystemStatus\SystemStatus::class)->assertForbidden();
    }

    public function test_the_preflight_command_exits_with_an_error_code_when_something_is_wrong(): void
    {
        file_put_contents($this->fakePublicDir() . '/volcado.sql', 'x');

        $this->artisan('academy:preflight', ['--no-dns' => true])->assertFailed();
    }

    public function test_the_backup_command_makes_a_valid_compressed_dump(): void
    {
        $this->artisan('app:backup-database', ['--keep' => 14])->assertSuccessful();

        $files = glob(storage_path('app/backups/horizontia-*.sql.gz'));
        $this->assertNotEmpty($files);
        $latest = end($files);
        $this->assertStringContainsString('CREATE TABLE', (string) gzdecode(file_get_contents($latest)));
        $this->assertNotNull((new \App\Services\Preflight(false))->lastBackup());
        array_map('unlink', $files);
    }

    // ------------------------------------------------------------ error alerts

    public function test_unexpected_errors_are_e_mailed_once_per_hour_and_expected_ones_are_ignored(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        config(['academy.alert_email' => 'alertas@academia.test', 'academy.error_alerts' => true]);
        {
            $boom = new \RuntimeException('Base de datos caída');
            \App\Services\ErrorAlert::report($boom);
            \App\Services\ErrorAlert::report($boom);   // same error, same hour
            \App\Services\ErrorAlert::report(new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException());
            \App\Services\ErrorAlert::report(\Illuminate\Validation\ValidationException::withMessages(['a' => 'b']));

            \Illuminate\Support\Facades\Mail::assertSentCount(1);
        }
    }

    public function test_without_an_alert_address_nothing_is_sent(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        \App\Services\ErrorAlert::report(new \RuntimeException('x'));

        \Illuminate\Support\Facades\Mail::assertNothingSent();
    }
}
