# Invoice settlement write-boundary conservation — 2026-11-01

## Root cause

`InvoiceBalanceUpdater::mutateBalance()` locked the owning invoice and balance before payment and credit mutations but trusted the stored `remaining_amount` without checking it against the existing invoice face value, paid amount and credit allocations. A historical/imported row with an incorrect remaining amount could allow a new settlement to compound the error, despite subsequent status recalculation.

## Implementation and evidence

The Invoice owner now verifies, under its existing transaction/locks and before any mutation, that `invoice_total - paid_amount - credit_allocated_amount = remaining_amount`, using the same `InvoiceBalanceCalculator` and exact `DecimalMath` as the normal settlement path. A mismatch fails closed with `InvalidArgumentException('Invoice balance is inconsistent with its persisted settlements.')`. No default amounts, FX conversion, historical financial repair or new accounting rules were introduced. This does not change the legitimate overpayment path, because a negative remaining amount is allowed when the balance is internally consistent.

Extended `ManualInvoiceServiceTest` with a real posted manual invoice and Finance posting-plan fixture. The new test simulates isolated historical errors in remaining, paid and credited amounts, asserts that payment application rejects each before mutation, then confirms original invoice totals remain unchanged. `RefreshDatabase` keeps these deliberate direct SQL test changes out of real data.

**Verification boundary:** Test source was committed but the new Laravel test has **not been executed** here because the real Windows checkout, Composer/vendor and database drivers are inaccessible. Do not treat this as a passing MySQL/production result. Existing Purchase tests, all backend/frontend regression suites and historical Finance ledger reconciliation remain required on the exact candidate SHA.

No paid services, GitHub Actions, production writes, protected-branch updates, historic data rewrites or release approval bypass were used.
