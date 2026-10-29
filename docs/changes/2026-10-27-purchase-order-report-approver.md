# Purchase Order Detail Report approver

## Request

Show who approved a purchase order beside its Approved status in the Purchase Order Detail Report.

## Changes

- Read the PO's existing `approved_by` user through a tenant-matched join and return the approver's full name as a related object in each PO result.
- Show `Approved by [name]` beside the status in the report. Approved or closed POs with no recorded approver show `Not recorded`; other statuses only show the label when an approver exists.
- Include the same approver text in the PO heading used by preview, print, PDF, Excel and CSV exports. No approval records or historical PO data were changed.

## Verification

- `DetailedPurchaseReportTest`: 1 passed, 34 assertions, including approver name, missing approver, HTML, CSV and PDF output.
- Purchase Order Detail Report page tests: 2 passed.
- TypeScript typecheck, targeted ESLint, PHP Pint and `git diff --check` passed.
