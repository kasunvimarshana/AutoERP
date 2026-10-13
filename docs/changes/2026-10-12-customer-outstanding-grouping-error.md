# Fix customer outstanding report grouping error

## Request

Resolve the server error shown when opening the Customer Outstanding report.

## Changes

- Grouped customer outstanding aggregates directly by the invoice currency column so MySQL `ONLY_FULL_GROUP_BY` accepts the query.
- Avoided showing the no-results empty state when the report request fails.

## Verification

- PHP syntax check passed for the report service.
- `npm run typecheck` passed.
- `git diff --check` passed.
- Automated tests were not run.
