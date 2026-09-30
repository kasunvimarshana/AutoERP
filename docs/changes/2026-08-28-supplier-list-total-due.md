# Supplier list total due

Date: 2026-08-28

## Purpose

Show each supplier's outstanding invoice balance directly in the Supplier List without changing the existing filters or table columns.

## Changes

- Added an Invoice-owned batch balance query for the suppliers on the current paginated list.
- Calculated totals from positive immutable invoice balance records while excluding draft, reversed, cancelled, and void invoices.
- Preserved tenant and organization-unit isolation in the balance query.
- Grouped outstanding amounts by currency so balances in different currencies are never added together.
- Added a `Total Due` column to the Supplier List and formatted each currency total as money.
- Suppliers without an outstanding balance display zero in their default currency.

## Scope

- Existing Supplier List filters remain unchanged.
- The existing Categories column remains unchanged.
- Supplier lookup and detail responses do not perform the additional list balance query.

## Verification

- Focused Supplier API total-due test passed, including same-currency aggregation, multi-currency separation, and cancelled-invoice exclusion: 1 test, 9 assertions.
- Full Supplier API and Invoice balance-provider regression run passed before the final multi-currency refinement: 12 tests, 151 assertions.
- Frontend TypeScript checking passed.
- Production Vite build passed.
- PHP Pint formatting passed for the changed PHP files.
- Git whitespace validation passed.
