# Vehicle service supervisor preloaded lookup

Date: 2026-07-12

## Problem

The vehicle service job form loaded supervisor options lazily from the server as the user typed into the `Supervisor` field. That added unnecessary lookup requests for a relatively stable employee list and made the field feel heavier than needed.

## Change

- preloaded the available supervisor employee list once when the vehicle service job form mounts;
- kept the existing `GenericLookupSelect` control for consistency, but changed its supervisor search function to filter the preloaded in-memory list locally;
- enabled immediate open/filter behavior for the supervisor field with no remote debounce or per-keystroke API recall;
- preserved the current selected supervisor in the local option pool so edit forms remain stable even if the current value is not in the first fetched batch.

## Verification

- `npm run typecheck`

## Scope

This change is limited to the frontend vehicle service job form supervisor field. It removes unnecessary lazy lookup requests while keeping the existing form structure and selection behavior intact.
