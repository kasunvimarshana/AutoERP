# Consistent Selling response details after creation and return — 2026-10-11

## Evidence and root cause

The shared `SaleResource` emits warehouse location, item variant, stock batch, serial number and invoice balance from the related models. However, `SalePostingService::create` loaded only a subset of those associations on a first post and a different subset when replaying a completed idempotency key. `SaleReturnPostingService::create` omitted warehouse location and inventory-dimension relations on both the new-return and completed-idempotency paths. After posting a return, the Selling React workspace immediately replaces the displayed sale with this response, so previously visible warehouse location and line dimensions can disappear from the user interface.

## Owned minimal correction

- In `SalePostingService`, eager-load `warehouseLocation`, `lines.variant`, `lines.batch`, `lines.serialNumber` and `invoice.balance` on both first-write and idempotent response paths, matching `SaleResource`'s actual response contract.
- In `SaleReturnPostingService`, load the same sale-detail associations plus `returns.lines` on first-write and idempotent response paths.
- Extend the existing database-backed `SellingWorkflowTest` to assert that each path returns the expected eager-loaded associations.
- No changes to prices, credit calculations, inventory movements, permissions, or financial posting.

## Validation limits

The exact repository source and both idempotent/first-write response paths were inspected before this change. The source and new test assertions were verified through GitHub after the commit. Test execution is still blocked by GitHub issue #124 (all three Actions jobs terminate before executing steps); assertions are authored, not claimed passing. Keep the integration draft unmerged until full Laravel, MySQL, frontend and release validation passes.
