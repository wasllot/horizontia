import { createStagehand, login, BASE_URL } from "./helpers.js";
import { PROD_USERS } from "./prod-fixtures.js";

const COURSE_URL = `${BASE_URL}/course/time-management-mastery-boost-your-productivity`;

async function dumpCookies(context, label) {
  const cookies = await context.cookies();
  const relevant = cookies.filter((c) => /session|xsrf|remember/i.test(c.name));
  console.log(`\n--- cookies @ ${label} ---`);
  relevant.forEach((c) => console.log(`  ${c.name} = ${c.value.slice(0, 24)}... (domain=${c.domain}, path=${c.path})`));
  if (relevant.length === 0) console.log("  (none found)");
  return relevant;
}

async function main() {
  const stagehand = await createStagehand();
  const page = stagehand.page;
  const context = page.context();

  const navLog = [];
  page.on("response", (resp) => {
    const status = resp.status();
    if (status >= 300 && status < 400) {
      navLog.push(`REDIRECT ${status}: ${resp.url()} -> ${resp.headers()["location"]}`);
    }
  });
  page.on("requestfailed", (req) => {
    navLog.push(`REQUEST FAILED: ${req.url()} (${req.failure()?.errorText})`);
  });

  console.log("Logging in as QA student:", PROD_USERS.student.email);
  await login(page, PROD_USERS.student.email, PROD_USERS.student.password);
  console.log("Post-login URL:", page.url());
  await dumpCookies(context, "immediately after login");

  console.log("\nNavigating to course page:", COURSE_URL);
  const resp = await page.goto(COURSE_URL, { waitUntil: "domcontentloaded" });
  console.log("Course page status:", resp.status());
  await page.waitForLoadState("networkidle", { timeout: 15000 }).catch(() => {});
  console.log("URL after course page load:", page.url());

  await dumpCookies(context, "after course page load");

  const loginBtnVisible = await page.locator('a:has-text("Iniciar sesión"), a:has-text("Iniciar Sesion")').count();
  console.log("\n'Iniciar sesión' button visible on course page:", loginBtnVisible > 0 ? "YES (logged out!)" : "no");

  // Try hitting an authenticated-only endpoint directly to double check session state server-side.
  const bookingsResp = await page.goto(`${BASE_URL}/student/bookings`, { waitUntil: "domcontentloaded" });
  console.log("\nGET /student/bookings status:", bookingsResp.status(), "final URL:", page.url());
  const stillLoggedIn = !page.url().includes("/login");
  console.log("Still authenticated after course page visit:", stillLoggedIn ? "YES" : "NO - SESSION WAS DROPPED");

  console.log("\n--- redirect / failed-request log during this whole run ---");
  navLog.forEach((l) => console.log(" ", l));
  if (navLog.length === 0) console.log("  (none)");

  await page.screenshot({ path: "failures/diagnose-session-drop.png", fullPage: true }).catch(() => {});

  await stagehand.close();
}

main().catch((err) => {
  console.error("DIAGNOSE SCRIPT FAILED:", err);
  process.exit(1);
});
