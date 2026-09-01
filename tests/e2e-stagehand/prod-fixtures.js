// Fixtures for QA rounds run directly against LIVE PRODUCTION (https://horizontia.com).
// Unlike fixtures.js (LOCAL env, seeded DB rows with known IDs), production has real user
// data we must never touch — everything here is either a pre-bootstrapped QA account or a
// marker/helper used to label content OUR scripts create so it's trivially greppable and
// safe to bulk-delete later. No hardcoded production course/category/tutor IDs — those are
// discovered at runtime by driving the real UI (see flow-prod-crawl.js's discovery step).

export const QA_MARKER = "QA TEST - ";

export function uniqSuffix() {
  return `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
}

// The 3 accounts bootstrapped once via a one-off admin script (already deleted) ahead of
// this round, per the task brief. Password is shared across all three.
const PROD_PASSWORD = "zoOEW5txQsajiqCF6t0O7t_I";

export const PROD_USERS = {
  admin: { email: "claude-qa-admin@horizontia-qa.test", password: PROD_PASSWORD },
  tutor: { email: "claude-qa-tutor@horizontia-qa.test", password: PROD_PASSWORD },
  student: { email: "claude-qa-student@horizontia-qa.test", password: PROD_PASSWORD },
};
