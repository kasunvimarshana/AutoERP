# Vehicle service job list priority pill positioning

Date: 2026-07-19

## Problem

The priority count pills on the vehicle service job status tabs were visible, but they still read as inline labels instead of true notification bubbles.

## Change

- repositioned the `Draft`, `Inspected`, and `In progress` count pills to the top-right of the tab label;
- added a subtle shadow so the pill reads more like a notification badge;
- kept the existing count logic, cap behavior, and status color theme unchanged.

## Verification

- `npm run typecheck`

## Scope

This change affects only the frontend presentation of the vehicle service job list priority pills.
