# Consumable Usage report

Date: 2026-10-30

## Request

Add a separate, useful report for posted consumable usage that users can find from Reports, instead of relying on the history section beneath the posting form.

## Changes

- Added a Consumable Usage Report page with date range and item filters, item-wise used quantity and valuation cost, total expense cost in base currency, and paginated usage entries with reversal status.
- Reused the Inventory-owned Consumable Usage query and summary. No new transactional schema, posting rule, or calculation was introduced.
- Added the report to the Reports navigation and All Reports list, plus a direct link from the Consumable Usage screen for users with Reporting access.
- Added a specific route entitlement requiring Reporting, Finance, Inventory, and Consumable Usage view access. Added a navigation check for permitted and missing-Inventory contexts.

## Verification

- TypeScript typecheck passed.
- The new navigation check passed. The full navigation test file still has three pre-existing failures caused by stale expected Reporting and Stock Card child lists.
- `git diff --check` passed.
