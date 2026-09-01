// PRODUCTION QA: admin creates a brand-new throwaway user via the real admin/users UI
// (not a backend script). Exercises resources/views/livewire/pages/admin/users/users.blade.php
// -> app/Livewire/Pages/Admin/Users/Users.php::addUser(). Every field is clearly QA-labeled.
import path from "path";
import { fileURLToPath } from "url";
import {
  BASE_URL,
  createStagehand,
  login,
  attachErrorCollectors,
  saveFailureScreenshot,
} from "./helpers.js";
import { QA_MARKER, uniqSuffix, PROD_USERS } from "./prod-fixtures.js";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const steps = [];
let createdUserEmail = null;
let createdUserRole = null;

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

  await step(page, "admin login", async () => {
    await login(page, PROD_USERS.admin.email, PROD_USERS.admin.password);
  });

  await step(page, "navigate to admin/users", async () => {
    await page.goto(BASE_URL + "/admin/users", { waitUntil: "domcontentloaded" });
    await page.waitForLoadState("networkidle", { timeout: 10000 }).catch(() => {});
    const bodyText = await page.evaluate(() => document.body.innerText);
    if (bodyText.includes("Whoops")) throw new Error("Whoops on /admin/users");
  });

  await step(page, "open 'add new user' modal, fill + submit real form", async () => {
    const uniq = uniqSuffix();
    createdUserEmail = `qa-ui-created-tutor+${uniq}@horizontia-qa.test`;
    createdUserRole = "tutor";

    const addBtn = page.locator('[data-bs-target="#tb-add-user"]');
    await addBtn.first().click();
    await page.locator("#add_user_form").waitFor({ state: "visible", timeout: 10000 });

    await page.fill('#add_user_form input[wire\\:model="form.first_name"]', `${QA_MARKER}UIUser`);
    await page.fill('#add_user_form input[wire\\:model="form.last_name"]', uniq);
    await page.fill('#add_user_form input[wire\\:model="form.email"]', createdUserEmail);

    // #user_role is a real <select> underneath select2 styling — selectOption() drives the
    // underlying element directly, which is enough to update the Livewire-bound value.
    await page.locator("#user_role").selectOption(createdUserRole);
    await page.waitForTimeout(300);

    const password = "QaUiCreated123!Aa";
    await page.fill('#add_user_form input[wire\\:model="form.password"]', password);
    await page.fill('#add_user_form input[wire\\:model="form.confirm_password"]', password);

    await page.locator('#add_user_form button[type="submit"]').click();
    await page.waitForTimeout(1500);

    const bodyText = await page.evaluate(() => document.body.innerText);
    if (bodyText.includes("Whoops")) throw new Error("Whoops after submitting add-user form");
  });

  await step(page, "confirm new user appears in admin/users listing", async () => {
    await page.goto(BASE_URL + "/admin/users?role=" + createdUserRole, { waitUntil: "domcontentloaded" });
    await page.waitForLoadState("networkidle", { timeout: 10000 }).catch(() => {});
    // Search box filters by profile first/last name.
    const searchBox = page.locator('input[wire\\:model\\.live\\.debounce\\.500ms="search"]');
    if (await searchBox.count()) {
      await searchBox.first().fill("QA TEST");
      await page.waitForTimeout(1000);
    }
    const bodyText = await page.evaluate(() => document.body.innerText);
    if (!bodyText.includes(createdUserEmail) && !bodyText.includes("QA TEST")) {
      throw new Error(`newly-created user (${createdUserEmail}) not visible in admin/users listing after filtering`);
    }
  });

  await step(page, "new user can log in with the real UI", async () => {
    await page.goto(BASE_URL + "/logout", { waitUntil: "domcontentloaded" }).catch(() => {});
    await login(page, createdUserEmail, "QaUiCreated123!Aa");
    if (page.url().includes("/login")) {
      throw new Error(`login with newly UI-created user ${createdUserEmail} did not redirect away from /login`);
    }
  });

  await stagehand.close();

  console.log("\n=== flow-prod-admin-user summary ===");
  const passed = steps.filter((s) => s.ok).length;
  const failed = steps.filter((s) => !s.ok);
  console.log(`PASS: ${passed}/${steps.length}  FAIL: ${failed.length}`);
  for (const f of failed) {
    console.log(`  FAIL: ${f.name} :: ${f.error} ${f.screenshot ? "(screenshot: " + f.screenshot + ")" : ""}`);
  }
  console.log(`created user: ${createdUserEmail} (role=${createdUserRole})`);

  const fs = await import("fs");
  fs.writeFileSync(
    path.join(__dirname, "results-prod-admin-user.json"),
    JSON.stringify({ steps, createdUserEmail, createdUserRole }, null, 2)
  );
  if (failed.length > 0) process.exitCode = 1;
}

main().catch((err) => {
  console.error("flow-prod-admin-user FATAL:", err);
  process.exitCode = 1;
});
