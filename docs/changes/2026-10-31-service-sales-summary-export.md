# Service Job Sales Summary export

Date: 2026-10-31

## Request

Add export options to the Service Job Sales Summary report.

## Changes

- Added the existing permission-aware Preview, Print, PDF, Excel, and CSV controls beside the report's job count and collected total. Exports use the applied date and item filters; controls are hidden while results load.
- Added a Reporting export endpoint protected by the existing report-export permission, with the same request validation, tenant scope, organization scope, and report query as the screen.
- Added export presentation mapping for report totals, ranked stock/combo items, combo components, service jobs, and their item breakdowns. Amounts come directly from the existing report result. Overlapping sections are explicitly labelled and are not automatically summed. The existing configured export row limit rejects oversized exports.
- Exported item filters use readable item names/codes rather than database IDs. No relationships, schemas, business calculations, or historical records changed.
- Removed an unused display helper from the report page found by the targeted lint check.

## Verification

- PHP tests: 3 passed, 76 assertions, including all five export formats, original decimal amounts, readable references, component costs, row limits, and export-route permission middleware.
- Report page test passed: draft filters do not affect exports until applied; loading hides export controls.
- TypeScript typecheck passed.
- Targeted ESLint: zero errors; one pre-existing synchronous effect state-update warning remains in the report's loading logic.
- Pint and `git diff --check` passed.
