# Purchase debit-note historical currency reconciliation aid — 2026-10-14

## Source and scope

The existing historical financial release blocker is GitHub issue #128. The candidate already implements source-backed currency snapshots and a fail-closed mismatch/unknown-currency gate for **new Purchase debit-note allocations**; see `docs/changes/2026-10-13-purchase-currency-provenance-and-allocation-guard.md`. Neither change retroactively verifies nor repairs existing financial allocations.

This follow-up adds an investigation aid under `docs/audits/purchase-debit-note-currency-reconciliation.sql` and this change record. It contains five read-only investigation queries, not an application migration, posting mechanism, a default-currency backfill, or a production-data mutation. No Invoice, Purchase, Payment, Finance, or Rental application code has been altered.

## Findings captured by the queries

1. Missing `purchase_debit_notes.currency_id`, including historical notes with positive allocated amounts.
2. Missing `purchase_returns.currency_id`.
3. `invoice_credit_allocations` keyed to Purchase debit notes with missing source/invoice, missing or inconsistent supplier/party, wrong invoice direction, missing/mismatched note/invoice currency or organization scope.
4. Debit-note `amount` versus `allocated_amount + remaining_amount` and independent Invoice credit-allocation sum.
5. Source-document lineage Return → Goods Receipt → Purchase Order with currency IDs as **evidence candidates only**.

The investigation explicitly flags null supplier or invoice party identities instead of allowing SQL three-valued comparison to hide invalid rows. A subsequent review found the same three-valued-logic defect in its original organization comparison: a NULL organization on only one side evaluates to SQL UNKNOWN and escaped the anomaly filter. The current SQL checks both NULL/non-NULL asymmetries explicitly and independently compares note organization and allocation organization against the invoice. The result also projects all three organization IDs for manual review. All queries retain the tenant identity in their source joins. Interpret results with responsible Purchase/Invoice/Finance owners; a zero-row result is not evidence that all GL/FX/tax/period/reversal paths reconcile.

## Verification performed and not performed

- Verified the **five SELECT statements** from the same query structure on a disposable **in-memory synthetic SQLite fixture** representing the source, invoice, allocation, and lineage tables. The fixtures included unresolved currency, mismatched currency, null supplier, valid allocation, missing note-level allocation conservation and an unresolved Return→GRN→PO chain. The SQL returned the expected flagged cases, including the previously missed null-supplier condition.
- **Focused follow-up regression on 2026-10-14:** reproduced the original SQL NULL versus non-NULL organization predicate failure using Python `sqlite3` (it returned SQL NULL, not a flagged discrepancy); executed the corrected portable predicate against five disposable SQLite allocation/note/invoice cases: (null note/known invoice), (null allocation/known invoice), (different known organizations), (all equal), and (all null). **5/5 expected classifications passed**. This is a focused predicate regression, **not** a run of the five complete queries against MySQL or populated historical data.
- Confirmed this addition is **read-only SQL plus documentation**; it does not change production application code.
- **NOT executed:** populated MySQL 8.4 copies, actual customer financial rows, Finance journal tie-out, production upgrades, migration rollback, InnoDB contention, security audits, UAT, deployment, or a new exact-head full application suite. The user's newly uploaded local console log confirms a **clean checkout at the earlier documentation commit `17190eef50aceede432d7c36dee774ccaded0a8f`** followed by `php artisan test`: **921 passed / 10,110 assertions**, `npm run typecheck` and `npm run lint`: successful, Vitest **116 files / 432 tests passed**, and production build **707 modules / PASS**. These are user-executed tests, not executions in this environment. The new organization-scope SQL correction occurred **after** that log and has only the focused synthetic regression above; a full suite rerun at the new commit has not been verified.

## Focused supplier-party audit correction (2026-10-14)

A further source contract comparison with `Modules\\Invoice\\Enums\\InvoicePartyType` and `PurchaseDebitNoteService::assertAllocationScope()` found a separate **false-negative audit bug**: when **both** the debit note and the invoice had the *same invalid* `customer` party type, the historical allocation check accepted their equality instead of enforcing that the parties must be `supplier`. Updated **both** the `CASE` classification and its `WHERE` filter in the read-only audit to explicitly require `n.supplier_type = 'supplier'` and `i.party_type = 'supplier'`. This does **not** alter the already fail-closed application allocation service.

**Local focused regression (actually executed using Python's standard-library `sqlite3` in a disposable in-memory database):** 6/6 predicate cases passed, covering valid matching supplier, same-type invalid customer/customer, supplier/customer mismatch, missing note type, different supplier IDs and repeated valid supplier. The previous equality-only predicate's false negative was reproduced. **No populated MySQL data, Finance ledger, full Laravel suite or MySQL integration tests were executed at this later audit commit.**

## Safe next execution path

Only with a properly authorized isolated backup/restored copy: confirm the Purchase upgrade columns exist, run the five SELECTs, preserve anomalies as review evidence (without exposing customer data publicly), reconcile questionable allocations and source snapshots with Invoice/Finance postings, and independently validate fresh and populated MySQL upgrades, rollback and concurrency. No source currency or FX is guessed and no historical monetary data is rewritten. A temporary database for automated MySQL tests must be separately disposable, explicitly named with `_test` per `tests/bootstrap/mysql.php`; do **not** aim automated tests at the restored financial copy.

Issue #124's GitHub runner/account restriction, requested no-paid/no-Actions policy, absence of approving review, and nine unchecked `docs/vehicle-rental/TODO.md` §R release gates remain unaffected. PR #127 must stay draft until all required checks and approvals are met.
