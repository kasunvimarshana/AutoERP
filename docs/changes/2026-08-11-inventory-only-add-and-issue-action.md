# Inventory-only Add and issue stock action

Date: 2026-08-11

## Purpose

Prevent the Add & issue stock action from appearing when a combo or package item is selected on a Vehicle Service job line.

## Changes

- Centralized the inventory-item eligibility check using selected item metadata.
- Requires both the derived inventory line source and an actual stockable, non-combo item before showing inventory issue controls.
- Explicitly excludes combo, package, service, labour, and non-stock item types even if inconsistent data marks one as stockable.
- Reuses the same predicate when deriving line source types and filtering unified search results.

## Verification

- Added coverage for ordinary inventory items, stockable combos, and stockable packages.
- Focused Vehicle Service line-item tests passed.
- TypeScript and targeted ESLint passed.
