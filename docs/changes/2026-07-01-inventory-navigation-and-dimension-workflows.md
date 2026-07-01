# Inventory navigation and dimension workflows

Date: 2026-07-01

## Problem

The Inventory workspace route and route entitlement existed, but the tenant sidebar did not expose an Inventory entry. Users with the Inventory module enabled could reach `/inventory` only by direct URL.

Inventory workflow forms also hid stock dimensions that the backend already validates and persists, including item variants, warehouse locations, batches/lots, serial numbers, and entered UOMs. This made direct Inventory operations incomplete for dimensioned stock and forced users into base item/warehouse-only workflows.

## Correction

Added an Inventory module entry under Operations navigation and pinned the exact `/inventory` route entitlement with tests.

Added Inventory-owned lookup adapters for active batches/lots and available serial numbers, returning human-readable lookup labels instead of raw IDs.

Added a shared Inventory optional-dimensions form section and wired the existing reservation, allocation, transfer, stock count, adjustment, and availability workflows to submit the supported backend dimension fields. Transfer source and destination locations remain header-level fields to match the backend transfer contract.

## Verification

- `php artisan test app/Modules/Inventory/Tests --stop-on-failure`
- `npx vitest run resources/js/modules/inventory/inventoryApi.test.ts resources/js/app/navigation/navigationUtils.test.ts resources/js/app/access/resolvedRouteEntitlements.test.ts --reporter=dot --silent=true`
- `npm run typecheck`
- `npm run lint`
