# Vehicle list focused filters

Date: 2026-08-28

## Why

The Vehicle List filter area contained more controls than the client needs for the primary search workflow.

## Changes

- Kept only the general Search field and Customer selector on the Vehicle List.
- Removed the Status, Make, Model, Type, and Category filter controls and their unused page state/request parameters.
- Simplified filter clearing and status-row refresh logic to match the remaining filters.
- Kept the backend filtering capabilities unchanged for other consumers.

## Verification

- `.\node_modules\.bin\tsc --noEmit`
- `npm run build`
- `git diff --check`
