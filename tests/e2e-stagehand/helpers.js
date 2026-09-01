import { Stagehand } from "@browserbasehq/stagehand";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export const BASE_URL = process.env.BASE_URL || "http://localhost:8001";
export const FAILURES_DIR = path.join(__dirname, "failures");

if (!fs.existsSync(FAILURES_DIR)) {
  fs.mkdirSync(FAILURES_DIR, { recursive: true });
}

/**
 * Create + init a single Stagehand instance to be reused across an entire
 * script run (many navigations / actors) instead of relaunching a browser
 * per page — much faster, and it's the pattern validated in smoke.js.
 */
export async function createStagehand() {
  const stagehand = new Stagehand({
    env: "LOCAL",
    modelName: "google/gemini-2.5-flash",
    modelClientOptions: { apiKey: process.env.GEMINI_APY_KEY },
    localBrowserLaunchOptions: {
      headless: true,
      args: ["--no-sandbox", "--disable-setuid-sandbox"],
    },
  });
  await stagehand.init();
  return stagehand;
}

/**
 * Plain Playwright login (no .act()). Confirmed selectors from smoke.js /
 * resources/views/auth/login.blade.php.
 */
export async function login(page, email, password) {
  await page.goto(BASE_URL + "/login", { waitUntil: "domcontentloaded" });
  await page.fill("#username", email);
  await page.fill("#password", password);
  const submitBtn = page.locator('form.am-login-form button[type="submit"]');
  await Promise.all([
    page
      .waitForURL((url) => !url.pathname.includes("/login"), { timeout: 15000 })
      .catch((e) => {
        throw new Error(`login for ${email} did not redirect away from /login: ${e.message}`);
      }),
    submitBtn.click(),
  ]);
  await page.waitForLoadState("networkidle", { timeout: 15000 }).catch(() => {});
}

export async function logout(page) {
  await page.goto(BASE_URL + "/logout", { waitUntil: "domcontentloaded" }).catch(() => {});
}

/**
 * Attach console/pageerror collectors to a page ONCE. Returns an object with
 * a `drain()` method that returns everything collected since the last drain
 * (call it right after each navigation you want to check).
 */
export function attachErrorCollectors(page) {
  let consoleErrors = [];
  let pageErrors = [];

  page.on("console", (msg) => {
    if (msg.type() === "error") {
      consoleErrors.push(msg.text());
    }
  });
  page.on("pageerror", (err) => {
    pageErrors.push(err.message || String(err));
  });

  return {
    drain() {
      const result = { consoleErrors, pageErrors };
      consoleErrors = [];
      pageErrors = [];
      return result;
    },
  };
}

const WHOOPS_MARKERS = [
  "Whoops, looks like something went wrong",
  "Symfony\\Component\\Debug",
  "Symfony\\Component\\ErrorHandler",
  "Illuminate\\Database\\QueryException",
  "Server Error",
  "Fatal error:",
  "Stack trace:",
];

/**
 * Navigate to a path, capture HTTP status, detect Laravel exception-page
 * markers in the DOM, and collect JS console/page errors that occurred
 * during the navigation. `collectors` should come from attachErrorCollectors
 * and be reused across many calls against the same page.
 */
export async function checkPage(page, urlPath, { collectors, label } = {}) {
  const url = BASE_URL + "/" + String(urlPath).replace(/^\/+/, "");
  let status = null;
  let navError = null;

  if (collectors) collectors.drain(); // clear anything left over from prior nav

  try {
    const resp = await page.goto(url, { waitUntil: "domcontentloaded", timeout: 20000 });
    status = resp ? resp.status() : null;
    await page.waitForLoadState("networkidle", { timeout: 8000 }).catch(() => {});
  } catch (e) {
    navError = e.message;
  }

  let bodyText = "";
  let whoops = false;
  try {
    bodyText = await page.evaluate(() => document.body ? document.body.innerText.slice(0, 5000) : "");
    whoops = WHOOPS_MARKERS.some((m) => bodyText.includes(m));
  } catch (e) {
    // page might have navigated away / closed; ignore
  }

  const errors = collectors ? collectors.drain() : { consoleErrors: [], pageErrors: [] };

  const isFailure = navError || (status && status >= 500) || whoops;

  const result = {
    label: label || urlPath,
    urlPath,
    status,
    navError,
    whoops,
    consoleErrors: errors.consoleErrors,
    pageErrors: errors.pageErrors,
    isFailure,
  };

  if (isFailure) {
    await saveFailureScreenshot(page, label || urlPath).catch(() => {});
  }

  return result;
}

export async function saveFailureScreenshot(page, label) {
  const safe = String(label).replace(/[^a-z0-9._-]+/gi, "_").slice(0, 120);
  const file = path.join(FAILURES_DIR, `${safe}-${Date.now()}.png`);
  await page.screenshot({ path: file, fullPage: true });
  return file;
}

export function summarize(results) {
  const pass = results.filter((r) => !r.isFailure);
  const fail = results.filter((r) => r.isFailure);
  return { pass, fail, passCount: pass.length, failCount: fail.length };
}
