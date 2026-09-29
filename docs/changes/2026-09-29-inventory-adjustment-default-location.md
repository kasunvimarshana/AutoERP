# Inventory adjustment default warehouse and location

## Request

Preselect the warehouse and location marked in the stock adjustment form.

## Changes

- Resolve the configured default warehouse and its configured default location through the Warehouse module APIs.
- Preserve manual user selections when asynchronous defaults load or the selected warehouse changes.
- Surface lookup failures through the adjustment form's existing error feedback.

## Verification

- TypeScript typecheck passed (`npm run typecheck`).
- `git diff --check` passed.
