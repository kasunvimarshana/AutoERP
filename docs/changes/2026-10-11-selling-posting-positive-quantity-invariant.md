# Selling service enforces positive posted-sale quantities — 2026-10-11

## Verified root cause

`StoreSaleRequest` validates `lines.*.quantity` with `gt:0`, but the owning `SalePostingService::create` can also be called directly by application services and integration tests. Before this correction it normalized quantities only after dimension validation and never independently rejected zero or negative values. The finance/inventory posting transaction therefore relied on HTTP-only validation to enforce a critical stock-issue and invoice business invariant.

The sibling return posting service already asserts positive normalized return quantities. This restores the same owned invariant to sale posting rather than compensating in another module.

## Minimal implementation

- Normalize each sale line's quantity once, directly after validating the stockable item.
- Reject quantities `<= SalePostingService::ZERO` using existing `DecimalMath::compare` and `InvalidArgumentException` before dimension selection, line creation, stock issue, tax or invoice posting.
- Keep the normalized quantity for all subsequent sale and inventory operations; remove the old later duplicate normalization.
- Extend the existing database-backed Selling workflow fixture with direct service calls for zero and negative quantities. Assert the rejection, transaction rollback (no sale or invoice), and unchanged stocked quantity. Preserve successful sale, idempotency, insufficient-stock and return scenarios.

## Verification / release limitation

GitHub source anchors and stored file changes were inspected on candidate `8549b919d40ef387593be5f0bd82f91606c518b7` before creating this commit. No executable PHP/SQLite/MySQL tests ran from this connector session. GitHub Actions CI for this repo still fails before job steps, tracked by issue #124; this note does not claim that the exact candidate has passed the test suite or is production-ready. All financial and Vehicle Rental release gates remain enforced.
