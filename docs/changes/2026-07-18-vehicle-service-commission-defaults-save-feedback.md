# Vehicle service commission defaults save feedback

Date: 2026-07-18

## Problem

On the vehicle service jobs page, saving commission defaults updated the data but left the commission settings panel open without clear success feedback. That made it harder for users to know the save was completed and left the toggle button in the expanded state.

## Change

- added success-toast feedback after commission defaults are saved;
- wired the commission settings panel save callback back to the jobs page;
- automatically hides the commission settings panel after a successful save, which also resets the `Commission defaults` button state and label.

## Verification

- `npm run typecheck`

## Scope

This change affects only the frontend vehicle service commission-defaults save UX.
