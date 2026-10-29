# Goods Receipt Note Summary Report

Added a dedicated Purchase report based on the R7 sample. It lists posted GRNs by organization location with GRN date, supplier, linked PO, the GRN `notes` field, accepted quantity, and accepted quantity × unit price before discounts, tax, or charges. It excludes free quantity, which is not a GRN field in this system.

The report supports receipt date, supplier, warehouse, and text filters. GRN and PO numbers link to their detail pages. Location subtotals and currency grand totals cover the full filtered result even when the screen is paginated. Quantity totals are displayed only when every included line uses the same UOM; mixed UOMs are labelled instead of being summed as a misleading quantity. CSV, HTML preview/print, and PDF exports use the same grouped result.

The implementation is isolated in the Reporting module with a dedicated request, controller, service, React page, and export template. No database migration was needed.

Verification: focused backend report test, frontend page test, TypeScript typecheck, targeted PHP Pint check, targeted ESLint, and `git diff --check`.
