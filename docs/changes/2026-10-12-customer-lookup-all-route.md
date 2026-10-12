# Fix all-customer lookup route

## Request

Resolve the 404 returned while searching customers in the Customer Outstanding report.

## Changes

- Mapped the `all` customer lookup mode to the existing optional-kind route without an `all` URL segment, matching the Customer module's route constraint.
- Restricted the lookup mode type to the supported customer lookup kinds.

## Verification

- `npm run typecheck` passed.
- `git diff --check` passed.
- Automated tests were not run.
