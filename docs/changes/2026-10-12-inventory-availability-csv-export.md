# Export inventory availability as CSV

## Request

Add a CSV export to the Inventory Availability page using its active filters.

## Changes

- Added an authenticated, stock-view-permission protected Inventory endpoint that streams matching stock balance rows without the table pagination limit.
- Reused the Availability query scope and search/warehouse/stock-level filters for the export.
- Exported human-readable item, warehouse, location, unit, and stock details without database IDs; protected text cells against spreadsheet formula execution.
- Added an Export CSV action to the Availability page, including loading and error feedback.

## Verification

- `git diff --check` passed.
- Automated tests were not run.
