# Purchase stock lookup route fix

Date: 2026-10-30

## Problem

The Purchase Order item selector requested `api/v1/items/lookup/purchase-stock`, but the Item API route constrained its optional `kind` parameter to a fixed list that omitted `purchase-stock`. Laravel returned 404 before the lookup controller ran. The previous regression test called the query service directly and did not exercise route matching.

## Change

- Added `purchase-stock` to the Item lookup route's allowed kinds.
- Extended the Purchase integration test to assert that the requested URL resolves to the Item lookup route before testing consumable lookup, Purchase Order creation, and GRN receipt.

## Verification

- Targeted Purchase integration test passed with the route assertion.
- Item lookup appeared in `php artisan route:list`.
- PHP syntax checks and `git diff --check` passed.
