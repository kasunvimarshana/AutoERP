# Item bundle child lookup labour-only filter

Date: 2026-07-19

## Problem

In the item create flow, when the user selected the item type as `combo` and opened the `Bundles` tab, the child-item lookup loaded all items. For combo bundles, that made the picker noisy and allowed selecting non-labour items when the intended workflow was labour-only bundle lines.

## Change

- extended the shared `ItemLookupSelect` component so callers can request a specific item lookup kind;
- updated item bundle child-item selectors to use the `labour` lookup kind;
- applied the same labour-only filter in both the one-shot item create bundle builder and the bundle relation drawer so create and edit flows stay consistent.

## Verification

- `npm run typecheck`

## Scope

This change affects only the frontend item bundle child-item lookup behavior.
