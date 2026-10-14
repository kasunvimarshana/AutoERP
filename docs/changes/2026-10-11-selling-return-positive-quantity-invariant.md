# Selling return quantity server-side invariant — 2026-10-11

## Context and verified root cause
- The Selling return API's `StoreSaleReturnRequest` validates each returned quantity with `gt:0`.
- However, the owning `SaleReturnPostingService::create` is also invoked directly in the existing Selling test and other possible service callers. It previously checked only that the requested return quantity did not **exceed** the unreturned quantity. A zero or negative value therefore reached the return/credit/stock workflow without an explicit positive-quantity domain assertion. The existing request validation cannot be the service's sole data-integrity boundary.
- The service already normalizes quantities through `DecimalMath` and centralizes `ZERO` as a named constant.

## Minimal change
- Compare normalized quantity against `self::ZERO` inside Selling's existing transactional posting service. Reject zero or negative values with a predictable `InvalidArgumentException` before creating any return line, credit allocation or stock movement.
- Extend `SellingWorkflowTest`'s existing database fixture with zero and negative direct-service return attempts, asserting the specific rejection, zero persisted return records and unchanged inventory balance. Retain existing successful return and idempotency assertions.
- No contracts, relationships, migrations, configuration, release branch or other modules modified; no duplicate validation abstraction introduced.

## Verification and release gate
- Reviewed the exact controller, request validation, posting service, balance and test fixture on the full combined integration candidate; the change reuses existing `DecimalMath` and named zero.
- Source anchor and new regression assertions verified before Git tree write.
- Executable Laravel/DB/MySQL tests were **not run** in this context; CI run #38144367239 previously failed all three jobs before any step. `#124` remains unresolved.
- This improvement is committed only to the draft integrated PR #127. It is **not proof of production readiness**; full tests and financial/UAT release checks remain mandatory.
