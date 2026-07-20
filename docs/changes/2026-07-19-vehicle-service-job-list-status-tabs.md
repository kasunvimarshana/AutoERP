# Vehicle service job list status tabs

Date: 2026-07-19

## Problem

When many vehicle service jobs shared the same status, users had to scroll through the full list to find the next relevant group, such as additional `inspected` jobs further down the page.

## Change

- reviewed the existing vehicle service job list API and confirmed it already supports server-side `status` filtering;
- added a tab-style status switcher to the vehicle service job list page with quick access to `All`, `Inspected`, `Draft`, `In progress`, `Completed`, `Invoiced`, `Partially paid`, `Paid`, and `Cancelled`;
- kept the existing pagination behavior unchanged by reusing the current list API filter instead of creating a separate client-side grouping flow;
- synchronized the selected status with the page query string so filtered list views remain linkable and reload cleanly.

## Verification

- `npm run typecheck`

## Scope

This change affects only the frontend vehicle service job list filtering experience.
