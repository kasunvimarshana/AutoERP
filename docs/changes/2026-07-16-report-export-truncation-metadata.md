# Report export truncation metadata

Date: 2026-07-16

## Problem

General report previews and exports were intentionally bounded by `reporting.export_row_limit`, but the service requested exactly that many rows. It could not distinguish a complete result containing exactly the limit from a larger result that had been truncated.

Users and API consumers could therefore receive an incomplete export without machine-readable truncation evidence.

## Correction

General report document generation now:

1. loads `configured limit + 1` rows;
2. detects whether more rows exist;
3. removes the probe row before rendering;
4. stores the configured row limit and truncation state in immutable `ReportData`;
5. exposes that metadata to report views;
6. returns named response headers for bounded exports:
   - `X-AutoERP-Report-Row-Limit`
   - `X-AutoERP-Report-Truncated`

Specialized report services that provide an intentionally complete row set do not claim a row limit.

This change keeps current synchronous exports bounded. A queued large-export workflow remains a separate scalability feature rather than being guessed into this fix.

## Relationships

No schema or model relationship changed.

## Verification

Run:

```bash
git diff --check
php artisan test --filter=ReportExportTruncationMetadataTest
php artisan test --filter=Reporting
php artisan test
composer test:mysql
```
