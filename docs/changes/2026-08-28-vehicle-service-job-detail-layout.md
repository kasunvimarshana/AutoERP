# Vehicle service job detail layout

Date: 2026-08-28

## Why

The service-job detail workspace used a narrow side column for duplicate workshop status information and quick actions, reducing the space available for the primary job workflow.

## Changes

- Removed the Workshop status side panel from the vehicle-service job detail page.
- Expanded the tabbed job workspace to the full available content width.
- Moved Quick actions below the tabbed workspace into a full-width horizontal panel matching the Current status panel styling.
- Preserved every existing quick action, status condition, navigation target, and backend interaction unchanged.
- Kept the shared entity-detail layout unchanged so other modules retain their current layouts.

## Verification

- `.\node_modules\.bin\tsc --noEmit`
- `npm run build`
- `git diff --check`
- The focused vehicle-service test run completed with 8 passing tests and 2 unrelated existing stale assertion failures concerning inventory row-version calculation and local total recalculation.

