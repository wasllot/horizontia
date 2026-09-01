import path from "path";
import { fileURLToPath } from "url";
import {
  BASE_URL,
  createStagehand,
  login,
  attachErrorCollectors,
  saveFailureScreenshot,
} from "./helpers.js";
import { USERS } from "./fixtures.js";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const THUMBNAIL = path.join(__dirname, "fixtures", "qa-thumbnail.png");

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
  // Blur programmatically, not via an outside click (which could dismiss an enclosing modal).
  await editable.evaluate((el) => el.blur());
  await page.waitForTimeout(300);
}

async function main() {
  const stagehand = await createStagehand();
  const page = stagehand.page;
  const collectors = attachErrorCollectors(page);

  let categoryName = null;

  await step(page, "admin login", async () => {
    await login(page, USERS.admin.email, USERS.admin.password);
  });

  await step(page, "create a blog category via real UI form", async () => {
    await page.goto(BASE_URL + "/admin/blog-categories", { waitUntil: "domcontentloaded" });
    await page.waitForSelector('input[wire\\:model\\.defer="name"]', { timeout: 10000 });
    const uniq = Date.now();
    categoryName = `QA E2E Category ${uniq}`;
    await page.fill('input[wire\\:model\\.defer="name"]', categoryName);
    await page.fill('textarea[wire\\:model\\.defer="description"]', "Category created end-to-end by an automated browser test.");
    await page.locator('a[wire\\:click\\.prevent="update"]').click();
    await page.waitForTimeout(1200);
    const bodyText = await page.evaluate(() => document.body.innerText);
    if (bodyText.includes("Whoops")) throw new Error("Whoops after creating blog category");
    if (!bodyText.includes(categoryName)) {
      throw new Error(`newly-created category "${categoryName}" not visible in the page after submit`);
    }
  });

  let blogTitle = null;
  await step(page, "create a blog post via real UI (title, category, rich text, image)", async () => {
    await page.goto(BASE_URL + "/admin/blogs/create", { waitUntil: "domcontentloaded" });
    await page.waitForSelector('input[wire\\:model="title"]', { timeout: 10000 });
    const uniq = Date.now();
    blogTitle = `QA E2E Blog Post ${uniq}`;
    await page.fill('input[wire\\:model="title"]', blogTitle);

    // pick our just-created category (or fall back to the first available option)
    const catSelect = page.locator("#category_ids");
    const optionValues = await catSelect.locator("option").evaluateAll((opts) =>
      opts.map((o) => ({ value: o.value, text: o.textContent }))
    );
    const match = optionValues.find((o) => o.text && o.text.includes(categoryName || "")) || optionValues.find((o) => o.value);
    if (!match) throw new Error("no category options available to select on create-blog form");
    await catSelect.selectOption(match.value);
    await page.waitForTimeout(300);

    await fillSummernote(page, "blog_desc", "This blog post body was written end-to-end by an automated Playwright browser test.");

    await page.setInputFiles("#image", THUMBNAIL);
    await page.waitForTimeout(1000);

    await page.fill('input[wire\\:model="meta_title"]', blogTitle + " meta title");
    await page.fill('textarea[wire\\:model="meta_description"]', "Meta description for the QA E2E blog post.");

    await page.locator('button[wire\\:click="store"]').click();
    await page.waitForURL((url) => url.pathname.includes("/admin/blogs") && !url.pathname.includes("/create"), { timeout: 15000 });
    const bodyText = await page.evaluate(() => document.body.innerText);
    if (bodyText.includes("Whoops")) throw new Error("Whoops after creating blog post");
  });

  await step(page, "confirm new blog appears in admin blog listing", async () => {
    await page.goto(BASE_URL + "/admin/blogs", { waitUntil: "domcontentloaded" });
    await page.waitForLoadState("networkidle", { timeout: 10000 }).catch(() => {});
    const bodyText = await page.evaluate(() => document.body.innerText);
    if (!blogTitle || !bodyText.includes(blogTitle)) {
      throw new Error(`new blog "${blogTitle}" not found in admin blog listing`);
    }
  });

  await stagehand.close();

  console.log("\n=== flow-admin summary ===");
  const passed = steps.filter((s) => s.ok).length;
  const failed = steps.filter((s) => !s.ok);
  console.log(`PASS: ${passed}/${steps.length}  FAIL: ${failed.length}`);
  for (const f of failed) {
    console.log(`  FAIL: ${f.name} :: ${f.error} ${f.screenshot ? "(screenshot: " + f.screenshot + ")" : ""}`);
  }

  const fs = await import("fs");
  fs.writeFileSync(path.join(__dirname, "results-admin.json"), JSON.stringify({ steps }, null, 2));
  if (failed.length > 0) process.exitCode = 1;
}

main().catch((err) => {
  console.error("flow-admin FATAL:", err);
  process.exitCode = 1;
});
