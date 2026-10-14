# Distinguish vehicle service workforce assignment states

## Request

Use color to make each labor item and its employee assignment state easier to distinguish at a glance.

## Changes

- Added a subtle blue tint and left accent to each labor item row, with a stronger highlight while its employee picker is open.
- Matched the employee picker panel header to the active labor item with a light blue treatment.
- Kept existing assignments on neutral white rows and highlighted unsaved employee selections in amber.
- Preserved the picker interaction and batch save behavior.

## Verification

- `git diff --check` passed.
- `npm run typecheck` was attempted twice but did not complete during the available run window.
- Automated tests were not run.
