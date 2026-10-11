<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Courses\Livewire\Pages\Tutor\CourseCreation\Components\ManageCourseContent\CourseBuilder;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Services\ScormService;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;
use ZipArchive;

/**
 * Remote-storage (Cloudflare R2 / any S3-compatible endpoint) behaviour.
 *
 * Config tests always run. The integration tests talk to a REAL S3 endpoint and
 * are skipped unless these env vars are present (point them at MinIO in dev or at a
 * throw-away R2 bucket pair):
 *
 *   R2_TEST_ENDPOINT, R2_TEST_KEY, R2_TEST_SECRET, R2_TEST_PUBLIC_BUCKET, R2_TEST_PRIVATE_BUCKET
 *
 * They never touch the dev database (DatabaseTransactions) and clean up their objects.
 */
class R2StorageTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    private array $objects = [];

    protected function tearDown(): void
    {
        if ($this->remoteConfigured()) {
            foreach ($this->objects as [$disk, $path]) {
                try {
                    Storage::disk($disk)->deleteDirectory($path);
                    Storage::disk($disk)->delete($path);
                } catch (\Throwable) {
                }
            }
        }
        parent::tearDown();
    }

    private function remoteConfigured(): bool
    {
        return (bool) env('R2_TEST_ENDPOINT');
    }

    private function useRemote(): void
    {
        if (!$this->remoteConfigured()) {
            $this->markTestSkipped('Set R2_TEST_* env vars to run the remote storage integration tests.');
        }
        $this->ensurePlatformFixtures();

        foreach (['r2' => 'R2_TEST_PUBLIC_BUCKET', 'r2_private' => 'R2_TEST_PRIVATE_BUCKET'] as $disk => $bucketVar) {
            config([
                "filesystems.disks.$disk.endpoint" => env('R2_TEST_ENDPOINT'),
                "filesystems.disks.$disk.key" => env('R2_TEST_KEY'),
                "filesystems.disks.$disk.secret" => env('R2_TEST_SECRET'),
                "filesystems.disks.$disk.bucket" => env($bucketVar),
                "filesystems.disks.$disk.use_path_style_endpoint" => true,
            ]);
            Storage::forgetDisk($disk);
        }
        config(['filesystems.default' => 'r2', 'filesystems.private_disk' => 'r2_private']);
    }

    private function track(string $disk, string $path): void
    {
        $this->objects[] = [$disk, $path];
    }

    // ------------------------------------------------------------------
    // Always-on config checks
    // ------------------------------------------------------------------

    public function test_r2_disks_are_defined_with_the_settings_r2_requires(): void
    {
        foreach (['r2', 'r2_private'] as $disk) {
            $config = config("filesystems.disks.$disk");

            $this->assertSame('s3', $config['driver']);
            $this->assertSame('auto', $config['region'], 'R2 uses region "auto"');
            $this->assertTrue($config['use_path_style_endpoint']);
            // aws-sdk-php >= 3.337 adds CRC32 checksum headers by default, which R2 does not accept.
            $this->assertSame('when_required', $config['request_checksum_calculation']);
            $this->assertSame('when_required', $config['response_checksum_validation']);
        }

        $this->assertArrayNotHasKey('url', config('filesystems.disks.r2_private'), 'private bucket must have no public URL');
    }

    public function test_disk_helpers_follow_the_configuration(): void
    {
        config(['filesystems.default' => 'local', 'filesystems.private_disk' => null]);
        $this->assertSame('public', getStorageDisk());
        $this->assertSame('public', getPrivateDisk(), 'legacy installs keep one disk');
        $this->assertFalse(isRemoteDisk('public'));

        config(['filesystems.default' => 'r2', 'filesystems.private_disk' => 'r2_private']);
        $this->assertSame('r2', getStorageDisk());
        $this->assertSame('r2_private', getPrivateDisk());
        $this->assertTrue(isRemoteDisk('r2_private'));
    }

    public function test_lesson_media_url_on_r2_is_a_presigned_url_without_network_access(): void
    {
        config([
            'filesystems.private_disk' => 'r2_private',
            'filesystems.disks.r2_private.endpoint' => 'https://abc123.r2.cloudflarestorage.com',
            'filesystems.disks.r2_private.key' => 'AKIAEXAMPLE',
            'filesystems.disks.r2_private.secret' => 'secretexample',
            'filesystems.disks.r2_private.bucket' => 'private-bucket',
        ]);
        Storage::forgetDisk('r2_private');
        Http::fake();

        $url = lessonMediaUrl('curriculum_videos/lesson-1.mp4', 30);

        $this->assertStringStartsWith('https://abc123.r2.cloudflarestorage.com/private-bucket/curriculum_videos/lesson-1.mp4', $url);
        $this->assertStringContainsString('X-Amz-Signature=', $url);
        $this->assertMatchesRegularExpression('/X-Amz-Expires=(1799|1800)\b/', $url);   // 30 min; the signer rounds down when the clock ticks over a second
        Http::assertNothingSent();
    }

    public function test_dedicated_local_private_disk_serves_scorm_through_the_enrollment_proxy(): void
    {
        // Mixed setup used before a private bucket exists: public media on its own disk, private content on the server's
        // `local` disk (storage/app, never under public/). Any private disk different from the media disk activates it.
        $this->ensurePlatformFixtures();
        Storage::fake('local');
        config(['filesystems.default' => 'local', 'filesystems.private_disk' => 'local']);
        $this->assertNotSame(getStorageDisk(), getPrivateDisk());
        $scorm = $this->scormCurriculum();

        $zip = tempnam(sys_get_temp_dir(), 'mixscorm') . '.zip';
        $archive = new ZipArchive();
        $archive->open($zip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $archive->addFromString('index.html', '<html>mixed</html>');
        $archive->close();
        Storage::disk('local')->put('scorm_temp/mixed.zip', file_get_contents($zip));
        @unlink($zip);

        $entry = (new ScormService())->processScormZip('scorm_temp/mixed.zip', $scorm->id);

        $this->assertSame("scorm/{$scorm->id}/index.html", $entry, 'protected path, not the public storage/ one');
        $this->assertTrue(Storage::disk('local')->exists("scorm/{$scorm->id}/index.html"));
        $publicCopy = public_path("storage/scorm/{$scorm->id}/index.html");
        $this->assertTrue(!file_exists($publicCopy) || !str_contains(file_get_contents($publicCopy), 'mixed'), 'package must not be extracted under public/');

        $url = route('courses.scorm.asset', ['curriculumId' => $scorm->id, 'path' => 'index.html']);
        $this->actingAs($this->demoStudent())->get($url)->assertForbidden();
        $this->actingAs($this->student())->get($url)->assertOk();
    }

    public function test_scorm_entry_urls(): void
    {
        $this->assertSame(asset('storage/scorm/9/index.html'), scormEntryUrl(9, 'storage/scorm/9/index.html'));
        $this->assertStringEndsWith('/contenido-scorm/9/res/start.html', scormEntryUrl(9, 'scorm/9/res/start.html'));
    }

    // ------------------------------------------------------------------
    // Integration with a real S3-compatible endpoint
    // ------------------------------------------------------------------

    public function test_public_media_round_trip_and_cached_existence_check(): void
    {
        $this->useRemote();
        $path = 'qa-tests/' . uniqid() . '.txt';
        $this->track('r2', $path);

        $this->assertFalse(mediaExists($path));
        Storage::disk('r2')->put($path, 'hello r2');

        // The negative result is cached for a few seconds only, so a fresh upload is not hidden for long.
        \Cache::forget('media_exists:r2:' . md5($path));
        $this->assertTrue(mediaExists($path));
        $this->assertSame('hello r2', Storage::disk('r2')->get($path));
    }

    public function test_presigned_lesson_url_serves_the_file_and_rejects_tampering(): void
    {
        $this->useRemote();
        $path = 'curriculum_videos/qa-' . uniqid() . '.mp4';
        $this->track('r2_private', $path);
        Storage::disk('r2_private')->put($path, str_repeat('v', 4096));

        $url = lessonMediaUrl($path);

        $this->assertSame(200, Http::get($url)->status());
        $this->assertSame(206, Http::withHeaders(['Range' => 'bytes=0-99'])->get($url)->status(), 'video seeking needs Range support');
        $this->assertGreaterThanOrEqual(400, Http::get(preg_replace('/X-Amz-Signature=[a-f0-9]+/', 'X-Amz-Signature=deadbeef', $url))->status());
        $this->assertGreaterThanOrEqual(400, Http::get(strtok($url, '?'))->status(), 'the private bucket must not be readable without a signature');
    }

    public function test_tutor_video_upload_lands_in_the_private_bucket(): void
    {
        $this->useRemote();
        $curriculum = Curriculum::create([
            'section_id' => $this->section()->id, 'title' => 'R2 video', 'type' => 'video', 'sort_order' => 90,
        ]);

        Livewire::actingAs($this->tutor())
            ->test(CourseBuilder::class, ['id' => $this->section()->course_id])
            ->call('selectLesson', $curriculum->id)
            ->set('videoUpload', UploadedFile::fake()->create('lesson.mp4', 50, 'video/mp4'))
            ->set('durationSeconds', 60)
            ->call('saveLesson')
            ->assertHasNoErrors();

        $curriculum->refresh();
        $this->assertNotEmpty($curriculum->media_path);
        $this->track('r2_private', $curriculum->media_path);
        $this->assertTrue(Storage::disk('r2_private')->exists($curriculum->media_path));
        $this->assertFalse(Storage::disk('r2')->exists($curriculum->media_path), 'paid videos must not go to the public bucket');
    }

    public function test_scorm_package_is_extracted_to_the_private_bucket_and_served_to_enrolled_students_only(): void
    {
        $this->useRemote();
        $scorm = $this->scormCurriculum();
        $this->track('r2_private', "scorm/{$scorm->id}");

        $zip = tempnam(sys_get_temp_dir(), 'r2scorm') . '.zip';
        $archive = new ZipArchive();
        $archive->open($zip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $archive->addFromString('index.html', '<html>r2 scorm</html>');
        $archive->addFromString('js/app.js', 'console.log(1)');
        $archive->close();
        Storage::disk('r2_private')->put('scorm_temp/qa.zip', file_get_contents($zip));
        @unlink($zip);

        $entry = (new ScormService())->processScormZip('scorm_temp/qa.zip', $scorm->id);

        $this->assertSame("scorm/{$scorm->id}/index.html", $entry);
        $this->assertTrue(Storage::disk('r2_private')->exists("scorm/{$scorm->id}/js/app.js"));
        $this->assertFalse(Storage::disk('r2_private')->exists('scorm_temp/qa.zip'), 'temp zip is removed');

        $url = route('courses.scorm.asset', ['curriculumId' => $scorm->id, 'path' => 'js/app.js']);
        $this->actingAs($this->demoStudent())->get($url)->assertForbidden();
        $this->actingAs($this->student())->get($url)->assertOk();
    }

    public function test_storage_copy_command_moves_local_files_to_r2_without_deleting_the_source(): void
    {
        $this->useRemote();
        Storage::fake('legacy');
        $name = 'qa-copy-' . uniqid();
        Storage::disk('legacy')->put("$name/a.txt", 'A');
        Storage::disk('legacy')->put("$name/sub/b.txt", 'B');
        $this->track('r2_private', $name);

        $this->assertSame(0, Artisan::call('storage:copy', ['from' => 'legacy', 'to' => 'r2_private', '--dirs' => [$name], '--dry-run' => true]));
        $this->assertFalse(Storage::disk('r2_private')->exists("$name/a.txt"), 'dry run writes nothing');

        $this->assertSame(0, Artisan::call('storage:copy', ['from' => 'legacy', 'to' => 'r2_private', '--dirs' => [$name]]));
        $this->assertSame('B', Storage::disk('r2_private')->get("$name/sub/b.txt"));
        $this->assertTrue(Storage::disk('legacy')->exists("$name/a.txt"));

        $this->artisan('storage:copy', ['from' => 'legacy', 'to' => 'r2_private', '--dirs' => [$name]])
            ->expectsOutputToContain('copied=0 skipped=2');
    }
}
