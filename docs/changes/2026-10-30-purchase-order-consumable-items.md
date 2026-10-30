# Purchase orders accept stockable consumables

Date: 2026-10-30

## Problem

The Purchase Order line lookup returned only Stock items, and Purchase Order and GRN validation rejected Consumable items. This prevented stockable consumables from entering the existing purchase and receipt flow used by Consumable Usage.

## Changes

- Added a purchase-specific item lookup that returns active, stockable Stock and Consumable items within the existing tenant and organization scope. The existing Stock-only lookup keeps its meaning.
- Pointed the Purchase line selector at the purchase-specific lookup.
- Updated Purchase Order and GRN validation to accept stockable Stock and Consumable items, while continuing to reject other item types and non-stockable items.
- Added a regression test that searches for a consumable, creates a Purchase Order, receives it through a GRN, and verifies the resulting stock.

## Verification

- Targeted Purchase integration test passed.
- TypeScript typecheck passed.
- PHP syntax checks and `git diff --check` passed.

No database schema or existing records were changed.
