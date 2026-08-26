# Opening stock CSV import

## Trigger

Inventory opening balances could only be entered one item at a time through the Adjustments form. Initial stock setup therefore became slow and error-prone for larger item catalogues.

## Change

- Added an **Import opening stock CSV** workflow inside Inventory Adjustments while preserving the existing manual adjustment form.
- Added a downloadable CSV template using human-readable item, variant, UOM, batch, and serial codes rather than database IDs.
- Added Inventory-owned template, preview, and draft-import endpoints under `/api/v1/inventory/opening-stock-import`.
- Added a read-only server preview that resolves codes, converts entered UOM quantities and costs to the item base UOM, and returns row-level validation results.
- Added one atomic draft-creation command that revalidates the uploaded file and creates a single `opening_balance` adjustment through the existing `StockAdjustmentService`.
- Kept posting as a separate permission-controlled action through the existing adjustment list.

## Data-integrity rules

- One import targets one selected warehouse and optional location, matching the adjustment aggregate boundary.
- The CSV never accepts raw relationship IDs.
- Only active, stockable items with a configured base UOM are accepted.
- Opening quantity must be positive and unit cost cannot be negative.
- Variant, UOM, batch, and serial references must already exist and belong to the selected item and organization scope; the import does not silently create master or tracking records.
- Serial-tracked quantities remain constrained to one unit per serial by the existing Inventory validator.
- Duplicate item/dimension rows are rejected.
- A row is rejected when the selected stock dimension already has stock; users must use a recount adjustment instead of importing opening stock twice.
- Any invalid row prevents draft creation.
- Preview performs no writes. The existing adjustment posting transaction and system-quantity check remain authoritative if stock changes before posting.

## CSV columns

Required:

- `item_code`
- `opening_quantity`
- `unit_cost`

Optional:

- `variant_code`
- `uom_code`
- `batch_number`
- `serial_number`
- `reason`

## Scope

No schema, stock-balance, valuation, permission, batch/serial creation, or posting implementation was replaced. File size and row limits are configured in the Inventory module.

## Verification

- Opening stock backend tests: 3 tests, 21 assertions passed.
- Existing Inventory workflow tests: 4 tests, 30 assertions passed.
- Focused frontend tests: 2 files, 6 tests passed.
- Full frontend test suite: 78 files, 304 tests passed.
- PHP syntax checks and Laravel Pint passed.
- TypeScript typecheck and full frontend ESLint passed.
- Production Vite build passed.
