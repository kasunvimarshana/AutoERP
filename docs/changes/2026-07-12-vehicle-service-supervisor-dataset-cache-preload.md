# Vehicle service supervisor dataset cache preload

Date: 2026-07-12

## Problem

The vehicle service job supervisor field was still tied to per-form lookup loading behavior. Even after moving filtering into the frontend, the form could still trigger employee lookup traffic during interaction because the preload path was not using the shared local dataset cache foundation.

## Change

- added a reusable `prefetchLocallyFilteredLookupDataset` helper to the shared lookup cache layer;
- split available-employee lookup loading into a shared raw loader, a query-cached lookup loader, a locally filtered dataset loader, and an explicit preload helper in `lookupApi`;
- updated the vehicle service job supervisor field to preload the available-employee dataset on mount and use the shared locally filtered loader for dropdown searching;
- removed the form-specific supervisor merge/filter preload implementation so the behavior stays consistent with the shared lookup architecture.

## Verification

- `npm run typecheck`

## Scope

This change only adjusts the frontend lookup-loading path for the vehicle service job supervisor field and the shared lookup cache utilities it depends on.
