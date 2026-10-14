# Rank vehicle service job item search results by relevance

## Request

When a user searches for an item such as “wash” while creating or editing a service job, show the closest matches first across stock, service, labour, combo, and package items.

## Changes

- Add a Vehicle Service lookup endpoint that combines supported item and sellable batch options into one globally ranked result list.
- Rank exact matches first, then name/code/SKU/barcode prefixes, then other contains matches, with stable name/code ordering within each group.
- Keep existing item and batch lookup defaults unchanged for other modules; relevance ordering is opt-in for this endpoint.
- Point the service job line picker at the combined endpoint while retaining existing option details and pagination.

## Verification

- Vehicle Service relevance integration test passed.
- Vehicle Service line lookup and line fields frontend tests passed (10 tests).
- TypeScript typecheck passed.
- ESLint passed for the changed lookup and line picker files.
- `git diff --check` passed.
