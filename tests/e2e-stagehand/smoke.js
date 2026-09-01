import { Stagehand } from "@browserbasehq/stagehand";
import { z } from "zod";

const BASE_URL = process.env.BASE_URL || "http://localhost:8001";

async function main() {
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
  const page = stagehand.page;

  const resp = await page.goto(BASE_URL + "/", { waitUntil: "domcontentloaded" });
  console.log("GET / status:", resp.status());
  console.log("title:", await page.title());

  await page.goto(BASE_URL + "/login", { waitUntil: "domcontentloaded" });
  await page.fill("#username", "qa-admin@horizontia.test");
  await page.fill("#password", "Password123!");
  const submitBtn = page.locator('form.am-login-form button[type="submit"]');
  console.log("submit button count:", await submitBtn.count());
  await Promise.all([
    page.waitForURL((url) => !url.pathname.includes("/login"), { timeout: 15000 }).catch((e) => console.log("waitForURL error:", e.message)),
    submitBtn.click(),
  ]);
  await page.waitForLoadState("networkidle", { timeout: 15000 }).catch(() => {});
  console.log("after login url:", page.url());

  const ExtractSchema = z.object({
    loggedIn: z.boolean(),
    heading: z.string(),
  });
  const result = await page.extract({
    instruction: "Does this page show a logged-in admin area (yes/no) and what is the page heading?",
    schema: ExtractSchema,
  });
  console.log("extract result:", result);

  await stagehand.close();
}

main().catch(async (err) => {
  console.error("SMOKE TEST FAILED:", err);
  process.exit(1);
});
