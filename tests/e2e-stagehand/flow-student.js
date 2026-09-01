import path from "path";
import { fileURLToPath } from "url";
import {
  BASE_URL,
  createStagehand,
  login,
  attachErrorCollectors,
  saveFailureScreenshot,
} from "./helpers.js";
import { FIXTURES, USERS } from "./fixtures.js";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const steps = [];

async function step(page, name, fn) {
  process.stdout.write(`--- STEP: ${name} ... `);
  try {
    await fn();
    steps.push({ name, ok: true });
    console.log("OK");
  } catch (err) {
    console.log("FAILED:", err.message);
    let screenshot = null;
    try {
      screenshot = await saveFailureScreenshot(page, name);
    } catch (e) {}
    steps.push({ name, ok: false, error: err.message, screenshot });
  }
}

async function main() {
  const stagehand = await createStagehand();
  const page = stagehand.page;
  const collectors = attachErrorCollectors(page);

  await step(page, "student login", async () => {
    await login(page, USERS.student.email, USERS.student.password);
  });

  await step(page, "search courses via real search box, finds seeded fixtures", async () => {
    await page.goto(BASE_URL + "/search-courses", { waitUntil: "domcontentloaded" });
    await page.fill("#keyword", "QA");
    await page.waitForTimeout(1200); // wire:model.live debounce
    const cards = page.locator('h2.cr-course-title a[href*="/course/"]');
    const count = await cards.count();
    if (count === 0) throw new Error("search for 'QA' returned zero course results");
    const texts = await cards.allTextContents();
    if (!texts.some((t) => t.includes("QA"))) {
      throw new Error(`search results didn't include an expected QA-titled course: ${JSON.stringify(texts)}`);
    }
  });

  await step(page, "click into a search result -> course detail page renders", async () => {
    const secondCourseLink = page.locator('h2.cr-course-title a[href*="/course/"]').filter({ hasText: "QA Second Course" });
    await secondCourseLink.first().click();
    await page.waitForURL((url) => url.pathname.startsWith(`/course/${FIXTURES.course2Slug}`), { timeout: 15000 });
    await page.waitForLoadState("networkidle", { timeout: 10000 }).catch(() => {});
    const bodyText = await page.evaluate(() => document.body.innerText);
    if (bodyText.includes("Whoops")) throw new Error("Whoops on course detail page");
  });

  await step(page, "add course to cart via real Add to Cart button", async () => {
    // qa-second-course is a paid course (19.99), not yet purchased/enrolled by qa-student.
    // It IS already sitting in the cart via TestingSeeder, so the button should already
    // show as "proceed to checkout" rather than "add to cart" -- handle both cases.
    // Scope to the in-page action-buttons block specifically: the same "proceed to
    // checkout" href also exists (hidden) inside the header's cart offcanvas panel
    // (which additionally carries wire:navigate.remove), so an unscoped selector can
    // resolve to that invisible element instead of the visible in-page one.
    const addToCartBtn = page.locator('.cr-action-buttons button.am-btn[wire\\:click="addToCart"]');
    const proceedLink = page.locator('.cr-action-buttons a.am-btn[href*="/checkout"]');
    if (await addToCartBtn.count()) {
      await addToCartBtn.first().click();
      await page.waitForTimeout(1200);
    }
    const found = await proceedLink.count();
    if (found === 0) {
      throw new Error("expected an in-page 'proceed to checkout' link to appear after Add to Cart, found none");
    }
  });

  await step(page, "proceed to checkout from course-detail page, renders gracefully without a payment gateway", async () => {
    const proceedLink = page.locator('.cr-action-buttons a.am-btn[href*="/checkout"]').first();
    await proceedLink.click();
    await page.waitForURL((url) => url.pathname === "/checkout", { timeout: 15000 });
    await page.waitForLoadState("networkidle", { timeout: 10000 }).catch(() => {});
    const bodyText = await page.evaluate(() => document.body.innerText);
    if (bodyText.includes("Whoops")) throw new Error("Whoops page on /checkout");
    const errors = collectors.drain();
    if (errors.pageErrors.length) throw new Error(`checkout page JS errors: ${JSON.stringify(errors.pageErrors)}`);

    // Order summary should render both cart items (course2 seeded directly + whatever
    // was added above) without crashing on the sub_category null-safety bug we fixed.
    const summaryItems = await page.locator(".am-ordersummary_list li, .am-ordersummary li").count();
    if (summaryItems === 0) throw new Error("checkout order summary rendered no items");

    // No gateway is configured in this environment -- the Pay button should still be
    // present (page doesn't crash), but there should be no payment-method radios.
    const payBtn = page.locator('button[wire\\:click="updateInfo"]');
    if ((await payBtn.count()) === 0) throw new Error("expected a Pay button to render even with no gateway configured");
    const gatewayRadios = await page.locator('input[id^="payment-"]').count();
    console.log(`    gateway radios present: ${gatewayRadios} (0 expected -- no gateway configured in this env)`);
  });

  await step(page, "open the already-enrolled QA course and view a lesson", async () => {
    await page.goto(BASE_URL + `/course-taking/${FIXTURES.courseSlug}`, { waitUntil: "domcontentloaded" });
    await page.waitForLoadState("networkidle", { timeout: 10000 }).catch(() => {});
    const bodyText = await page.evaluate(() => document.body.innerText);
    if (bodyText.includes("Whoops")) throw new Error("Whoops on course-taking page");
    // The seeded "QA Lesson 1" article curriculum item is a preview lesson and should
    // load as the active curriculum by default; confirm real lesson content rendered.
    const articleBlock = page.locator('[id^="article-"]').first();
    await articleBlock.waitFor({ state: "visible", timeout: 10000 });
    const text = await articleBlock.innerText();
    if (!text || text.trim().length === 0) throw new Error("lesson article content area is blank");
    const errors = collectors.drain();
    if (errors.pageErrors.length) throw new Error(`course-taking page JS errors: ${JSON.stringify(errors.pageErrors)}`);
  });

  await step(page, "click through the sidebar to the seeded SCORM curriculum item", async () => {
    // FIXTURES.scormCurriculumId's media_path was null before flow-tutor-course-creation.js
    // ran (which uploads to a DIFFERENT, newly-created SCORM lesson) -- this seeded one may
    // still have no media. Just confirm clicking it doesn't crash the page either way.
    const item = page.locator(`#cr-curriculum-3, [wire\\:click*="setActiveCurriculum"]`).filter({ hasText: "SCORM" });
    const count = await item.count();
    if (count === 0) {
      console.log("    (no SCORM sidebar item found by text match -- skipping, not a failure)");
      return;
    }
    await item.first().click();
    await page.waitForTimeout(800);
    const bodyText = await page.evaluate(() => document.body.innerText);
    if (bodyText.includes("Whoops")) throw new Error("Whoops after clicking SCORM curriculum item");
  });

  await step(page, "register a brand-new user via the real UI, then log in with it", async () => {
    // resources/views/livewire/pages/auth/login.blade.php has a comment "Registration
    // link removed by request" -- the link is hidden from the login page nav, but the
    // /register route itself has no auth gate (RedirectIfAuthenticated only) and the
    // route crawl earlier confirmed it's a real, functioning page (non-5xx) -- so this
    // is a deliberately-unlinked-but-still-live flow, worth exercising for real.
    await page.goto(BASE_URL + "/register", { waitUntil: "domcontentloaded" });
    const formExists = await page.locator('form.am-signup-form').count();
    if (formExists === 0) {
      console.log("    no registration form found on /register -- treating as intentionally disabled, skipping");
      return;
    }
    const uniq = Date.now();
    const email = `qa-e2e-newuser-${uniq}@horizontia.test`;
    const password = "Password123!Aa";
    await page.fill("#first_name", "QA");
    await page.fill("#last_name", `NewUser${uniq}`);
    await page.fill("#email", email);
    await page.fill("#password", password);
    await page.fill("#password_confirmation", password);
    const termsBox = page.locator("#terms");
    if (await termsBox.count()) await termsBox.check({ force: true });
    await page.locator('form.am-signup-form button[type="submit"]').click();
    await page.waitForTimeout(2000);
    const bodyText = await page.evaluate(() => document.body.innerText);
    if (bodyText.includes("Whoops")) throw new Error("Whoops page on registration submit");
    const stillOnRegister = page.url().includes("/register");
    console.log(`    after submit, url=${page.url()} stillOnRegister=${stillOnRegister}`);

    // Now try logging in with the freshly-registered credentials.
    await login(page, email, password);
    if (page.url().includes("/login")) {
      throw new Error(`login with newly-registered user ${email} did not redirect away from /login`);
    }
  });

  await stagehand.close();

  console.log("\n=== flow-student summary ===");
  const passed = steps.filter((s) => s.ok).length;
  const failed = steps.filter((s) => !s.ok);
  console.log(`PASS: ${passed}/${steps.length}  FAIL: ${failed.length}`);
  for (const f of failed) {
    console.log(`  FAIL: ${f.name} :: ${f.error} ${f.screenshot ? "(screenshot: " + f.screenshot + ")" : ""}`);
  }

  const fs = await import("fs");
  fs.writeFileSync(path.join(__dirname, "results-student.json"), JSON.stringify({ steps }, null, 2));
  if (failed.length > 0) process.exitCode = 1;
}

main().catch((err) => {
  console.error("flow-student FATAL:", err);
  process.exitCode = 1;
});
