# GRN receivable lines accepted amount column

Date: 2026-07-22

## Problem

The Goods Receipt create page showed accepted quantities and unit prices in the `Receivable lines` table, but it did not show the resulting accepted line amount. Users had to calculate `unit price * accepted quantity` mentally.

## Change

- added an `Accepted amount` column to the GRN receivable lines table;
- compute the value on the frontend using the shared decimal multiplication utility from the line’s `accepted_quantity` and `unit_price`;
- render the new amount with the shared `MoneyDisplay` component using the selected purchase order currency when available;
- included the same accepted amount in the mobile row details for consistency;
- added a purchase flow regression assertion that checks the column exists and updates to the expected amount after `Receive All Remaining`.

## Verification

- `npm run typecheck`
- attempted: `npx vitest run resources/js/modules/purchase/PurchaseSourceCreateFlows.test.tsx`

## Notes

The focused Vitest command is currently blocked by an existing React Router ESM/CommonJS test-environment issue in this repository (`Cannot use import statement outside a module`), so the UI-specific assertion could not be executed in this environment even though the TypeScript build passes.

## Scope

This change affects only the frontend Goods Receipt create experience and its related purchase create-flow test coverage.
