# Fix customer credit profile create and update flow

## Request

Allow the Credit Profile tab to create profiles for imported customers without a profile, update existing profiles safely, and avoid reusing another customer's form state.

## Changes

- Make `row_version` optional when creating a missing profile and retain strict version checks when updating an existing one.
- Lock the customer row during profile creation/update so concurrent first-time saves serialize before checking the unique customer profile.
- Reset the tab form whenever the customer changes, and show when no profile has been saved yet.
- Default a missing profile to credit disabled so saving an imported customer does not grant credit implicitly.

## Verification

- TypeScript typecheck passed.
- PHP syntax checks passed for the changed request and service.
- Invoice credit exposure enforcement remains pending clarification of which invoices count as credit exposure.
- Partial-payment policy enforcement was excluded as requested.
