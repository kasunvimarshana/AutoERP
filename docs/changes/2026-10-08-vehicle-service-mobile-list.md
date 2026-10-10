# Improve vehicle service job list on mobile

## Request

Make the vehicle service job list easier to use on mobile while preserving its desktop layout and permission rules.

## Changes

- Added a mobile card layout with the job number as the detail link, status badge, vehicle, customer, date, and total.
- Kept Edit available on mobile only when the user has the existing update permission and the job is in an editable status.
- Replaced the full mobile status tabs with priority status chips and moved the complete status selector and date filters into a collapsible Filters panel.
- Kept the desktop table, status tabs, and filters at the existing `md` breakpoint and above.
- Kept job creation and commission defaults behind their existing permissions; commission defaults are in a mobile More menu.

## Verification

- `npm run typecheck` passed.
- `git diff --check` passed.
- Focused ESLint invocation did not finish during the available run window; automated tests were not run.
