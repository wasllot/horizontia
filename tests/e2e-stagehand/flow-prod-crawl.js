// PRODUCTION QA: read-only discovery + route crawl against LIVE https://horizontia.com.
//
// Unlike crawl-routes.js (LOCAL env, seeded DB with known fixture IDs), production has real
// user data we must never touch. This script:
//   1. Discovers real course/tutor/blog slugs by reading anchors off real listing pages
//      (plain DOM reads, no .act()/.extract()) -- used ONLY for read-only GET checks.
//   2. Crawls a hand-picked, conservative subset of GET routes as guest/student/tutor/admin,
//      explicitly SKIPPING anything that would need a guessed ID belonging to someone else's
//      real resource (bookings, disputes, identity verifications, other users' course edit
//      pages, etc.), any route that mutates state (clear-cache, upgrade, approve-identity,
//      exit-impersonate), and anything payment/OAuth-related.
//   3. Takes an evidence screenshot of a real course listing page and checks for broken
//      <img> tags, to confirm the public/storage symlink fix is actually serving images.
//
// courseId/courseSlug for our own QA course (owned by us, safe to view its admin/tutor edit
// pages) are read from results-prod-course.json if flow-prod-course.js already ran.
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";
import {
  BASE_URL,
  createStagehand,
  login,
  logout,
  attachErrorCollectors,
  checkPage,
  saveFailureScreenshot,
  summarize,
} from "./helpers.js";
import { PROD_USERS } from "./prod-fixtures.js";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

let ownCourseId = null;
let ownCourseSlug = null;
try {
  const prev = JSON.parse(fs.readFileSync(path.join(__dirname, "results-prod-course.json"), "utf8"));
  ownCourseId = prev.created?.courseId || null;
  ownCourseSlug = prev.created?.courseSlug || null;
} catch (e) {
  console.log("(no results-prod-course.json found yet -- own-course-scoped routes will be skipped)");
}

async function discoverSlug(page, listingPath, hrefPattern) {
  await page.goto(BASE_URL + listingPath, { waitUntil: "domcontentloaded" });
  await page.waitForLoadState("networkidle", { timeout: 10000 }).catch(() => {});
  const hrefs = await page.locator(`a[href*="${hrefPattern}"]`).evaluateAll((els) => els.map((el) => el.getAttribute("href")));
  for (const href of hrefs) {
    const m = href && href.match(new RegExp(`${hrefPattern}([a-z0-9-]+)`));
    if (m && m[1]) return m[1];
  }
  return null;
}

function planRoutes({ realBlogSlug, realTutorSlug, realCourseSlug }) {
  const guest = [
    { key: "home", path: "" },
    { key: "courses.search-courses", path: "search-courses" },
    { key: "find-tutors", path: "find-tutors" },
    { key: "blogs", path: "blogs" },
    { key: "login", path: "login" },
    { key: "register", path: "register" },
    { key: "password.request", path: "forgot-password" },
    { key: "password.reset (dummy token)", path: "reset-password/dummy-token-for-prod-qa" },
    { key: "contacto", path: "contacto" },
    { key: "up (health check)", path: "up" },
  ];
  if (realBlogSlug) guest.push({ key: "blog-details (real, read-only)", path: `blog/${realBlogSlug}` });
  if (realTutorSlug) guest.push({ key: "tutor-detail (real, read-only)", path: `tutor/${realTutorSlug}` });
  if (realCourseSlug) guest.push({ key: "courses.course-detail (real, read-only)", path: `course/${realCourseSlug}` });
  if (ownCourseSlug) guest.push({ key: "courses.course-detail (own QA course)", path: `course/${ownCourseSlug}` });

  const student = [
    { key: "student.profile", path: "student/profile" },
    { key: "student.profile.personal-details", path: "student/profile/personal-details" },
    { key: "student.profile.account-settings", path: "student/profile/account-settings" },
    { key: "student.profile.identification", path: "student/profile/identification" },
    { key: "student.bookings", path: "student/bookings" },
    { key: "student.favourites", path: "student/favourites" },
    { key: "student.certificate-list", path: "student/certificates" },
    { key: "student.invoices", path: "student/invoices" },
    { key: "student.disputes", path: "student/disputes" },
    { key: "student.billing-detail", path: "student/billing-detail" },
    { key: "laraguppy.messenger", path: "messenger" },
    { key: "courses.course-list", path: "course-list" },
  ];
  if (ownCourseSlug) student.push({ key: "courses.course-taking (own QA course)", path: `course-taking/${ownCourseSlug}` });

  const tutor = [
    { key: "tutor.dashboard", path: "tutor/dashboard" },
    { key: "tutor.profile", path: "tutor/profile" },
    { key: "tutor.profile.personal-details", path: "tutor/profile/personal-details" },
    { key: "tutor.profile.account-settings", path: "tutor/profile/account-settings" },
    { key: "tutor.profile.identification", path: "tutor/profile/identification" },
    { key: "tutor.profile.resume.certificate", path: "tutor/profile/resume/certificate" },
    { key: "tutor.profile.resume.education", path: "tutor/profile/resume/education" },
    { key: "tutor.profile.resume.experience", path: "tutor/profile/resume/experience" },
    { key: "tutor.bookings.upcoming-bookings", path: "tutor/bookings/upcoming-bookings" },
    { key: "tutor.bookings.manage-sessions", path: "tutor/bookings/manage-sessions" },
    { key: "tutor.bookings.subjects", path: "tutor/bookings/manage-subjects" },
    { key: "tutor.invoices", path: "tutor/invoices" },
    { key: "tutor.disputes", path: "tutor/disputes" },
    { key: "tutor.payouts", path: "tutor/payouts" },
    { key: "courses.tutor.courses", path: "courses" },
    { key: "courses.tutor.create-course", path: "create-course" },
    { key: "courses.tutor.assignments", path: "tutor/assignments" },
    { key: "courses.tutor.manage-live-streams", path: "tutor/manage-live-streams" },
    { key: "courses.tutor.schedule-live-stream", path: "tutor/schedule-live-stream" },
  ];
  if (ownCourseId) tutor.push({ key: "courses.tutor.edit-course (own QA course)", path: `course/edit/details/${ownCourseId}` });

  const admin = [
    { key: "admin.blog-categories", path: "admin/blog-categories" },
    { key: "admin.blog-listing", path: "admin/blogs" },
    { key: "admin.create-blog", path: "admin/blogs/create" },
    { key: "admin.users", path: "admin/users" },
    { key: "admin.bookings", path: "admin/bookings" },
    { key: "admin.disputes", path: "admin/disputes" },
    { key: "admin.identity-verification", path: "admin/identity-verification" },
    { key: "admin.insights", path: "admin/insights" },
    { key: "admin.invoices", path: "admin/invoices" },
    { key: "admin.leads", path: "admin/leads" },
    { key: "admin.notification-settings", path: "admin/notification-settings" },
    { key: "admin.email-settings", path: "admin/email-settings" },
    { key: "admin.payment-methods", path: "admin/payment-methods" },
    { key: "admin.commission-settings", path: "admin/commission-settings" },
    { key: "admin.withdraw-requests", path: "admin/withdraw-requests" },
    { key: "admin.profile", path: "admin/profile" },
    { key: "admin.packages.index", path: "admin/packages" },
    { key: "admin.packages.installed", path: "admin/packages/installed" },
    { key: "admin.taxonomy.languages", path: "admin/taxonomies/languages" },
    { key: "admin.taxonomy.subject-groups", path: "admin/taxonomies/subject-groups" },
    { key: "admin.taxonomy.subjects", path: "admin/taxonomies/subjects" },
    { key: "admin.manage-menus", path: "admin/manage-menus" },
    { key: "courses.admin.categories", path: "admin/categories" },
    { key: "courses.admin.commission-setting", path: "admin/commission-setting" },
    { key: "courses.admin.course-enrollments", path: "admin/course-enrollments" },
    { key: "courses.admin.courses", path: "admin/courses" },
    { key: "courses.admin.create-course", path: "admin/create-course" },
    { key: "optionbuilder", path: "admin/option-builder" },
    { key: "pagebuilder", path: "admin/pages" },
    { key: "page.create", path: "admin/pages/create" },
  ];
  if (ownCourseId) admin.push({ key: "courses.admin.edit-course (own QA course)", path: `admin/course/edit/details/${ownCourseId}` });

  return { guest, student, tutor, admin };
}

async function main() {
  const stagehand = await createStagehand();
  const page = stagehand.page;
  const collectors = attachErrorCollectors(page);

  console.log("--- Discovery: real blog/tutor/course slugs from live listing pages (read-only) ---");
  const realBlogSlug = await discoverSlug(page, "/blogs", "/blog/");
  const realTutorSlug = await discoverSlug(page, "/find-tutors", "/tutor/");
  const realCourseSlug = await discoverSlug(page, "/search-courses", "/course/");
  console.log(`discovered: blog=${realBlogSlug} tutor=${realTutorSlug} course=${realCourseSlug}`);
  console.log(`own QA course: id=${ownCourseId} slug=${ownCourseSlug}`);

  const { guest, student, tutor, admin } = planRoutes({ realBlogSlug, realTutorSlug, realCourseSlug });
  console.log(`Route plan: guest=${guest.length} student=${student.length} tutor=${tutor.length} admin=${admin.length}`);

  const allResults = [];

  for (const r of guest) {
    const res = await checkPage(page, r.path, { collectors, label: `guest:${r.key}` });
    allResults.push({ actor: "guest", ...r, ...res });
  }

  const actorPlans = { student, tutor, admin };
  for (const actorName of ["student", "tutor", "admin"]) {
    await logout(page);
    await login(page, PROD_USERS[actorName].email, PROD_USERS[actorName].password);
    for (const r of actorPlans[actorName]) {
      const res = await checkPage(page, r.path, { collectors, label: `${actorName}:${r.key}` });
      allResults.push({ actor: actorName, ...r, ...res });
    }
  }

  // ---------------------------------------------------------------
  // Image-fix evidence: screenshot a real course listing page (has thumbnails from
  // public/storage) and check for any broken <img> (naturalWidth === 0 once loaded).
  // ---------------------------------------------------------------
  console.log("\n--- Image-fix evidence capture ---");
  await page.goto(BASE_URL + "/search-courses", { waitUntil: "domcontentloaded" });
  await page.waitForLoadState("networkidle", { timeout: 10000 }).catch(() => {});
  await page.waitForTimeout(1000);
  const imgReport = await page.evaluate(() => {
    const imgs = Array.from(document.querySelectorAll("img"));
    return imgs.map((img) => ({
      src: img.currentSrc || img.src,
      complete: img.complete,
      naturalWidth: img.naturalWidth,
      broken: img.complete && img.naturalWidth === 0,
    }));
  });
  const brokenImgs = imgReport.filter((i) => i.broken);
  const storageImgs = imgReport.filter((i) => i.src && i.src.includes("/storage/"));
  console.log(`   total <img> on /search-courses: ${imgReport.length}, /storage/ images: ${storageImgs.length}, broken: ${brokenImgs.length}`);
  if (brokenImgs.length) console.log("   BROKEN:", JSON.stringify(brokenImgs.slice(0, 10)));
  const evidenceShot = await saveFailureScreenshot(page, "image-fix-evidence-search-courses");
  console.log(`   evidence screenshot: ${evidenceShot}`);

  await stagehand.close();

  const { pass, fail, passCount, failCount } = summarize(allResults);
  console.log(`\n=== Production route crawl summary ===`);
  console.log(`PASS: ${passCount}  FAIL: ${failCount}`);
  if (fail.length) {
    console.log("\nFAILURES:");
    for (const f of fail) {
      console.log(
        `  [${f.actor}] ${f.key} (${f.path}) -> status=${f.status} navError=${f.navError} whoops=${f.whoops} consoleErrors=${f.consoleErrors.length} pageErrors=${f.pageErrors.length}`
      );
      if (f.pageErrors.length) console.log(`      pageErrors: ${JSON.stringify(f.pageErrors).slice(0, 300)}`);
    }
  }

  const outFile = path.join(__dirname, "results-prod-crawl.json");
  fs.writeFileSync(
    outFile,
    JSON.stringify(
      {
        discovered: { realBlogSlug, realTutorSlug, realCourseSlug, ownCourseId, ownCourseSlug },
        imageEvidence: { totalImgs: imgReport.length, storageImgs: storageImgs.length, brokenImgs, evidenceShot },
        results: allResults,
      },
      null,
      2
    )
  );
  console.log(`\nFull results written to ${outFile}`);
  if (failCount > 0) process.exitCode = 1;
}

main().catch((err) => {
  console.error("flow-prod-crawl FATAL:", err);
  process.exit(1);
});
