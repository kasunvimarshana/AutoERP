# Action menu outside-click close

Date: 2026-07-12

## Problem

After the shared `ActionMenu` was moved to overlay positioning, the dropdown could remain open until the user clicked one of its actions or toggled the summary again. That felt sticky and did not match expected overlay-menu behavior.

## Change

- added a shared outside-click listener to `ActionMenu`;
- when the menu is open and the user clicks anywhere outside the menu, the dropdown now closes immediately;
- kept the existing close-on-action behavior unchanged.

## Verification

- `npm run typecheck`

## Scope

This is a shared frontend interaction improvement for every screen using `ActionMenu`. It only affects menu-dismiss behavior and does not change the available actions or their handlers.
