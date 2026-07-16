# Report export truncation warning

Date: 2026-07-16

## Problem

Bounded report exports now exposed row-limit and truncation metadata, but HTML, print, and PDF users still needed a clear visible warning before relying on an incomplete report as a full operational or financial record.

## Correction

The shared report layout now renders an accessible warning when `ReportData::truncated` is true. The warning states the configured row limit and instructs the user to refine filters before treating the output as complete.

Because all general HTML, print, and PDF report templates extend the shared layout, the message is implemented once without duplicating report-specific logic.

CSV and XLSX column structures remain unchanged; their machine-readable truncation state is provided through the response headers documented in the preceding report-export change record.

## Relationships

No production model, schema, or relationship changed.

## Verification

Run:

```bash
git diff --check
php artisan test --filter=ReportExportTruncationMetadataTest
php artisan test --filter=Reporting
php artisan test
composer test:mysql
```
