import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";
import { createStagehand, login, logout, attachErrorCollectors, checkPage, summarize } from "./helpers.js";
import { FIXTURES, USERS } from "./fixtures.js";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const routes = JSON.parse(fs.readFileSync(path.join(__dirname, "routes-data.json"), "utf8"));

// Mirrors tests/Feature/PlatformSmokeTest.php::SKIPPED_ROUTES / isSkipped().
const SKIP_REASONS = {
  "social.redirect": "Real OAuth provider redirect (Google/etc) — no credentials in this env.",
  "social.callback": "Requires a real OAuth callback code/state from the provider.",
  "broadcasting/auth": "Requires a real Pusher/Reverb channel-auth request body; no broadcast driver configured.",
  "payment.process": "Requires a configured, live payment gateway (Stripe unconfigured per project notes).",
  "livewire.preview-file": "Requires a real, previously-uploaded temporary Livewire upload filename.",
  "livewire/livewire.js": "Static framework asset file, not application code.",
  "livewire/livewire.min.js.map": "Static framework asset file, not application code.",
  "verification.verify": "Requires a valid Laravel signed URL (id+hash) generated at send-time; cannot be forged in a black-box crawl.",
  "sanctum.csrf-cookie": "Trivial framework route; covered implicitly by every stateful request.",
  "google/callback": "Requires a real Google OAuth callback code.",
};

function isSkipped(route) {
  const { name, uri } = route;
  const key = name || uri;
  if (SKIP_REASONS[key]) return SKIP_REASONS[key];
  if (uri.startsWith("auth/{provider}")) return SKIP_REASONS["social.redirect"];
  if (name && name.startsWith("ltu.") && name !== "ltu.translation.index") {
    return "Third-party TranslationsUI package sub-route needing real translation/phrase model IDs; only the index page is crawled.";
  }
  return null;
}

function actorFor(middleware) {
  const flat = middleware.join("|");
  if (flat.includes("RoleMiddleware:admin")) return "admin";
  if (flat.includes("RoleMiddleware:tutor|student") || flat.includes("RoleMiddleware:student|tutor")) return "student";
  if (flat.includes("RoleMiddleware:admin|sub_admin")) return "admin";
  if (flat.includes("RoleMiddleware:tutor")) return "tutor";
  if (flat.includes("RoleMiddleware:student")) return "student";
  if (flat.includes("Authenticate")) return "student"; // authenticated, role-agnostic
  return "guest";
}

// Mirrors PlatformSmokeTest::resolvedUri() explicit map, adapted to our real fixture IDs.
function explicitResolve(key) {
  const map = {
    "admin.update-blog": `admin/blogs/update/${FIXTURES.blogId}`,
    "courses.admin.edit-course": `admin/course/edit/details/${FIXTURES.courseId}`,
    "courses.tutor.edit-course": `course/edit/details/${FIXTURES.courseId}`,
    "admin.manage-dispute": "admin/manage-dispute/1",
    "pagebuilder.build": `admin/pages/${FIXTURES.pageId}/build`,
    "page.edit": `admin/pages/${FIXTURES.pageId}/edit`,
    "admin.approve-user-identity": `admin/users/approve-identity/${FIXTURES.studentId}`,
    "blog-details": `blog/${FIXTURES.blogSlug}`,
    "courses.course-taking": `course-taking/${FIXTURES.courseSlug}`,
    "courses.course-detail": `course/${FIXTURES.courseSlug}`,
    "pagebuilder.iframe": `pages/${FIXTURES.pageId}/iframe`,
    pay: "pay/1",
    "password.reset": "reset-password/dummy-token-for-smoke-test",
    "courses.scorm.get-progress": `scorm/progress/${FIXTURES.scormCurriculumId}`,
    "courses.secure.video": "secure-video/courses/dummy.mp4",
    "student.complete-booking": `student/complete-booking/${FIXTURES.bookingId}`,
    "student.manage-dispute": `student/manage-dispute/${FIXTURES.bookingId}`,
    "student.reschedule-session": `student/reschedule-session/${FIXTURES.bookingId}`,
    "thank-you": "thank-you/1",
    "tutor.bookings.session-detail": `tutor/bookings/session-detail/${new Date().toISOString().slice(0, 10)}`,
    "tutor.manage-dispute": `tutor/manage-dispute/${FIXTURES.bookingId}`,
    "tutor-detail": `tutor/${FIXTURES.tutorSlug}`,
    "confirm-identity": `user/identity-confirmation/${FIXTURES.studentId}`,
    "ltu.translation.index": "admin/translations",
  };
  return map[key] ?? null;
}

function naiveResolve(uri) {
  if (!uri.includes("{")) return uri;
  if (uri === "{any}") return "about-us";
  const replacements = {
    "{id}": String(FIXTURES.courseId),
    "{slug}": FIXTURES.courseSlug,
    "{billing_detail}": "1",
    "{booking_cart}": "1",
    "{friend}": "1",
    "{identity_verification}": "1",
    "{message}": "1",
    "{thread}": "1",
    "{tutor_certification}": "1",
    "{tutor_education}": "1",
    "{tutor_experience}": "1",
    "{threadId}": "1",
    "{date}": new Date().toISOString().slice(0, 10),
    "{path}": "dummy.mp4",
    "{page}": String(FIXTURES.pageId),
  };
  let resolved = uri;
  for (const [k, v] of Object.entries(replacements)) {
    resolved = resolved.split(k).join(v);
  }
  return resolved.includes("{") ? null : resolved;
}

function planRoutes() {
  const plan = { admin: [], tutor: [], student: [], guest: [] };
  const skipped = [];
  const unresolvable = [];

  for (const route of routes) {
    const key = route.name || route.uri;
    const skipReason = isSkipped(route);
    if (skipReason) {
      skipped.push({ key, uri: route.uri, reason: skipReason });
      continue;
    }
    const resolved = explicitResolve(key) ?? naiveResolve(route.uri);
    if (resolved === null) {
      unresolvable.push({ key, uri: route.uri });
      continue;
    }
    const actor = actorFor(route.middleware);
    plan[actor].push({ key, uri: route.uri, path: resolved });
  }
  return { plan, skipped, unresolvable };
}

async function main() {
  const { plan, skipped, unresolvable } = planRoutes();
  console.log(
    `Route plan: admin=${plan.admin.length} tutor=${plan.tutor.length} student=${plan.student.length} guest=${plan.guest.length} skipped=${skipped.length} unresolvable=${unresolvable.length}`
  );
  if (unresolvable.length) {
    console.log("UNRESOLVABLE (treated as skipped, needs a mapping):", unresolvable);
  }

  const stagehand = await createStagehand();
  const page = stagehand.page;
  const collectors = attachErrorCollectors(page);

  const allResults = [];

  // Guest first (no login needed).
  for (const r of plan.guest) {
    const res = await checkPage(page, r.path, { collectors, label: `guest:${r.key}` });
    allResults.push({ actor: "guest", ...r, ...res });
  }

  for (const actorName of ["student", "tutor", "admin"]) {
    await logout(page);
    await login(page, USERS[actorName].email, USERS[actorName].password);
    for (const r of plan[actorName]) {
      const res = await checkPage(page, r.path, { collectors, label: `${actorName}:${r.key}` });
      allResults.push({ actor: actorName, ...r, ...res });
    }
  }

  await stagehand.close();

  const { pass, fail, passCount, failCount } = summarize(allResults);

  console.log(`\n=== Route crawl summary ===`);
  console.log(`PASS: ${passCount}  FAIL: ${failCount}  SKIPPED: ${skipped.length}  UNRESOLVABLE: ${unresolvable.length}`);
  if (fail.length) {
    console.log("\nFAILURES:");
    for (const f of fail) {
      console.log(
        `  [${f.actor}] ${f.key} (${f.uri}) -> status=${f.status} navError=${f.navError} whoops=${f.whoops} consoleErrors=${f.consoleErrors.length} pageErrors=${f.pageErrors.length}`
      );
      if (f.pageErrors.length) console.log(`      pageErrors: ${JSON.stringify(f.pageErrors).slice(0, 300)}`);
      if (f.consoleErrors.length) console.log(`      consoleErrors: ${JSON.stringify(f.consoleErrors).slice(0, 300)}`);
    }
  }

  const outFile = path.join(__dirname, "results-crawl-routes.json");
  fs.writeFileSync(
    outFile,
    JSON.stringify({ plan: { admin: plan.admin.length, tutor: plan.tutor.length, student: plan.student.length, guest: plan.guest.length }, skipped, unresolvable, results: allResults }, null, 2)
  );
  console.log(`\nFull results written to ${outFile}`);
}

main().catch((err) => {
  console.error("crawl-routes FAILED:", err);
  process.exit(1);
});
