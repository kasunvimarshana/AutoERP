# Vehicle registration normalized search

Date: 2026-09-13

## Requirement

Allow a user creating a Vehicle Service job to find a formatted vehicle registration such as `ABC-3450` when entering common variants such as:

- `abc 3450`;
- `abc3450`;
- `ABC-3450`.

The stored and displayed registration number must remain in its original human-readable format.

## Root cause

The shared Vehicle lookup previously applied the user's raw search text directly to `registration_number` with a normal `LIKE` comparison. A space in `abc 3450` therefore could not match the hyphen in `ABC-3450`, and removing the separator entirely also produced a different string.

## Implementation

- Updated the Vehicle-owned `matchingSearch` query scope, which is the backend source used by Vehicle Service and other Vehicle lookups.
- Preserved the existing search across vehicle number, code, registration, chassis, engine, and VIN fields.
- Added an extra registration-number comparison that:
  - converts both sides to lowercase;
  - removes ASCII spaces;
  - removes hyphens.
- Kept the original `registration_number` unchanged in storage and API results, so a match still displays as `ABC-3450`.
- Added no frontend-only filtering and no duplicate Vehicle Service search logic.

Only separators and case are ignored. Letters and digits must still match; for example, `abc 3450` does not match `ABC-3806`.

## Scope and integrity

- Tenant, organization-unit, status, availability-for-service, and ownership filters remain unchanged and are applied together with the normalized search.
- The normalized condition uses parameter binding for the search value.
- No schema or data migration is required.
- The same behavior is available to other consumers of the central Vehicle search scope, including the relevant Vehicle Rental lookup.

## Verification

- Focused Vehicle API lookup test: **1 test passed, 38 assertions**.
- Full Vehicle engine suite: **14 tests passed, 150 assertions**.
- Vehicle Rental lookup/boundary suites: **13 tests passed, 118 assertions**.
- Regression fixture `API-1234` was returned for both `api 1234` and `api1234` searches.
- PHP formatter and `git diff --check` passed.

## Deployment

Deploy the backend change normally. No migration, data backfill, or frontend rebuild is required for this fix.
