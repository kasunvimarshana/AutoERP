# Item bundle child autofill defaults

Date: 2026-07-19

## Problem

After choosing a child item in the item bundle entry flow, users still had to manually fill the related bundle defaults. That added unnecessary steps in the `Bundles` tab even though the selected item already provided enough context to prefill the line.

## Change

- updated the bundle child-item selection flow to auto-fill the bundle quantity, UOM, and line type when a child item is selected;
- reused the selected child item's base UOM as the default bundle UOM;
- mapped the selected child item's item type into the supported bundle line type set;
- applied the same autofill behavior in both the bundle relation drawer and the one-shot item create bundle builder to keep the experience consistent.

## Verification

- `npm run typecheck`

## Scope

This change affects only the frontend autofill behavior inside item bundle entry flows.
