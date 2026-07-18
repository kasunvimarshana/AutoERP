# Vehicle service line action icon clarity tuning

Date: 2026-07-18

## Problem

The new icon-only edit and remove actions were cleaner than text buttons, but they still felt a little too small and light in the job-line table.

## Change

- increased the vehicle service line action button size from `9x9` to `10x10`;
- increased the edit and remove SVG icon size and stroke weight for clearer visibility;
- kept the same actions, accessibility labels, and combo-child action restrictions unchanged.

## Verification

- `npm run typecheck`

## Scope

This change affects only the frontend vehicle service job-line icon button clarity.
