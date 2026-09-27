# Selling module

## Scope

Added a Selling module for direct sales of stocked items. Sales require an active customer and warehouse, use the current configured sales price and tax rules, support item variants, batches/lots, and serial selection through inventory lookups, issue stock through Inventory, and create and post an outbound customer invoice in the same transaction. A due date records a credit sale; invoice balance and subsequent payments remain owned by Invoice and Payments.

Returns are recorded as append-only credit notes against a posted sale. Returned stock is received through Inventory at the original cost snapshot. A credit note is applied against the original invoice balance, with any remaining credit retained as unapplied credit. Sale and return writes use idempotency, row-version checks, locked records, and database transactions.

## Ownership and integration

- Selling owns sale and return documents and their immutable line snapshots.
- Inventory remains responsible for stock validation, movements, quantities, and valuation.
- Invoice remains responsible for posted invoice records, balances, print rendering, and finance posting. The Sales invoice uses the focused print layout already used by Job Service.
- Customer and warehouse selection uses existing lookup APIs; UI displays names and document references.
- Registered the Selling provider, feature, navigation, routes, and permissions.

## Verification

- Selling workflow: passed (17 assertions), including stock posting, invoice creation, duplicate request handling, rollback on insufficient stock, return, and credit application.
- Fast Purchase workflow: passed (25 tests, 144 assertions).
- Job Service invoice workflow: passed (13 tests, 124 assertions).
- Frontend typecheck, ESLint, Pint, and migration pretend completed successfully.

## Notes

No previous change record was modified.
