# Batch and lot management with batch-specific pricing

Date: 2026-08-30

## Why

Batch- and lot-tracked items needed a complete receiving and Vehicle Service flow where acquisition cost remains tied to the received stock, each positive-stock batch can carry its own selling or service price, and historical price revisions remain auditable. Non-tracked items must continue through the existing simple flow.

## Changes

- Added Inventory-owned batch creation with human-readable batch and lot details, manufacture/expiry dates, validation for batch/lot tracked stock items, and a dedicated manage permission.
- Added immutable, effective-dated batch sales/service price revisions with organization scope, optimistic concurrency, overlap protection, correction history, and an Item Pricing UI.
- Added GRN batch allocations. A receipt line can select existing batches or create batches while receiving, split the accepted quantity across allocations, and post/reverse one inventory movement and valuation layer per allocation using the GRN acquisition cost.
- Preserved the original GRN and stock movement path for `tracking_type = none` items.
- Added Vehicle Service batch options that return one result per non-expired positive-stock batch, resolve the batch service price with item-price fallback, display batch/lot, stock and price, and store the selected batch and price revision on the job line.
- Enabled Vehicle Service inventory availability and issue posting against the selected batch while preserving the authoritative valuation cost returned by Inventory.
- Updated purchase invoice receipt validation to recognize posted batch-allocation inventory movements without placing a compatibility movement on the parent GRN line.
- Added the `Batch Price Revisions` report alongside the existing batch/lot, expiry, valuation, aging, movement, and stock balance reports.

## Verification

- New Inventory batch management integration tests passed: 2 tests, 6 assertions.
- Existing Inventory, Purchase, and Vehicle Service focused tests passed: 37 tests, 182 assertions.
- Inventory permission, API versioning, and Reporting tests passed: 15 tests, 253 assertions.
- TypeScript typecheck passed.
- Focused ESLint checks passed.
- Production frontend build passed.
- PHP syntax checks passed for all changed PHP files.
- Laravel Pint completed for all changed PHP files.
