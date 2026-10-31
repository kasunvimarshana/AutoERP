# Inventory Valuation filters

## Request

Make the existing valuation report's search clearly item-specific, add an optional date range, and mark Status optional.

## Changes

- The report now exposes `Item Search` and searches only item code and item name.
- Added From/To filtering on valuation-layer creation date. Rows still show their current remaining quantities and values. The UI explains that weighted-average layers may contain later receipts.
- Changed Status to an optional dropdown populated from `ValuationLayerStatus`; its blank option includes all statuses.
- Added report metadata for a search label and date-filter hint so the shared filter panel can show report-specific guidance without embedding report keys.

## Verification

- Inspected the generated valuation query: item code/name search, inclusive creation-date bounds, and exact status filter were present.
- PHP Pint, TypeScript typecheck, targeted ESLint, the reporting registry test, and `git diff --check` passed.
