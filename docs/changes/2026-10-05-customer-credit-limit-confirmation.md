# Customer credit limit check at service payment confirmation

## Request

Check customer credit exposure when a user chooses to keep a service invoice on credit, while preserving the existing direct cash/card receipt flow. Credit exposure counts all outstanding invoices for the customer in the active tenant and organization context.

## Changes

- Added credit exposure details to service payment options using the Invoice module's authoritative outstanding-balance provider.
- Added a server-side credit check at the final “Keep invoice on credit” action. It locks the customer row while re-reading the profile and exposure, then blocks over-limit credit unless `allow_over_credit` is enabled.
- Show open exposure, limit, remaining credit, and threshold/over-limit warnings in the credit payment UI. The server rechecks before accepting the decision; warnings require a second confirmation.
- Left direct cash/card preparation and receipt creation on their existing path. Partial-payment policy enforcement was not added.
- The credit limit has no explicit currency field. The check compares only when open balances have one currency matching the customer's default currency; otherwise it prevents a misleading comparison and asks the user to resolve the currency mismatch.

## Verification

- TypeScript typecheck passed.
- PHP syntax checks passed for changed PHP files.
- `git diff --check` passed.
- Tests were not run.
