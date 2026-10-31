# Exact Invoice credit allocation conservation — 2026-11-01

## Verified root cause and financial impact

`InvoiceBalanceUpdater::recordCreditAllocation()` previously calculated prior allocations via an ORM/database `SUM(allocated_amount)` and then normalized the aggregate by converting it to a PHP string. Depending on the database driver/returned numeric type, this introduces a floating-point or scientific-notation coercion boundary into otherwise exact DECIMAL money. The rest of Invoice uses `DecimalMath` on persisted six-place strings; the cumulative credit history should do the same.

The Invoice updater also checked a positive payment/reversal/credit amount via `DecimalMath::compare(..., scale=6)` before normalizing it. Amounts with a non-zero seventh fractional digit, such as `1.0000001`, could compare as positive but silently lose precision when later added/subtracted at scale six. Purchase's service-facing debit-note allocation similarly compared a raw decimal amount at scale six. The HTTP request validators already enforce six places, but internal service calls must preserve this invariant independently.

## Owner-module implementation

- **Invoice**: Normalize and return the exact positive decimal at entry to payment application, reversal and credit allocation, ensuring unrepresentable fractions are rejected before DB mutations.
- **Invoice**: Sum persisted Invoice credit allocation rows using `cursor()` with `DecimalMath::sum`, not a driver-coerced numeric aggregate. The query selects only `allocated_amount` and remains tenant/source-scoped; no floating-point or overfetch of full model columns.
- **Purchase**: Normalize debit-note allocation amount at its own service boundary before comparison and before calling Invoice. Invoice remains owner of recording the credit and invoice balance, Purchase remains owner of debit-note conservation.

No rounding policy, account mapping, monetary migration, existing allocation rewrite, or cross-module compatibility workaround was introduced.

## Added executable regression coverage

- **Four targeted unit tests** in `tests/Unit/InvoiceBalancePrecisionTest.php` assert that Purchase/Invoice service calls with a nonzero seventh fractional digit fail before interacting with the database.
- **One guarded disposable-MySQL integration test** in `tests/Database/PurchaseReleaseVerificationTest.php` allocates three exact `0.000001` credits against a single Purchase debit note and invoice, checks cumulative prior allocations `0.000000`, `0.000001`, `0.000002`, all remaining balances, and equal Invoice/Purchase credit totals.

**Execution and limitations:** GitHub connector source inspections and exact branch/diff checks were completed. The isolated container has PHP 8.4.24 but no BCMath, Composer, PDO SQLite/MySQL or full local checkout. The new Laravel and MySQL tests have **not** been executed. The user's prior 958-test Windows transcript predates this commit. Until exact-SHA Laravel tests, MySQL, frontend, audit reconciliation and UAT run, the change cannot be certified production ready.

This correctness fix is not equivalent to GL reconciliation or an approved financial posting/reversal policy for Purchase/Selling/Rental notes. PR #127 and issues #124/#128 retain their existing acceptance gates; no Actions, paid service, production database, protected-branch merge or approval bypass was used.
