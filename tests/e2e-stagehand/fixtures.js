// Fixture IDs from the `testing` DB, seeded by database/seeders/TestingSeeder.php.
// Regenerate by reading these rows via tinker if TestingSeeder.php ever changes.
export const FIXTURES = {
  courseId: 1,
  courseSlug: "qa-test-course",
  course2Id: 2,
  course2Slug: "qa-second-course",
  tutorId: 25,
  tutorSlug: "qa-tutor",
  studentId: 26,
  blogId: 1,
  blogSlug: "pharma-rds-covid-19-scar-that-goes-beyond-thoughts",
  pageId: 1, // Larabuild\Pagebuilder\Models\Page id=1 (slug "/")
  scormCurriculumId: 3, // "QA SCORM Lesson" curriculum, media_path still null
  articleCurriculumId: 1, // "QA Lesson 1" curriculum (preview article)
  bookingId: 1, // completed SlotBooking between qa-tutor/qa-student
  categoryId: 1,
  subCategoryId: 2,
  languageId: 1,
};

export const USERS = {
  admin: { email: "qa-admin@horizontia.test", password: "Password123!" },
  tutor: { email: "qa-tutor@horizontia.test", password: "Password123!" },
  student: { email: "qa-student@horizontia.test", password: "Password123!" },
};
