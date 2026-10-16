# Hide item code in stock-only purchase lookup

## Request

Hide item codes from the stock-item search results in the Purchase Order flow.

## Changes

- Configured the stock-only Purchase lookup to display item names without their codes.
- Kept the shared item lookup's existing code-and-name label for other flows.

## Verification

- `npm run typecheck` passed.
- `git diff --check` passed.
