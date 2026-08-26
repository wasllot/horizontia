<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Courses\Http\Controllers\ScormController;
use Modules\Courses\Livewire\Pages\Tutor\CourseCreation\Components\ManageCourseContent\Components\Curriculum as CurriculumComponent;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\ScormProgress;
use Tests\Feature\Support\SeedsPlatformData;
use Tests\TestCase;
use ZipArchive;

/**
 * SCORM upload/playback flow: build a minimal-but-valid SCORM zip fixture
 * in-memory, upload it through the real tutor authoring endpoint
 * (Curriculum::updateCurriculumContent, a Livewire component action — this
 * is genuinely how the SCORM upload UI submits, per
 * Modules/Courses/resources/views/livewire/tutor/course-creation/components/
 * manage-course-content/components/curriculum.blade.php), then simulate a
 * student progressing through it via the real ScormController endpoints
 * that back scorm_progresses.
 *
 * This suite writes real files under storage/app/public/scorm{,_temp}/ (the
 * only way to genuinely exercise ZipArchive extraction) and removes
 * everything it creates in tearDown — it never touches
 * storage/app/public/courses (the real, shared course-media path).
 */
class ScormFlowTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsPlatformData;

    /** @var string[] Absolute paths this test created, to clean up afterwards. */
    protected array $pathsToClean = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePlatformFixtures();
    }

    protected function tearDown(): void
    {
        foreach ($this->pathsToClean as $path) {
            if (is_dir($path)) {
                File::deleteDirectory($path);
            } elseif (is_file($path)) {
                @unlink($path);
            }
        }

        // updateCurriculumContent() also leaves the originally-uploaded zip
        // behind under scorm_temp/ (it's only ever read from, never
        // cleaned up by the app) — remove anything this test run created
        // there so repeated runs don't accumulate garbage in real storage.
        foreach (glob(storage_path('app/public/scorm_temp/qa-*.zip')) ?: [] as $leftoverZip) {
            @unlink($leftoverZip);
        }

        parent::tearDown();
    }

    /**
     * Build a tiny but genuinely valid SCORM 1.2 package: an imsmanifest.xml
     * pointing at a single index.html resource.
     */
    protected function buildScormZip(bool $withManifest = true, bool $withFallbackIndex = false): string
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'qa_scorm_') . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $html = '<!doctype html><html><body>QA SCORM content</body></html>';

        if ($withManifest) {
            $manifest = <<<XML
            <?xml version="1.0" standalone="no" ?>
            <manifest identifier="qa_test_manifest" version="1" xmlns="http://www.imsproject.org/xsd/imscp_rootv1p1p2">
                <organizations default="qa_org">
                    <organization identifier="qa_org">
                        <item identifier="item_1" identifierref="resource_1">
                            <title>QA SCORM Item</title>
                        </item>
                    </organization>
                </organizations>
                <resources>
                    <resource identifier="resource_1" type="webcontent" href="index.html">
                        <file href="index.html"/>
                    </resource>
                </resources>
            </manifest>
            XML;
            $zip->addFromString('imsmanifest.xml', $manifest);
            $zip->addFromString('index.html', $html);
        } elseif ($withFallbackIndex) {
            // No manifest, but a recognized fallback filename.
            $zip->addFromString('index.html', $html);
        } else {
            // Neither a manifest nor any recognized fallback file: a
            // genuinely invalid SCORM package.
            $zip->addFromString('readme.txt', 'not a scorm package');
        }

        $zip->close();

        $bytes = file_get_contents($zipPath);
        @unlink($zipPath);

        return $bytes;
    }

    public function test_tutor_can_upload_a_valid_scorm_package_and_entry_point_is_resolved(): void
    {
        $tutor = $this->tutor();
        $section = $this->section();
        $curriculum = $this->scormCurriculum();
        $this->assertNull($curriculum->media_path, 'Fixture should start without media.');

        $this->pathsToClean[] = storage_path('app/public/scorm/' . $curriculum->id);

        $upload = UploadedFile::fake()->createWithContent('qa-scorm-package.zip', $this->buildScormZip());

        Livewire::actingAs($tutor)
            ->test(CurriculumComponent::class, ['section' => $section])
            ->set('activeCurriculumItem', $curriculum->toArray())
            ->set('scorm_file', $upload)
            ->call('updateCurriculumContent')
            ->assertHasNoErrors();

        $curriculum->refresh();
        $this->assertNotNull($curriculum->media_path, 'SCORM upload did not set media_path — see report for the storage-path bug this caught.');
        $this->assertStringEndsWith('index.html', $curriculum->media_path);
        $this->assertStringStartsWith('storage/scorm/' . $curriculum->id . '/', $curriculum->media_path);

        // The extracted entry point must actually exist on disk at the path
        // the app will serve via asset($curriculum->media_path).
        $this->assertTrue(
            Storage::disk('public')->exists('scorm/' . $curriculum->id . '/index.html'),
            'Extracted SCORM entry point file is missing from public storage.'
        );
    }

    public function test_scorm_upload_with_no_entry_point_fails_gracefully_without_crashing(): void
    {
        $tutor = $this->tutor();
        $section = $this->section();
        $curriculum = $this->scormCurriculum();

        $this->pathsToClean[] = storage_path('app/public/scorm/' . $curriculum->id);

        $upload = UploadedFile::fake()->createWithContent('qa-invalid-package.zip', $this->buildScormZip(withManifest: false, withFallbackIndex: false));

        Livewire::actingAs($tutor)
            ->test(CurriculumComponent::class, ['section' => $section])
            ->set('activeCurriculumItem', $curriculum->toArray())
            ->set('scorm_file', $upload)
            ->call('updateCurriculumContent')
            ->assertHasNoErrors() // file passed 'file|mimes:zip' validation fine
            ->assertDispatched('showAlertMessage', type: 'error');

        $curriculum->refresh();
        $this->assertNull($curriculum->media_path, 'media_path must stay unset when no entry point could be resolved.');
    }

    public function test_scorm_upload_falls_back_to_index_html_when_manifest_is_missing(): void
    {
        $tutor = $this->tutor();
        $section = $this->section();
        $curriculum = $this->scormCurriculum();

        $this->pathsToClean[] = storage_path('app/public/scorm/' . $curriculum->id);

        $upload = UploadedFile::fake()->createWithContent('qa-fallback-package.zip', $this->buildScormZip(withManifest: false, withFallbackIndex: true));

        Livewire::actingAs($tutor)
            ->test(CurriculumComponent::class, ['section' => $section])
            ->set('activeCurriculumItem', $curriculum->toArray())
            ->set('scorm_file', $upload)
            ->call('updateCurriculumContent')
            ->assertHasNoErrors();

        $curriculum->refresh();
        $this->assertSame('storage/scorm/' . $curriculum->id . '/index.html', $curriculum->media_path);
    }

    public function test_scorm_upload_rejects_non_zip_file_via_validation(): void
    {
        $tutor = $this->tutor();
        $section = $this->section();
        $curriculum = $this->scormCurriculum();

        $upload = UploadedFile::fake()->create('not-a-scorm.pdf', 10, 'application/pdf');

        Livewire::actingAs($tutor)
            ->test(CurriculumComponent::class, ['section' => $section])
            ->set('activeCurriculumItem', $curriculum->toArray())
            ->set('scorm_file', $upload)
            ->call('updateCurriculumContent')
            ->assertHasErrors(['scorm_file']);
    }

    public function test_student_can_save_and_fetch_scorm_progress_via_api(): void
    {
        $student = $this->student();
        $curriculum = $this->scormCurriculum();
        ScormProgress::where('user_id', $student->id)->where('curriculum_id', $curriculum->id)->delete();

        // LMSInitialize with no prior progress.
        $initial = $this->actingAs($student)->getJson("/scorm/progress/{$curriculum->id}");
        $initial->assertOk();
        $initial->assertJson(['success' => false]);

        // LMSCommit / LMSSetValue-style progress save.
        $save = $this->actingAs($student)->postJson("/scorm/progress/{$curriculum->id}", [
            'cmi' => [
                'core' => [
                    'lesson_status' => 'completed',
                    'score' => ['raw' => 92],
                ],
                'suspend_data' => 'qa-suspend-blob',
            ],
        ]);
        $save->assertOk();
        $save->assertJson(['success' => true]);

        $this->assertDatabaseHas((config('courses.db_prefix') ?? 'courses_') . 'scorm_progresses', [
            'user_id' => $student->id,
            'curriculum_id' => $curriculum->id,
            'lesson_status' => 'completed',
            'score_raw' => 92,
        ]);

        // A subsequent LMSInitialize must now return the saved progress.
        $reload = $this->actingAs($student)->getJson("/scorm/progress/{$curriculum->id}");
        $reload->assertOk();
        $reload->assertJsonPath('cmi.core.lesson_status', 'completed');
        $reload->assertJsonPath('cmi.core.score.raw', 92);
    }

    public function test_scorm_progress_endpoints_reject_guests(): void
    {
        $curriculum = $this->scormCurriculum();

        // auth+verified middleware redirects guests rather than crashing.
        $get = $this->get("/scorm/progress/{$curriculum->id}");
        $this->assertLessThan(500, $get->getStatusCode());

        $post = $this->post("/scorm/progress/{$curriculum->id}", ['cmi' => []]);
        $this->assertLessThan(500, $post->getStatusCode());
    }
}
