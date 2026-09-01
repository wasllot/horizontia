import { createStagehand, BASE_URL } from "./helpers.js";

const URL = `${BASE_URL}/tutor/claude-qa-tutor`;

async function main() {
  const stagehand = await createStagehand();
  const page = stagehand.page;

  const consoleErrors = [];
  const pageErrors = [];
  const failedRequests = [];
  page.on("console", (msg) => {
    if (msg.type() === "error") consoleErrors.push(msg.text());
  });
  page.on("pageerror", (err) => pageErrors.push(err.message || String(err)));
  page.on("requestfailed", (req) => failedRequests.push(`${req.url()} :: ${req.failure()?.errorText}`));

  await page.goto(URL, { waitUntil: "networkidle", timeout: 30000 });
  await page.waitForTimeout(2500);

  console.log("Console errors:", JSON.stringify(consoleErrors, null, 2));
  console.log("Page errors:", JSON.stringify(pageErrors, null, 2));
  console.log("Failed requests:", JSON.stringify(failedRequests, null, 2));

  const jqueryLoaded = await page.evaluate(() => typeof window.jQuery !== "undefined");
  const select2Loaded = await page.evaluate(() => typeof window.jQuery?.fn?.select2 !== "undefined");
  const selectIsSelect2 = await page.evaluate(() => {
    const el = document.querySelector("#timezone");
    return {
      exists: !!el,
      classes: el ? el.className : null,
      nextSiblingIsSelect2Container: el ? (el.nextElementSibling?.className || null) : null,
    };
  });
  console.log("jQuery loaded:", jqueryLoaded);
  console.log("select2 plugin loaded:", select2Loaded);
  console.log("select#timezone state:", JSON.stringify(selectIsSelect2, null, 2));

  await stagehand.close();
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
