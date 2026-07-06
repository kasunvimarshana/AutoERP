# Hybrid lookup cache architecture

Date: 2026-07-05

## Problem

Lookup dropdowns repeatedly called the backend even for small, slow-changing lists and also did not share previously loaded results across components. This created unnecessary server traffic and slower repeated interactions, especially in vehicle service flows.

## Correction

Introduced a reusable global lookup cache layer backed by Zustand and applied a hybrid strategy:

- `local-cache + frontend filter` for small, stable lookup datasets;
- `server search + shared query cache` for large or dynamic lookup datasets.

The cache is scoped by session context and tenant, and it clears on auth-session invalidation.

### Local cached lookups

These now load once and filter in the browser after the first fetch:

- UOM
- currencies
- customer categories
- item categories
- item brands
- vehicle makes
- vehicle types
- vehicle categories

### Server-driven lookups with shared cached query results

These still search on the backend, but identical queries are now reused across components:

- customers
- vehicles
- items
- stockable items
- service items
- labour items
- combo/package item lookups
- vehicle models by make
- service-available vehicles
- available employees
- warehouses
- warehouse locations by warehouse
- suppliers

The shared cache is wired at the lookup API layer so existing components benefit without rewriting each dropdown.

## Verification

- `npm run typecheck`
- `npm run lint -- resources/js/shared/api/lookupCache.ts resources/js/shared/api/lookupCache.test.ts resources/js/shared/state/lookupCacheStore.ts resources/js/shared/api/referenceApi.ts resources/js/shared/api/lookupApi.ts resources/js/modules/customer/customerApi.ts resources/js/modules/vehicle/vehicleApi.ts resources/js/modules/item/itemApi.ts resources/js/modules/uom/uomApi.ts`
- `npx vitest run resources/js/shared/api/lookupCache.test.ts --reporter=dot --silent`
