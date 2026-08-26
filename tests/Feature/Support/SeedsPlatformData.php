<?php

namespace Tests\Feature\Support;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\SlotBooking;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\TestingSeeder;
use Illuminate\Support\Facades\DB;
use Modules\Courses\Models\Category;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Section;

/**
 * Shared accessors for the persistent QA fixtures created by
 * database/seeders/TestingSeeder.php (and the real DatabaseSeeder demo data)
 * against the disposable `testing` database.
 *
 * Tests using this trait rely on the database already being seeded
 * (see project docs / README-testing) and run inside a DB transaction
 * (DatabaseTransactions) so nothing they write here persists afterwards.
 *
 * IMPORTANT: some pre-existing scaffold tests (tests/Feature/Auth/*,
 * tests/Feature/ProfileTest.php) use RefreshDatabase, which runs
 * migrate:fresh and wipes every table in the `testing` database. If those
 * happen to run earlier in the same PHPUnit process, our persistent fixtures
 * would be gone by the time these tests run. `ensurePlatformFixtures()`
 * self-heals that (cheaply, via existence checks) so this suite is correct
 * regardless of test execution order.
 */
trait SeedsPlatformData
{
    protected function ensurePlatformFixtures(): void
    {
        if (Blog::query()->doesntExist() || User::where('email', 'tutor@amentotech.com')->doesntExist()) {
            (new DatabaseSeeder())->run();
        }
        if (Course::where('slug', 'qa-test-course')->doesntExist() || User::where('email', 'qa-admin@horizontia.test')->doesntExist()) {
            (new TestingSeeder())->run();
        }
    }

    protected function admin(): User
    {
        return User::where('email', 'qa-admin@horizontia.test')->firstOrFail();
    }

    protected function tutor(): User
    {
        return User::where('email', 'qa-tutor@horizontia.test')->firstOrFail();
    }

    protected function student(): User
    {
        return User::where('email', 'qa-student@horizontia.test')->firstOrFail();
    }

    /** A second seeded tutor/student from the main demo dataset, for variety. */
    protected function demoTutor(): User
    {
        return User::where('email', 'tutor@amentotech.com')->firstOrFail();
    }

    protected function demoStudent(): User
    {
        return User::where('email', 'student@amentotech.com')->firstOrFail();
    }

    protected function course(): Course
    {
        return Course::where('slug', 'qa-test-course')->firstOrFail();
    }

    protected function course2(): Course
    {
        return Course::where('slug', 'qa-second-course')->firstOrFail();
    }

    protected function section(): Section
    {
        return Section::where('course_id', $this->course()->id)->firstOrFail();
    }

    protected function curriculum(): Curriculum
    {
        return Curriculum::where('section_id', $this->section()->id)->firstOrFail();
    }

    /** The SCORM-type curriculum item created by TestingSeeder (no media uploaded yet). */
    protected function scormCurriculum(): Curriculum
    {
        return Curriculum::where('section_id', $this->section()->id)
            ->where('title', 'QA SCORM Lesson')
            ->firstOrFail();
    }

    /** A completed 1:1 booking between the QA tutor and QA student, for dispute tests. */
    protected function booking(): SlotBooking
    {
        return SlotBooking::where('student_id', $this->student()->id)
            ->where('tutor_id', $this->tutor()->id)
            ->firstOrFail();
    }

    protected function category(): Category
    {
        return Category::where('slug', 'qa-category')->firstOrFail();
    }

    protected function blog(): Blog
    {
        return Blog::firstOrFail();
    }

    protected function blogCategory(): BlogCategory
    {
        return BlogCategory::firstOrFail();
    }

    /** id of a pagebuilder page (from DefaultPageSettingSeeder), by slug. */
    protected function pageId(string $slug): ?int
    {
        $table = config('pagebuilder.db_prefix') . 'pages';
        $row = DB::table($table)->where('slug', $slug)->first();
        return $row?->id;
    }

    protected function pageSlug(string $slug = 'about-us'): string
    {
        return $slug;
    }
}
