# Item module local mutation state batch 1

Date: 2026-07-12

## Problem

The item module still had several frontend workflows that reloaded whole collections after small mutations. This was most visible in the item list, brand list, category list, and the shared relation CRUD hook used by item-detail sub-features such as units, variants, bundles, prices, codes, and usage rules.

## Change

- updated `ItemListPage` to keep the current paginated collection in local state and patch item activate/deactivate responses in place;
- updated `ItemBrandListPage` and `ItemCategoryListPage` to replace mutation-driven list reloads with local collection updates for activate/deactivate and delete actions;
- made brand/category list updates filter-aware so rows are removed locally when their active state no longer matches the current filter;
- updated the shared `useItemRelationCrud` hook to stop calling `reload()` after create, update, and delete;
- the shared hook now keeps its current relation collection locally, upserts saved records in place, prepends new records on page 1, and removes deleted rows locally.

## Verification

- `npm run typecheck`

## Scope

This batch is limited to frontend item-module collection workflows. It covers the main item list, brand list, category list, and item relation tabs powered by `useItemRelationCrud`, reducing unnecessary API recalls while preserving current backend contracts and pagination/filter behavior.
