# Vehicle service combo pack line grouping UI

Date: 2026-07-18

## Problem

Vehicle service combo packs were rendered as flat job-line rows, so included child items looked like ordinary peer lines instead of being visually grouped under the parent combo pack. That made bundled services harder to understand and weakened the intended marketing-style package presentation.

## Change

- kept the existing frontend line state and API behavior unchanged;
- added a presentation-layer display model for vehicle service job lines so combo parents and combo children can render differently;
- updated the job-lines table to show combo parents as bundle headers with a `Combo pack` badge and included-item count;
- updated combo child rows to render indented under the parent with connector styling and an `Included` badge;
- softened child-row price and total display so bundled child pricing reads as included beneath the main combo pack instead of looking like a separate top-level sellable line;
- extended the shared `DataTable` component with an optional `rowClassName` hook so row-level styling can be applied cleanly.

## Verification

- `npm run typecheck`

## Scope

This change affects the frontend vehicle service job-lines table and the shared table styling hook it uses.
