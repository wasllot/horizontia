<?php

namespace Database\Seeders;

use App\Models\CartItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Courses\Models\Category;
use Modules\Courses\Models\Course;
use Modules\Courses\Models\Curriculum;
use Modules\Courses\Models\Enrollment;
use Modules\Courses\Models\Pricing;
use Modules\Courses\Models\Section;

/**
 * Additional, disposable data used ONLY by the automated test suite
 * (tests/Feature/*) against the `testing` database.
 *
 * Deliberately does NOT reuse Modules\Courses\database\seeders\CoursesDatabaseSeeder
 * because that seeder deletes every file under storage/app/public/courses
 * (Storage::disk($disk)->delete(...)) which, on this environment, is a real
 * shared filesystem path used by the actual dev app — running it would
 * destroy real uploaded course media. This seeder creates only DB rows
 * (no filesystem writes/deletes) so it is completely safe to run repeatedly
 * against the disposable `testing` database.
 *
 * NEVER call this from the main DatabaseSeeder / module service providers.
 */
class TestingSeeder extends Seeder
{
    public function run(): void
    {
        // Extra dedicated QA users (kept distinct from the demo dataset
        // created by TutorSeeder/StudentSeeder so tests have predictable,
        // stable credentials regardless of demo data changes).
        $admin = User::firstOrCreate(
            ['email' => 'qa-admin@horizontia.test'],
            ['password' => Hash::make('Password123!'), 'email_verified_at' => now()]
        );
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }
        $admin->profile()->firstOrCreate(
            ['user_id' => $admin->id],
            ['first_name' => 'QA', 'last_name' => 'Admin', 'verified_at' => now()]
        );

        $tutor = User::firstOrCreate(
            ['email' => 'qa-tutor@horizontia.test'],
            ['password' => Hash::make('Password123!'), 'email_verified_at' => now()]
        );
        if (!$tutor->hasRole('tutor')) {
            $tutor->assignRole('tutor');
        }
        $tutor->profile()->firstOrCreate(
            ['user_id' => $tutor->id],
            ['first_name' => 'QA', 'last_name' => 'Tutor', 'verified_at' => now()]
        );

        $student = User::firstOrCreate(
            ['email' => 'qa-student@horizontia.test'],
            ['password' => Hash::make('Password123!'), 'email_verified_at' => now()]
        );
        if (!$student->hasRole('student')) {
            $student->assignRole('student');
        }
        $student->profile()->firstOrCreate(
            ['user_id' => $student->id],
            ['first_name' => 'QA', 'last_name' => 'Student', 'verified_at' => now()]
        );

        // Category / sub-category
        $category = Category::firstOrCreate(
            ['slug' => 'qa-category'],
            ['name' => 'QA Category', 'description' => 'Category used by automated tests', 'status' => 'active']
        );
        $subCategory = Category::firstOrCreate(
            ['slug' => 'qa-subcategory'],
            ['name' => 'QA Subcategory', 'description' => 'Subcategory used by automated tests', 'parent_id' => $category->id, 'status' => 'active']
        );

        // Course (article-type curriculum so no real media files are needed)
        $course = Course::firstOrCreate(
            ['slug' => 'qa-test-course'],
            [
                'instructor_id' => $tutor->id,
                'title' => 'QA Test Course',
                'subtitle' => 'A course created for automated smoke testing',
                'description' => 'This course exists only to exercise course-related routes in the automated test suite.',
                'category_id' => $category->id,
                'sub_category_id' => $subCategory->id,
                'tags' => ['qa', 'testing'],
                'type' => 'article',
                'level' => 'beginner',
                'discussion_forum' => true,
                'language_id' => 1,
                'learning_objectives' => ['Learn testing basics'],
                'prerequisites' => 'None',
                'status' => 'active',
                'content_length' => 600,
            ]
        );

        $section = Section::firstOrCreate(
            ['course_id' => $course->id, 'title' => 'QA Section 1'],
            ['description' => 'First section']
        );

        Curriculum::firstOrCreate(
            ['section_id' => $section->id, 'title' => 'QA Lesson 1'],
            [
                'description' => 'A simple article-based lesson',
                'type' => 'article',
                'article_content' => '<p>Lesson content for QA testing.</p>',
                'content_length' => 300,
                'sort_order' => 1,
                'is_preview' => true,
            ]
        );

        Pricing::firstOrCreate(
            ['course_id' => $course->id],
            ['price' => 49.99, 'discount' => 10, 'final_price' => 44.99]
        );

        // A second, non-enrolled course so listing/search pages have >1 result
        $course2 = Course::firstOrCreate(
            ['slug' => 'qa-second-course'],
            [
                'instructor_id' => $tutor->id,
                'title' => 'QA Second Course',
                'subtitle' => 'Another course for search/listing tests',
                'description' => 'Second course used purely to give listing pages more than one row.',
                'category_id' => $category->id,
                'sub_category_id' => $subCategory->id,
                'tags' => ['qa'],
                'type' => 'article',
                'level' => 'beginner',
                'discussion_forum' => false,
                'language_id' => 1,
                'status' => 'active',
                'content_length' => 300,
            ]
        );
        $section2 = Section::firstOrCreate(
            ['course_id' => $course2->id, 'title' => 'Section 1'],
            ['description' => 'Section']
        );
        Curriculum::firstOrCreate(
            ['section_id' => $section2->id, 'title' => 'Lesson 1'],
            ['type' => 'article', 'article_content' => '<p>Content</p>', 'sort_order' => 1, 'is_preview' => true]
        );
        Pricing::firstOrCreate(
            ['course_id' => $course2->id],
            ['price' => 19.99, 'discount' => 0, 'final_price' => 19.99]
        );

        // Enrollment: QA student enrolled in the QA course
        Enrollment::firstOrCreate(
            ['student_id' => $student->id, 'course_id' => $course->id],
            ['tutor_id' => $tutor->id, 'course_price' => 49.99, 'course_discount' => 10, 'status' => 'active']
        );

        // Cart item: the second course sitting in the student's cart (not purchased)
        CartItem::firstOrCreate(
            [
                'user_id' => $student->id,
                'cartable_type' => Course::class,
                'cartable_id' => $course2->id,
            ],
            ['name' => $course2->title, 'qty' => 1, 'price' => 19.99, 'options' => []]
        );
    }
}
