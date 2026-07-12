# Action menu overlay positioning

Date: 2026-07-12

## Problem

The shared `More actions` dropdown rendered its menu panel in normal document flow. When users opened the menu inside cards or compact action areas, the parent container expanded vertically to fit the dropdown instead of letting it overlay above the surrounding UI.

## Change

- updated the shared `ActionMenu` component to render as an inline positioned anchor;
- moved the dropdown panel to absolute positioning below the trigger with a high stacking order;
- kept the existing close-on-action behavior unchanged.

## Verification

- `npm run typecheck`

## Scope

This is a shared frontend UI fix for all screens using `ActionMenu`. It prevents dropdown expansion from changing parent layout while preserving the current interaction logic.
