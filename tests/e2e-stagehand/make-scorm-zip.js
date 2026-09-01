// Builds a minimal-but-valid SCORM 1.2-style zip fixture for E2E upload testing.
// Uses the system `zip` binary (no extra npm dependency needed) via child_process.
// Matches what Modules/Courses/Services/ScormService.php::processScormZip expects:
// an imsmanifest.xml at the zip root declaring one organization/item/resource,
// whose <resource href="..."> points at a real file also present in the zip.
import { execFileSync } from "child_process";
import fs from "fs";
import os from "os";
import path from "path";

export function buildScormZip(outPath) {
  const tmpDir = fs.mkdtempSync(path.join(os.tmpdir(), "qa-scorm-"));

  const manifest = `<?xml version="1.0" standalone="no" ?>
<manifest identifier="QA_SCORM_COURSE" version="1"
  xmlns="http://www.imsproject.org/xsd/imscp_rootv1p1p2"
  xmlns:adlcp="http://www.adlnet.org/xsd/adlcp_rootv1p2">
  <organizations default="ORG1">
    <organization identifier="ORG1">
      <title>QA SCORM Lesson</title>
      <item identifier="ITEM1" identifierref="RES1">
        <title>QA SCORM Lesson</title>
      </item>
    </organization>
  </organizations>
  <resources>
    <resource identifier="RES1" type="webcontent" adlcp:scormtype="sco" href="index.html">
      <file href="index.html" />
    </resource>
  </resources>
</manifest>
`;

  const indexHtml = `<!doctype html>
<html><head><title>QA SCORM Lesson</title></head>
<body><h1>QA SCORM fixture content</h1><p>Built for automated E2E upload testing.</p></body>
</html>
`;

  fs.writeFileSync(path.join(tmpDir, "imsmanifest.xml"), manifest);
  fs.writeFileSync(path.join(tmpDir, "index.html"), indexHtml);

  fs.mkdirSync(path.dirname(outPath), { recursive: true });
  if (fs.existsSync(outPath)) fs.unlinkSync(outPath);

  execFileSync("zip", ["-j", outPath, path.join(tmpDir, "imsmanifest.xml"), path.join(tmpDir, "index.html")]);

  fs.rmSync(tmpDir, { recursive: true, force: true });
  return outPath;
}

// Allow running directly: `node make-scorm-zip.js`
if (import.meta.url === `file://${process.argv[1]}`) {
  const out = path.join(path.dirname(new URL(import.meta.url).pathname), "fixtures", "qa-scorm-package.zip");
  buildScormZip(out);
  console.log("Built SCORM fixture at", out);
}
