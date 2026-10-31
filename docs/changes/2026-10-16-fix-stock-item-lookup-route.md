# Fix stock item lookup route constraint

## Request

Fix the 404 returned when searching stock items in the Purchase Order item selector.

## Root cause

The Item module's `items/lookup/{kind?}` route constrained `kind` to an explicit allowlist that did not include `stock`, although the new stock-only lookup requested `/items/lookup/stock` and the lookup service supported that kind.

## Changes

- Added `stock` to the route's allowed lookup kinds so the stock-only query reaches `ItemController` and `ItemQueryService`.

## Verification

- PHP syntax check passed for the Item API routes file.
- Route registration and `git diff --check` passed.
