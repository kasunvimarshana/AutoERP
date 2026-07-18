# Vehicle service supervisor lookup preload fallback

Date: 2026-07-12

## Problem

The supervisor field preload optimization could still show `No matching supervisor found` if the user started typing before the initial supervisor list had finished loading. In that warm-up window, the local filter had no data to search yet.

## Change

- added a fallback in the vehicle service job form supervisor lookup;
- while the preloaded supervisor list is still `null`, the field temporarily uses the existing employee lookup API instead of filtering an empty local list;
- once the preload has completed, the field continues using the intended local in-memory filtering behavior.

## Verification

- `npm run typecheck`

## Scope

This change only adjusts the frontend supervisor lookup warm-up behavior in the vehicle service job form. It preserves the preloaded local-filter design while preventing empty-state failures during the initial load window.
