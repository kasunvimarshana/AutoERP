# Prevent duplicate sale lines within a single return — 2026-10-11

## Verified gap

`StoreSaleReturnRequest` requires distinct `lines.*.sale_line_id`, but direct callers of `SaleReturnPostingService::create` were not subject to that validation. For example, a sale of two units could be returned as two entries of the same line (one unit and half a unit) on one return. Previously, both entries could post separate credit and inventory movements because total quantity was still within the remaining quantity. This violates the API contract and fragments the one-to-one return-line source identity.

## Owned correction

The return posting service now verifies positive, valid integer sale line identifiers and rejects duplicate IDs before opening the posting transaction or acquiring an idempotency key. This retains its existing quantity, tenant, sale-owner, period, stock and invoice validation without duplicating rules into other modules. An additional direct-service regression case supplies two entries for the same sale line, with a combined quantity below the sold quantity, asserting rejection and no return rows, credit rows or stock change.

## Verification boundary

Source and assertions were reviewed against the current GitHub integration candidate. This is not an executed Laravel test. GitHub Actions is currently failing all backend/frontend jobs before any steps execute (issue #124); the release cannot be considered validated or merged until executable tests and business acceptance gates pass.
