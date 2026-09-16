# Inventory Availability Overview

Date: 2026-09-16

## Summary

Replaced the Inventory Availability tab's single-item lookup workflow with a fast, filterable stock-health overview.

## Changes

- Added global stock-line summary cards for all, in-stock, low-stock, and out-of-stock balances.
- Added item name/code/SKU search, warehouse selection, and stock-level filtering.
- Added a responsive availability table showing human-readable item and location details, available quantity with its base unit, stock status, reorder point, on-hand quantity, and reserved quantity.
- Classified stock levels in the Inventory backend from authoritative available quantities and Item-owned reorder levels.
- Added a typed stock-level enum and validated the stock-level API filter.
- Returned filtered summary counts independently of pagination and ordered the most urgent balances first.

## Stock-level rules

- Out of stock: available quantity is zero or below.
- Low stock: available quantity is above zero and at or below the configured item reorder level.
- In stock: available quantity is above zero and either exceeds the reorder level or has no reorder level configured.

## Verification

- Focused Inventory movement tests passed: 11 tests, 36 assertions.
- ESLint passed for all changed Inventory frontend files.
- PHP Pint passed for all changed Inventory backend files.
- PHP syntax checks passed.
- Production Vite build passed with 667 modules transformed.
- git diff --check passed before this change record was added.
- Full TypeScript verification reports only the pre-existing optional `result.meta` issue in `VehicleServiceHistoryReportPage.tsx`.
