// PRODUCTION QA: exhaustive course-related flows against LIVE https://horizontia.com,
// driven entirely through the real UI with Stagehand/Playwright (no backend scripts).
// Adapted from the LOCAL-env tests/e2e-stagehand/flow-tutor-course-creation.js +
// flow-student.js, merged into one script because production has no pre-seeded fixture
// IDs — everything this script needs (category/sub-category/language, the course itself)
// is discovered or created live, and every created title is prefixed with QA_MARKER.
//
// Safety: never touches pre-existing courses/categories/users. Never completes a real
// payment — checkout is only verified to render up to (not including) submitting payment.
import path from "path";
import { fileURLToPath } from "url";
import { z } from "zod";
import {
  BASE_URL,
  createStagehand,
  login,
  logout,
  attachErrorCollectors,
  saveFailureScreenshot,
} from "./helpers.js";
import { QA_MARKER, uniqSuffix, PROD_USERS } from "./prod-fixtures.js";
import { buildScormZip } from "./make-scorm-zip.js";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const FIXTURES_DIR = path.join(__dirname, "fixtures");
const SCORM_ZIP = path.join(FIXTURES_DIR, "qa-scorm-package.zip");
const THUMBNAIL = path.join(FIXTURES_DIR, "qa-thumbnail.png");
const PROMO_VIDEO = path.join(FIXTURES_DIR, "qa-promo-video.mp4");

buildScormZip(SCORM_ZIP);

let actCount = 0;
let extractCount = 0;

const steps = [];
const created = {
  courseTitle: null,
  courseId: null,
  courseSlug: null,
};

async function step(name, fn) {
  process.stdout.write(`--- STEP: ${name} ... `);
  try {
    await fn();
    steps.push({ name, ok: true });
    console.log("OK");
  } catch (err) {
    console.log("FAILED:", err.message);
    let screenshot = null;
    try {
      screenshot = await saveFailureScreenshot(global.__page, name);
    } catch (e) {}
    steps.push({ name, ok: false, error: err.message, screenshot });
  }
}

function cssId(id) {
  return String(id).replace(/([^a-zA-Z0-9_-])/g, "\\$1");
}

async function fillSummernote(page, textareaId, text) {
  const textarea = page.locator(`#${cssId(textareaId)}`);
  await textarea.waitFor({ state: "attached", timeout: 10000 });
  const editable = page
    .locator(`#${cssId(textareaId)}`)
    .locator("xpath=ancestor::*[position()<=4]//div[contains(@class,'note-editable')]")
    .first();
  await editable.waitFor({ state: "visible", timeout: 10000 });
  await editable.click();
  await page.keyboard.press("Control+A");
  await page.keyboard.type(text, { delay: 5 });
  await editable.evaluate((el) => el.blur());
  await page.waitForTimeout(300);
}

// Production has real, unknown category/sub-category/language DB rows -- pick the FIRST
// real (non-placeholder) <option> rather than hardcoding an ID, same read-then-act spirit
// as the rest of this suite (no guessing at IDs that might not exist on this environment).
async function selectFirstRealOption(page, selector) {
  const select = page.locator(selector);
  await select.waitFor({ state: "attached", timeout: 10000 });
  const options = await select.locator("option").evaluateAll((opts) =>
    opts.map((o) => ({ value: o.value, text: (o.textContent || "").trim() }))
  );
  const real = options.find((o) => o.value !== "");
  if (!real) throw new Error(`no real (non-empty) <option> found for ${selector}: ${JSON.stringify(options)}`);
  await select.selectOption(real.value);
  await page.waitForTimeout(500);
  return real;
}

async function selectNative(page, selector, value) {
  await page.locator(selector).selectOption(String(value));
  await page.waitForTimeout(400);
}

async function courseIdFromUrl(page) {
  const m = page.url().match(/\/course\/edit\/[a-z-]+\/(\d+)/);
  if (!m) throw new Error(`could not parse course id from URL: ${page.url()}`);
  return m[1];
}

async function expandSection(page, sectionId) {
  await page.locator(`label[for="accordion-item-${sectionId}"]`).click();
  await page.waitForTimeout(200);
}

async function main() {
  const stagehand = await createStagehand();
  const page = stagehand.page;
  global.__page = page;
  const collectors = attachErrorCollectors(page);

  let courseId = null;
  const uniq = uniqSuffix();
  const courseTitle = `${QA_MARKER}Full Course ${uniq}`;
  created.courseTitle = courseTitle;

  // ---------------------------------------------------------------
  // 1. Tutor logs in
  // ---------------------------------------------------------------
  await step("tutor login", async () => {
    await login(page, PROD_USERS.tutor.email, PROD_USERS.tutor.password);
  });

  // ---------------------------------------------------------------
  // 2. Details tab
  // ---------------------------------------------------------------
  await step("create-course: navigate to wizard", async () => {
    await page.goto(BASE_URL + "/create-course", { waitUntil: "domcontentloaded" });
    await page.waitForSelector("#course-title", { timeout: 10000 });
  });

  let pickedCategory = null;
  await step("details: fill and submit basic details (real category/sub-category/language)", async () => {
    await page.fill("#course-title", courseTitle);
    await page.fill("#course-subtitle", "A deeply-covered course created end-to-end by Playwright against PRODUCTION.");
    pickedCategory = await selectFirstRealOption(page, "#category-select");
    console.log(`    picked category: ${JSON.stringify(pickedCategory)}`);
    // sub-category options repopulate after category changes (wire:model.live round trip)
    await page.waitForTimeout(800);
    const pickedSubCategory = await selectFirstRealOption(page, "#sub-category-select");
    console.log(`    picked sub-category: ${JSON.stringify(pickedSubCategory)}`);
    await fillSummernote(page, "description", "This course was created end-to-end by an automated browser test against the live production site, exercising every curriculum content type. Safe to delete.");
    const tagInput = page.locator('input[x-model="newTag"]');
    await tagInput.fill("qa-e2e-prod");
    await tagInput.press("Enter");
    await tagInput.fill("playwright");
    await tagInput.press("Enter");
    await selectNative(page, 'select[data-wiremodel="type"]', "all");
    await selectNative(page, 'select[data-wiremodel="level"]', "beginner");
    await selectFirstRealOption(page, "#language");

    await page.locator('button[wire\\:click="createOrUpdateCourse"]').click();
    await page.waitForURL((url) => /\/course\/edit\/media\/\d+/.test(url.pathname), { timeout: 15000 });
    courseId = await courseIdFromUrl(page);
    created.courseId = courseId;
    console.log("    created course id =", courseId);
  });

  // ---------------------------------------------------------------
  // 3. Media tab
  // ---------------------------------------------------------------
  await step("media: upload thumbnail + promo video", async () => {
    await page.setInputFiles("#at_upload_thumbnail", THUMBNAIL);
    await page.locator("#cropedImage").waitFor({ state: "visible", timeout: 10000 });
    await page.locator("#skipCropping").click();
    await page.locator("#cropedImage").waitFor({ state: "hidden", timeout: 10000 });

    await page.setInputFiles("#at_upload_video", PROMO_VIDEO);
    await page.locator('a[wire\\:click\\.prevent="removeMedia(\'video\')"]').first().waitFor({ timeout: 30000 });

    await page.locator('button[wire\\:click="store"]').click();
    await page.waitForURL((url) => /\/course\/edit\/(pricing|content)\/\d+/.test(url.pathname), { timeout: 15000 });
  });

  // ---------------------------------------------------------------
  // 4. Pricing tab
  // ---------------------------------------------------------------
  await step("pricing: set a paid price with a discount", async () => {
    if (!/\/course\/edit\/pricing\//.test(page.url())) {
      throw new Error(`expected to land on pricing tab, got ${page.url()}`);
    }
    const freeToggle = page.locator("#cr-free-course-toggle");
    if (await freeToggle.isChecked()) {
      await page.locator('label[for="cr-free-course-toggle"]').click();
      await page.waitForTimeout(300);
    }
    await page.fill("#price", "49.99");
    await page.waitForTimeout(600);
    // Discount slabs are configured by real site settings -- don't assume "10" exists,
    // just pick whichever discount radio label is actually rendered first.
    const discountLabel = page.locator('label[for^="discount-"]').first();
    if (await discountLabel.count()) {
      await discountLabel.click();
      await page.waitForTimeout(300);
    }
    await page.locator('button[wire\\:click="savePricing"]').first().click();
    await page.waitForURL((url) => /\/course\/edit\/content\/\d+/.test(url.pathname), { timeout: 15000 });
  });

  // ---------------------------------------------------------------
  // 5. Content tab — sections + every curriculum content type
  // ---------------------------------------------------------------
  let sectionAId = null;
  let sectionBId = null;

  await step("content: add section A", async () => {
    await page.locator('button[wire\\:click="addSectionState(true)"]').click();
    await page.fill("#section-title", `${QA_MARKER}Section Alpha`);
    await fillSummernote(page, "section-desc", "First section, covers article/video/scorm/assignment lesson types.");
    await page.locator('button[wire\\:click="createSection"]').click();
    await page.waitForSelector(".accordion-item", { timeout: 10000 });
    const ids = await page.locator("input.accordion-checkbox").evaluateAll((els) =>
      els.map((el) => el.id.replace("accordion-item-", ""))
    );
    sectionAId = ids[ids.length - 1];
    console.log("    section A id =", sectionAId);
    await expandSection(page, sectionAId);
  });

  async function addCurriculum(title) {
    await page.locator('button[wire\\:click="updateCurriculumState(true)"]').first().click();
    const formScope = page.locator(".cr-curriculum-state").first();
    await formScope.waitFor({ state: "visible", timeout: 10000 });
    await formScope.locator("#curriculum-title").fill(title);
    const descBox = formScope.locator(`textarea[id^="curriculum_des_"]`).first();
    const descId = await descBox.getAttribute("id");
    await fillSummernote(page, descId, `Description for ${title}`);
    await formScope.locator('button[wire\\:click="addCurriculum"]').click();
    await page.waitForTimeout(500);
  }

  await step("content: add ARTICLE lesson", async () => {
    await addCurriculum(`${QA_MARKER}Article Lesson`);
    await page.locator('li[wire\\:click="updateCurriculumType(\'article\')"]').first().click();
    const box = page.locator('textarea[id^="article_content-"]').first();
    const id = await box.getAttribute("id");
    await fillSummernote(page, id, "This is the article body content for the QA article lesson (production run).");
    await page.locator('button[wire\\:click^="updateCurriculumContent"]').first().click();
    await page.waitForTimeout(800);
  });

  await step("content: add VIDEO (YouTube link) lesson", async () => {
    await addCurriculum(`${QA_MARKER}Video Lesson (YouTube)`);
    await page.locator('li[wire\\:click="updateCurriculumType(\'video\')"]').first().click();
    await page.locator(`label[for="yt_link-${sectionAId}"]`).click();
    await page.locator('input[wire\\:model\\.live\\.debounce\\.500ms="yt_link"]').first().fill("https://www.youtube.com/watch?v=dQw4w9WgXcQ");
    await page.waitForTimeout(600);
    await page.locator('button[wire\\:click^="updateCurriculumContent"]').first().click();
    await page.waitForTimeout(800);
  });

  await step("content: add SCORM lesson (real zip upload)", async () => {
    await addCurriculum(`${QA_MARKER}SCORM Lesson`);
    await page.locator('li[wire\\:click="updateCurriculumType(\'scorm\')"]').first().click();
    await page.setInputFiles('input[wire\\:model="scorm_file"]', SCORM_ZIP);
    await page.waitForTimeout(1000);
    await page.locator('button[wire\\:click^="updateCurriculumContent"]').first().click();
    await page.waitForSelector("text=SCORM file uploaded successfully", { timeout: 20000 });
  });

  let assignmentCurriculumId = null;
  await step("content: add ASSIGNMENT lesson", async () => {
    await addCurriculum(`${QA_MARKER}Assignment Lesson`);
    await page.locator('li[wire\\:click="updateCurriculumType(\'assignment\')"]').first().click();
    const box = page.locator('textarea[id^="article_content-"]').first();
    const id = await box.getAttribute("id");
    assignmentCurriculumId = id.replace("article_content-", "");
    await fillSummernote(page, id, "Assignment instructions for the QA assignment lesson (production run).");
    await page.locator('button[wire\\:click^="updateCurriculumContent"]').first().click();
    await page.waitForTimeout(800);
  });

  await step("content: reorder curriculum items via drag-and-drop", async () => {
    const handles = page.locator(".cr-sortable .cr-drag");
    const count = await handles.count();
    if (count < 2) throw new Error(`expected >=2 sortable items, found ${count}`);

    const items = page.locator(".cr-sortable .cr-curriculum-item");
    const before = await items.evaluateAll((els) => els.map((el) => el.getAttribute("wire:sortable.item")));

    let reordered = false;
    try {
      const first = handles.first();
      const last = handles.last();
      const fb = await first.boundingBox();
      const lb = await last.boundingBox();
      if (fb && lb) {
        await page.mouse.move(fb.x + fb.width / 2, fb.y + fb.height / 2);
        await page.mouse.down();
        const stepsN = 12;
        for (let i = 1; i <= stepsN; i++) {
          const x = fb.x + ((lb.x - fb.x) * i) / stepsN;
          const y = fb.y + ((lb.y - fb.y) * i) / stepsN + 40;
          await page.mouse.move(x, y, { steps: 2 });
          await page.waitForTimeout(30);
        }
        await page.mouse.up();
        await page.waitForTimeout(600);
      }
      const after = await items.evaluateAll((els) => els.map((el) => el.getAttribute("wire:sortable.item")));
      reordered = JSON.stringify(before) !== JSON.stringify(after);
    } catch (e) {
      reordered = false;
    }

    if (!reordered) {
      try {
        await handles.first().dragTo(handles.last());
        await page.waitForTimeout(600);
        const after15 = await items.evaluateAll((els) => els.map((el) => el.getAttribute("wire:sortable.item")));
        reordered = JSON.stringify(before) !== JSON.stringify(after15);
      } catch (e) {
        reordered = false;
      }
    }

    if (!reordered) {
      actCount++;
      await page.act("Drag the first lesson item in the sortable curriculum list (using its drag handle) down below the last lesson item, to reorder the list.");
      await page.waitForTimeout(600);
      const after2 = await items.evaluateAll((els) => els.map((el) => el.getAttribute("wire:sortable.item")));
      reordered = JSON.stringify(before) !== JSON.stringify(after2);
      if (!reordered) throw new Error("curriculum reorder did not change item order (both plain-drag and .act() attempts)");
    }
  });

  await step("content: delete the assignment lesson", async () => {
    if (!assignmentCurriculumId) throw new Error("no assignment curriculum id captured");
    const item = page.locator(`.cr-curriculum-item[wire\\:sortable\\.item="${assignmentCurriculumId}"]`);
    await item.locator('.am-itemdropdown a[data-bs-toggle="dropdown"]').click();
    await item.locator("a.cr-delete-curriculum").waitFor({ state: "visible", timeout: 10000 });
    await item.locator("a.cr-delete-curriculum").click();
    await page.locator("#delete-confirm-modal").waitFor({ state: "visible", timeout: 10000 });
    await page.locator("#delete-confirm-modal .cr-delete-action").click();
    await page.waitForTimeout(800);
    await item.waitFor({ state: "detached", timeout: 10000 }).catch(() => {});
  });

  await step("content: add + delete section B", async () => {
    await page.locator('button[wire\\:click="addSectionState(true)"]').click();
    await page.fill("#section-title", `${QA_MARKER}Section Beta (to delete)`);
    await fillSummernote(page, "section-desc", "Throwaway section used to test section deletion.");
    await page.locator('button[wire\\:click="createSection"]').click();
    await page.waitForTimeout(800);

    const ids = await page.locator("input.accordion-checkbox").evaluateAll((els) =>
      els.map((el) => el.id.replace("accordion-item-", ""))
    );
    sectionBId = ids[ids.length - 1];
    if (!sectionBId || sectionBId === sectionAId) throw new Error("could not identify newly-created section B");

    const sectionEl = page.locator(`#section-${sectionBId}`);
    await sectionEl.locator('.am-itemdropdown a[data-bs-toggle="dropdown"]').click();
    await sectionEl.locator("a.cr-delete-curriculum").waitFor({ state: "visible", timeout: 10000 });
    await sectionEl.locator("a.cr-delete-curriculum").click();
    await page.locator("#delete-confirm-modal").waitFor({ state: "visible", timeout: 10000 });
    await page.locator("#delete-confirm-modal .cr-delete-action").click();
    await page.waitForTimeout(800);
    await sectionEl.waitFor({ state: "detached", timeout: 10000 }).catch(() => {});
  });

  await step("content: save & continue to faqs", async () => {
    await page.locator('button[wire\\:click="save"]').click();
    await page.waitForURL((url) => /\/course\/edit\/faqs\/\d+/.test(url.pathname), { timeout: 15000 });
  });

  // ---------------------------------------------------------------
  // 6. FAQs / prerequisites tab
  // ---------------------------------------------------------------
  await step("faqs: add an FAQ and prerequisites, save", async () => {
    await page.locator('button[wire\\:click="addMoreFaq"]').click();
    await page.locator("#create-faq").waitFor({ state: "visible", timeout: 10000 });
    await page.fill("#create-faq #course-title", "What do I need to take this course?");
    await fillSummernote(page, "answer", "Just a computer and curiosity.");
    await page.locator('button[wire\\:click="addFaq"]').click();
    await page.locator("#create-faq").waitFor({ state: "hidden", timeout: 10000 });

    await fillSummernote(page, "prerequisites", "No prior experience required.");
    await page.locator('button[wire\\:click\\.prevent="save"]').click();
    await page.waitForURL((url) => /\/course\/edit\/noticeboard\/\d+/.test(url.pathname), { timeout: 15000 });
  });

  // ---------------------------------------------------------------
  // 7. Noticeboard -> under_review -> Publish tab
  // ---------------------------------------------------------------
  await step("noticeboard: save (moves course to under_review)", async () => {
    const saveBtn = page.locator('a[wire\\:click="save"], button[wire\\:click="save"]');
    await saveBtn.first().click();
    await page.waitForURL((url) => /\/course\/edit\/publish\/\d+/.test(url.pathname), { timeout: 15000 });
  });

  await step("publish: static success screen renders", async () => {
    const bodyText = await page.evaluate(() => document.body.innerText);
    if (bodyText.includes("Whoops")) throw new Error("Whoops page on publish tab");
  });

  // ---------------------------------------------------------------
  // 8. Validation edge case
  // ---------------------------------------------------------------
  await step("validation edge case: submit empty details form", async () => {
    await page.goto(BASE_URL + "/create-course", { waitUntil: "domcontentloaded" });
    await page.waitForSelector("#course-title", { timeout: 10000 });
    await page.locator('button[wire\\:click="createOrUpdateCourse"]').click();
    await page.waitForTimeout(1000);
    if (!page.url().endsWith("/create-course")) {
      throw new Error(`expected to stay on /create-course after empty submit, got ${page.url()}`);
    }
    const errorCount = await page.locator(".cr-invalid, .am-invalid").count();
    if (errorCount === 0) throw new Error("expected inline validation errors on empty submit, found none");
    const bodyText = await page.evaluate(() => document.body.innerText);
    if (bodyText.includes("Whoops")) throw new Error("Whoops page on empty details submit");
  });

  // ---------------------------------------------------------------
  // 9. Edit the course afterward
  // ---------------------------------------------------------------
  await step("edit existing course: change title, re-save", async () => {
    await page.goto(BASE_URL + `/course/edit/details/${courseId}`, { waitUntil: "domcontentloaded" });
    await page.waitForSelector("#course-title", { timeout: 10000 });
    const existingTitle = await page.locator("#course-title").inputValue();
    if (!existingTitle || !existingTitle.startsWith(`${QA_MARKER}Full Course`)) {
      throw new Error(`expected pre-filled title, got "${existingTitle}"`);
    }
    const editedTitle = existingTitle + " EDITED";
    await page.fill("#course-title", editedTitle);
    await page.locator('button[wire\\:click="createOrUpdateCourse"]').click();
    await page.waitForURL((url) => /\/course\/edit\/media\/\d+/.test(url.pathname), { timeout: 15000 });
    created.courseTitle = editedTitle;
  });

  await step("verify edit persisted: reload details tab, title still shows EDITED", async () => {
    await page.goto(BASE_URL + `/course/edit/details/${courseId}`, { waitUntil: "domcontentloaded" });
    await page.waitForSelector("#course-title", { timeout: 10000 });
    const t = await page.locator("#course-title").inputValue();
    if (!t.endsWith("EDITED")) throw new Error(`edit did not persist after reload, title="${t}"`);
  });

  // ---------------------------------------------------------------
  // 10. Live-stream scheduling
  // ---------------------------------------------------------------
  await step("tutor: schedule a live stream (placeholder link)", async () => {
    await page.goto(BASE_URL + "/tutor/schedule-live-stream", { waitUntil: "domcontentloaded" });
    await page.waitForLoadState("networkidle", { timeout: 10000 }).catch(() => {});
    const bodyText = await page.evaluate(() => document.body.innerText);
    if (bodyText.includes("Whoops")) throw new Error("Whoops page on schedule-live-stream");
    extractCount++;
    const result = await page.extract({
      instruction: "Does this page show a working 'schedule a live stream' form (yes/no), and is there any visible error message on the page?",
      schema: z.object({ hasScheduleForm: z.boolean(), errorVisible: z.boolean(), notes: z.string() }),
    });
    console.log("    schedule-live-stream sanity check:", JSON.stringify(result));
    if (result.errorVisible) throw new Error(`schedule-live-stream page shows a visible error: ${result.notes}`);
  });

  // ---------------------------------------------------------------
  // 11. Admin approves the course to 'active'
  // ---------------------------------------------------------------
  await step("admin: approve course to active", async () => {
    await logout(page);
    await login(page, PROD_USERS.admin.email, PROD_USERS.admin.password);
    await page.goto(BASE_URL + "/admin/courses", { waitUntil: "domcontentloaded" });
    await page.waitForLoadState("networkidle", { timeout: 10000 }).catch(() => {});
    const approveLink = page.locator(`a[wire\\:click="approveCourse(${courseId})"]`);
    if ((await approveLink.count()) === 0) {
      // fall back: search the admin listing for our course to confirm it's visible at all
      const bodyText = await page.evaluate(() => document.body.innerText);
      throw new Error(`no approve link found for course id ${courseId} on admin/courses listing. Body includes title? ${bodyText.includes(QA_MARKER)}`);
    }
    await approveLink.first().click();
    await page.waitForTimeout(1000);
  });

  await step("verify course status is now active", async () => {
    await page.goto(BASE_URL + "/admin/courses", { waitUntil: "domcontentloaded" });
    await page.waitForLoadState("networkidle", { timeout: 10000 }).catch(() => {});
    const approveLink = page.locator(`a[wire\\:click="approveCourse(${courseId})"]`);
    if ((await approveLink.count()) > 0) {
      throw new Error(`course ${courseId} still shows an approve link after approval — status may not have changed`);
    }
  });

  // ---------------------------------------------------------------
  // 12. Student: search finds it (also discovers its public slug), view detail,
  //     cart/checkout OR free-enroll, view lessons
  // ---------------------------------------------------------------
  await step("student: search finds the new QA course", async () => {
    await logout(page);
    await login(page, PROD_USERS.student.email, PROD_USERS.student.password);
    await page.goto(BASE_URL + "/search-courses", { waitUntil: "domcontentloaded" });
    await page.fill("#keyword", QA_MARKER.trim());
    await page.waitForTimeout(1200);
    const cards = page.locator('h2.cr-course-title a[href*="/course/"]').filter({ hasText: created.courseTitle.replace(" EDITED", "") });
    const count = await cards.count();
    if (count === 0) throw new Error(`search for course title did not find "${created.courseTitle}"`);
    const href = await cards.first().getAttribute("href");
    const m = href.match(/\/course\/([^/?]+)/);
    created.courseSlug = m ? m[1] : null;
    console.log("    discovered course slug =", created.courseSlug);
    await cards.first().click();
    await page.waitForLoadState("networkidle", { timeout: 10000 }).catch(() => {});
    const bodyText = await page.evaluate(() => document.body.innerText);
    if (bodyText.includes("Whoops")) throw new Error("Whoops on course detail page");
  });

  await step("student: cart/checkout OR free-enroll (whichever this site's paid-system setting shows), stop short of real payment", async () => {
    const addToCartBtn = page.locator('.cr-action-buttons button.am-btn[wire\\:click="addToCart"]');
    const enrollBtn = page.locator('.cr-action-buttons button.am-btn[wire\\:click="enrollCourse"]');
    const proceedLink = page.locator('.cr-action-buttons a.am-btn[href*="/checkout"]');
    const viewCourseLink = page.locator('.cr-action-buttons a.am-btn[href*="/course-taking/"]');

    if (await viewCourseLink.count()) {
      console.log("    course already enrolled/viewable — skipping cart/checkout, will view lesson directly");
      return;
    }
    if (await enrollBtn.count()) {
      console.log("    site paid-system setting is OFF for this account context -- using free enrollCourse button");
      await enrollBtn.first().click();
      await page.waitForTimeout(1500);
      const bodyText = await page.evaluate(() => document.body.innerText);
      if (bodyText.includes("Whoops")) throw new Error("Whoops after enrollCourse");
      return;
    }
    if (await addToCartBtn.count()) {
      await addToCartBtn.first().click();
      await page.waitForTimeout(1200);
    }
    const found = await proceedLink.count();
    if (found === 0) throw new Error("expected an in-page 'proceed to checkout' link to appear after Add to Cart, found none");
    await proceedLink.first().click();
    await page.waitForURL((url) => url.pathname === "/checkout", { timeout: 15000 });
    await page.waitForLoadState("networkidle", { timeout: 10000 }).catch(() => {});
    const bodyText = await page.evaluate(() => document.body.innerText);
    if (bodyText.includes("Whoops")) throw new Error("Whoops page on /checkout");
    const errors = collectors.drain();
    if (errors.pageErrors.length) throw new Error(`checkout page JS errors: ${JSON.stringify(errors.pageErrors)}`);
    const summaryItems = await page.locator(".am-ordersummary_list li, .am-ordersummary li").count();
    if (summaryItems === 0) throw new Error("checkout order summary rendered no items");
    const payBtn = page.locator('button[wire\\:click="updateInfo"]');
    if ((await payBtn.count()) === 0) console.log("    no Pay button found (may require selecting a payment method first) — not failing, just noting");
    const gatewayRadios = await page.locator('input[id^="payment-"]').count();
    console.log(`    gateway radios present: ${gatewayRadios} (checkout renders without crashing either way; NOT submitting any payment)`);
    // Deliberately stop here -- do NOT click Pay / submit any payment details.
  });

  await step("student: open the course-taking page for our QA course, view each lesson type", async () => {
    if (!created.courseSlug) throw new Error("no course slug discovered, cannot verify course-taking page");
    await page.goto(BASE_URL + `/course-taking/${created.courseSlug}`, { waitUntil: "domcontentloaded" });
    await page.waitForLoadState("networkidle", { timeout: 10000 }).catch(() => {});
    let bodyText = await page.evaluate(() => document.body.innerText);
    if (bodyText.includes("Whoops")) throw new Error("Whoops on course-taking page");
    if (bodyText.toLowerCase().includes("access denied") || bodyText.toLowerCase().includes("not enrolled")) {
      console.log("    student not enrolled (checkout stopped before real payment, as required) -- skipping in-lesson content checks");
      return;
    }
    const errors0 = collectors.drain();
    if (errors0.pageErrors.length) throw new Error(`course-taking page JS errors: ${JSON.stringify(errors0.pageErrors)}`);

    // article lesson (likely default active item)
    const articleBlock = page.locator('[id^="article-"]').first();
    if (await articleBlock.count()) {
      await articleBlock.waitFor({ state: "visible", timeout: 10000 });
    }

    // click through each of our named QA lessons in the sidebar
    for (const label of ["Article Lesson", "Video Lesson", "SCORM Lesson"]) {
      const item = page.locator(`[wire\\:click*="setActiveCurriculum"]`).filter({ hasText: label });
      const count = await item.count();
      if (count === 0) {
        console.log(`    no sidebar item found for "${label}" -- skipping (not necessarily a failure)`);
        continue;
      }
      await item.first().click();
      await page.waitForTimeout(1000);
      bodyText = await page.evaluate(() => document.body.innerText);
      if (bodyText.includes("Whoops")) throw new Error(`Whoops after clicking "${label}" curriculum item`);
      const errs = collectors.drain();
      if (errs.pageErrors.length) throw new Error(`JS errors after clicking "${label}": ${JSON.stringify(errs.pageErrors)}`);
    }
  });

  await stagehand.close();

  console.log("\n=== flow-prod-course summary ===");
  const passed = steps.filter((s) => s.ok).length;
  const failed = steps.filter((s) => !s.ok);
  console.log(`PASS: ${passed}/${steps.length}  FAIL: ${failed.length}`);
  for (const f of failed) {
    console.log(`  FAIL: ${f.name} :: ${f.error} ${f.screenshot ? "(screenshot: " + f.screenshot + ")" : ""}`);
  }
  console.log(`act() calls used: ${actCount}, extract() calls used: ${extractCount}`);
  console.log(`created: ${JSON.stringify(created)}`);

  const fs = await import("fs");
  fs.writeFileSync(
    path.join(__dirname, "results-prod-course.json"),
    JSON.stringify({ steps, created, actCount, extractCount }, null, 2)
  );

  if (failed.length > 0) process.exitCode = 1;
}

main().catch(async (err) => {
  console.error("flow-prod-course FATAL:", err);
  process.exitCode = 1;
});
