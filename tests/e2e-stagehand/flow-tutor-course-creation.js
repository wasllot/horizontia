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
import { FIXTURES, USERS } from "./fixtures.js";
import { buildScormZip } from "./make-scorm-zip.js";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const FIXTURES_DIR = path.join(__dirname, "fixtures");
const SCORM_ZIP = path.join(FIXTURES_DIR, "qa-scorm-package.zip");
const THUMBNAIL = path.join(FIXTURES_DIR, "qa-thumbnail.png");
const PROMO_VIDEO = path.join(FIXTURES_DIR, "qa-promo-video.mp4");

buildScormZip(SCORM_ZIP);

let actCount = 0;
let extractCount = 0;

const steps = []; // {name, ok, error?, screenshot?}

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

/**
 * Locate the real Summernote `.note-editable` contenteditable surface
 * associated with a given source <textarea id="...">, and type into it via
 * genuine keyboard events (so Summernote's own internal 'summernote.change'
 * event fires and syncs to Livewire exactly like a real user typing would).
 */
function cssId(id) {
  // Minimal CSS.escape replacement for plain alphanumeric/hyphen/underscore ids
  // (Node has no global CSS.escape — that's a browser-only API).
  return String(id).replace(/([^a-zA-Z0-9_-])/g, "\\$1");
}

async function fillSummernote(page, textareaId, text) {
  const textarea = page.locator(`#${cssId(textareaId)}`);
  await textarea.waitFor({ state: "attached", timeout: 10000 });
  // Summernote Lite mounts .note-editor as a sibling near the original textarea;
  // search within a few ancestor levels for the associated .note-editable.
  const editable = page
    .locator(`#${cssId(textareaId)}`)
    .locator("xpath=ancestor::*[position()<=4]//div[contains(@class,'note-editable')]")
    .first();
  await editable.waitFor({ state: "visible", timeout: 10000 });
  await editable.click();
  await page.keyboard.press("Control+A");
  await page.keyboard.type(text, { delay: 5 });
  // Blur programmatically (NOT by clicking elsewhere on the page): summernote.change
  // already fires from real typing, and when this editor sits inside a Bootstrap modal
  // (e.g. the "add FAQ" modal), clicking anywhere outside the modal-content lands on the
  // modal backdrop and dismisses the whole modal as an unwanted side effect.
  await editable.evaluate((el) => el.blur());
  await page.waitForTimeout(300);
}

async function selectNative(page, selector, value) {
  await page.locator(selector).selectOption(String(value));
  await page.waitForTimeout(400); // allow the delegated select2 'change' -> Livewire round trip
}

async function courseIdFromUrl(page) {
  const m = page.url().match(/\/course\/edit\/[a-z-]+\/(\d+)/);
  if (!m) throw new Error(`could not parse course id from URL: ${page.url()}`);
  return m[1];
}

async function expandSection(page, sectionId) {
  // The accordion radio input itself is visually hidden (custom-styled accordion);
  // its <label for="accordion-item-{id}"> is the real clickable surface.
  await page.locator(`label[for="accordion-item-${sectionId}"]`).click();
  await page.waitForTimeout(200);
}

async function main() {
  const stagehand = await createStagehand();
  const page = stagehand.page;
  global.__page = page;
  const collectors = attachErrorCollectors(page);

  let courseId = null;
  let courseIdForEdit = null;

  // ---------------------------------------------------------------
  // 1. Tutor logs in
  // ---------------------------------------------------------------
  await step("tutor login", async () => {
    await login(page, USERS.tutor.email, USERS.tutor.password);
  });

  // ---------------------------------------------------------------
  // 2. Details tab
  // ---------------------------------------------------------------
  await step("create-course: navigate to wizard", async () => {
    await page.goto(BASE_URL + "/create-course", { waitUntil: "domcontentloaded" });
    await page.waitForSelector("#course-title", { timeout: 10000 });
  });

  await step("details: fill and submit basic details", async () => {
    const uniq = Date.now();
    await page.fill("#course-title", `QA E2E Full Course ${uniq}`);
    await page.fill("#course-subtitle", "A deeply-covered course created end-to-end by Playwright");
    await selectNative(page, "#category-select", FIXTURES.categoryId);
    await selectNative(page, "#sub-category-select", FIXTURES.subCategoryId);
    await fillSummernote(page, "description", "This course was created end-to-end by an automated browser test, exercising every curriculum content type.");
    // tags
    const tagInput = page.locator('input[x-model="newTag"]');
    await tagInput.fill("qa-e2e");
    await tagInput.press("Enter");
    await tagInput.fill("playwright");
    await tagInput.press("Enter");
    await selectNative(page, 'select[data-wiremodel="type"]', "all");
    await selectNative(page, 'select[data-wiremodel="level"]', "beginner");
    await selectNative(page, "#language", FIXTURES.languageId);

    await page.locator('button[wire\\:click="createOrUpdateCourse"]').click();
    await page.waitForURL((url) => /\/course\/edit\/media\/\d+/.test(url.pathname), { timeout: 15000 });
    courseId = await courseIdFromUrl(page);
    courseIdForEdit = courseId;
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
    // Livewire real upload — wait for the remove-video link to appear, proof the upload finished.
    await page.locator('a[wire\\:click\\.prevent="removeMedia(\'video\')"]').first().waitFor({ timeout: 30000 });

    await page.locator('button[wire\\:click="store"]').click();
    await page.waitForURL((url) => /\/course\/edit\/(pricing|content)\/\d+/.test(url.pathname), { timeout: 15000 });
  });

  // ---------------------------------------------------------------
  // 4. Pricing tab (paid-system setting enabled for this QA run)
  // ---------------------------------------------------------------
  await step("pricing: set a paid price with a discount", async () => {
    if (!/\/course\/edit\/pricing\//.test(page.url())) {
      throw new Error(`expected to land on pricing tab, got ${page.url()}`);
    }
    const freeToggle = page.locator("#cr-free-course-toggle");
    if (await freeToggle.isChecked()) {
      // The checkbox itself is wire:ignore + visually hidden by the custom toggle-switch
      // styling; its <label for="cr-free-course-toggle"> is the real clickable surface.
      await page.locator('label[for="cr-free-course-toggle"]').click();
      await page.waitForTimeout(300);
    }
    await page.fill("#price", "49.99");
    await page.waitForTimeout(600); // let the debounced price update recalc the discount table
    // The discount radios are custom-styled (input hidden, <label for="discount-10"> visible).
    await page.locator('label[for="discount-10"]').click();
    await page.waitForTimeout(300);
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
    await page.fill("#section-title", "QA Section Alpha");
    await fillSummernote(page, "section-desc", "First section, covers article/video/scorm/assignment lesson types.");
    await page.locator('button[wire\\:click="createSection"]').click();
    await page.waitForSelector('.accordion-item', { timeout: 10000 });
    // The newest section's accordion-item radio input id gives us its id.
    const ids = await page.locator('input.accordion-checkbox').evaluateAll((els) =>
      els.map((el) => el.id.replace("accordion-item-", ""))
    );
    sectionAId = ids[ids.length - 1];
    console.log("    section A id =", sectionAId);
    await expandSection(page, sectionAId);
  });

  async function addCurriculum(title) {
    await page.locator('button[wire\\:click="updateCurriculumState(true)"]').first().click();
    // #curriculum-title is a DUPLICATE id on this page (also used, hidden, by the separate
    // edit-curriculum modal further down the DOM) -- scope to the visible "add new" form
    // container (.cr-curriculum-state, only rendered while addCurriculumState is true) so
    // Playwright doesn't grab the wrong (hidden) element of the two matches.
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
    await addCurriculum("QA Article Lesson");
    await page.locator('li[wire\\:click="updateCurriculumType(\'article\')"]').first().click();
    const box = page.locator('textarea[id^="article_content-"]').first();
    const id = await box.getAttribute("id");
    await fillSummernote(page, id, "This is the article body content for the QA article lesson.");
    await page.locator('button[wire\\:click^="updateCurriculumContent"]').first().click();
    await page.waitForTimeout(800);
  });

  await step("content: add VIDEO (YouTube link) lesson", async () => {
    await addCurriculum("QA Video Lesson (YouTube)");
    await page.locator('li[wire\\:click="updateCurriculumType(\'video\')"]').first().click();
    // The yt_link radio input is custom-styled/hidden; its <label for="yt_link-{sectionId}">
    // is the real clickable surface (id is keyed by section id, not curriculum id).
    await page.locator(`label[for="yt_link-${sectionAId}"]`).click();
    await page.locator('input[wire\\:model\\.live\\.debounce\\.500ms="yt_link"]').first().fill("https://www.youtube.com/watch?v=dQw4w9WgXcQ");
    await page.waitForTimeout(600);
    await page.locator('button[wire\\:click^="updateCurriculumContent"]').first().click();
    await page.waitForTimeout(800);
  });

  await step("content: add SCORM lesson (real zip upload)", async () => {
    await addCurriculum("QA SCORM Lesson (new)");
    await page.locator('li[wire\\:click="updateCurriculumType(\'scorm\')"]').first().click();
    await page.setInputFiles('input[wire\\:model="scorm_file"]', SCORM_ZIP);
    await page.waitForTimeout(1000);
    await page.locator('button[wire\\:click^="updateCurriculumContent"]').first().click();
    await page.waitForSelector("text=SCORM file uploaded successfully", { timeout: 20000 });
  });

  let assignmentCurriculumId = null;
  await step("content: add ASSIGNMENT lesson", async () => {
    await addCurriculum("QA Assignment Lesson");
    await page.locator('li[wire\\:click="updateCurriculumType(\'assignment\')"]').first().click();
    const box = page.locator('textarea[id^="article_content-"]').first();
    const id = await box.getAttribute("id");
    assignmentCurriculumId = id.replace("article_content-", "");
    await fillSummernote(page, id, "Assignment instructions for the QA assignment lesson.");
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

    // Attempt 1: raw mouse down/move/up sequence (works for mouse-emulated sortable libs).
    try {
      const first = handles.first();
      const last = handles.last();
      const fb = await first.boundingBox();
      const lb = await last.boundingBox();
      if (fb && lb) {
        await page.mouse.move(fb.x + fb.width / 2, fb.y + fb.height / 2);
        await page.mouse.down();
        const steps = 12;
        for (let i = 1; i <= steps; i++) {
          const x = fb.x + ((lb.x - fb.x) * i) / steps;
          const y = fb.y + ((lb.y - fb.y) * i) / steps + 40; // drop slightly past target
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

    // Attempt 2: the "livewire-sortable" package wraps SortableJS, which by default uses
    // native HTML5 drag-and-drop (not just mouse events) -- Playwright's dragTo() simulates
    // the real dragstart/dragover/drop lifecycle, which a raw mouse sequence does not.
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
      // Plain Playwright mouse drag didn't move SortableJS's list — fall back to a
      // single .act() call for this one genuinely finicky drag-and-drop interaction,
      // exactly the kind of case the brief calls out as worth spending an .act() on.
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
    // The delete link lives inside a Bootstrap dropdown menu (.am-itemdropdown_list) that's
    // only rendered visible after clicking its "..." kebab toggle -- open it first.
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
    await page.fill("#section-title", "QA Section Beta (to delete)");
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
    const answerId = "answer";
    await fillSummernote(page, answerId, "Just a computer and curiosity.");
    await page.locator('button[wire\\:click="addFaq"]').click();
    await page.locator("#create-faq").waitFor({ state: "hidden", timeout: 10000 });

    await fillSummernote(page, "prerequisites", "No prior experience required.");
    await page.locator('button[wire\\:click\\.prevent="save"]').click();
    await page.waitForURL((url) => /\/course\/edit\/noticeboard\/\d+/.test(url.pathname), { timeout: 15000 });
  });

  // ---------------------------------------------------------------
  // 7. Noticeboard -> sets status to under_review -> Publish tab
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
  // 8. Validation edge case: submit details tab empty on a 2nd course
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
  // 9. Edit the course created in step 2 afterward
  // ---------------------------------------------------------------
  await step("edit existing course: change title, re-save", async () => {
    await page.goto(BASE_URL + `/course/edit/details/${courseIdForEdit}`, { waitUntil: "domcontentloaded" });
    await page.waitForSelector("#course-title", { timeout: 10000 });
    const existingTitle = await page.locator("#course-title").inputValue();
    if (!existingTitle || !existingTitle.startsWith("QA E2E Full Course")) {
      throw new Error(`expected pre-filled title, got "${existingTitle}"`);
    }
    await page.fill("#course-title", existingTitle + " EDITED");
    await page.locator('button[wire\\:click="createOrUpdateCourse"]').click();
    await page.waitForURL((url) => /\/course\/edit\/media\/\d+/.test(url.pathname), { timeout: 15000 });
  });

  // ---------------------------------------------------------------
  // 10. Live-stream scheduling (separate tutor feature, not a curriculum
  //     type) — schedule one with a placeholder link, sanity-check via
  //     .extract() since the calendar widget is complex third-party UI.
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
    await login(page, USERS.admin.email, USERS.admin.password);
    await page.goto(BASE_URL + "/admin/courses", { waitUntil: "domcontentloaded" });
    await page.waitForLoadState("networkidle", { timeout: 10000 }).catch(() => {});
    const approveLink = page.locator(`a[wire\\:click="approveCourse(${courseId})"]`);
    if ((await approveLink.count()) === 0) {
      throw new Error(`no approve link found for course id ${courseId} on admin/courses listing`);
    }
    await approveLink.first().click();
    await page.waitForTimeout(1000);
  });

  await step("verify course status is now active (DB-level check via page)", async () => {
    // Re-visit the admin course listing and confirm the approve link is now gone
    // (it's only rendered while status === 'under_review').
    await page.goto(BASE_URL + "/admin/courses", { waitUntil: "domcontentloaded" });
    await page.waitForLoadState("networkidle", { timeout: 10000 }).catch(() => {});
    const approveLink = page.locator(`a[wire\\:click="approveCourse(${courseId})"]`);
    if ((await approveLink.count()) > 0) {
      throw new Error(`course ${courseId} still shows an approve link after approval — status may not have changed`);
    }
  });

  // ---------------------------------------------------------------
  // 12. Confirm the new, now-active course appears in public search
  // ---------------------------------------------------------------
  await step("verify new course appears in public search-courses listing", async () => {
    await logout(page);
    await page.goto(BASE_URL + "/search-courses", { waitUntil: "domcontentloaded" });
    await page.fill("#keyword", "QA E2E Full Course");
    await page.waitForTimeout(1200); // debounced wire:model.live search
    const found = await page.locator('h2.cr-course-title a[href*="/course/"]').filter({ hasText: "QA E2E Full Course" }).count();
    if (found === 0) throw new Error("newly-created & approved course not found in search-courses results");
  });

  await stagehand.close();

  console.log("\n=== flow-tutor-course-creation summary ===");
  const passed = steps.filter((s) => s.ok).length;
  const failed = steps.filter((s) => !s.ok);
  console.log(`PASS: ${passed}/${steps.length}  FAIL: ${failed.length}`);
  for (const f of failed) {
    console.log(`  FAIL: ${f.name} :: ${f.error} ${f.screenshot ? "(screenshot: " + f.screenshot + ")" : ""}`);
  }
  console.log(`act() calls used: ${actCount}, extract() calls used: ${extractCount}`);

  const fs = await import("fs");
  fs.writeFileSync(
    path.join(__dirname, "results-tutor-course-creation.json"),
    JSON.stringify({ steps, courseId, actCount, extractCount }, null, 2)
  );

  if (failed.length > 0) process.exitCode = 1;
}

main().catch(async (err) => {
  console.error("flow-tutor-course-creation FATAL:", err);
  process.exitCode = 1;
});
