# Keep purchase order action and detail redesign mobile-only

## Request

Limit the new purchase order action and line-item design to mobile, preserving the previous desktop web layout.

## Changes

- Restored the desktop View action, Overview-first tab order, Overview default, and full summary details.
- Kept the compact PO card actions, Lines-first default, contextual summary strip, and item progress cards on mobile viewports only.
- Matched the purchase order detail action layout to mobile while keeping the desktop action layout unchanged.

## Verification

- `npm run typecheck` passed after the viewport-only adjustments.
- ESLint passed for the changed TypeScript files.
- `git diff --check` passed.
- Automated tests were not run.
