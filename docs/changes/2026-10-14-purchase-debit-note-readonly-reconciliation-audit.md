# Purchase debit-note historical currency reconciliation aid — 2026-10-14

## Source and scope

The existing historical financial release blocker is GitHub issue #128. The candidate already implements source-backed currency snapshots and a fail-closed mismatch/unknown-currency gate for **new Purchase debit-note allocations**; see `docs/changes/2026-10-13-purchase-currency-provenance-and-allocation-guard.md`. Neither change retroactively verifies nor repairs existing financial allocations.

This follow-up adds **only** `docs/audits/purchase-debit-note-currency-reconciliation.sql` to version control. It contains five read-only investigation queries, not an application migration, posting mechanism, a default-currency backfill, or a production-data mutation. No Invoice, Purchase, Payment, Finance, or Rental application code has been altered.

## Findings captured by the queries

1. Missing `purchase_debit_notes.currency_id`, including historical notes with positive allocated amounts.
2. Missing `purchase_returns.currency_id`.
3. `invoice_credit_allocations` keyed to Purchase debit notes with missing source/invoice, missing or inconsistent supplier/party, wrong invoice direction, missing/mismatched note/invoice currency or organization scope.
4. Debit-note `amount` versus `allocated_amount + remaining_amount` and independent Invoice credit-allocation sum.
5. Source-document lineage Return → Goods Receipt → Purchase Order with currency IDs as **evidence candidates only**.

The investigation now explicitly flags null supplier or invoice party identities instead of allowing SQL three-valued comparison to hide invalid rows. All queries retain the tenant identity in their source joins. Interpret results with responsible Purchase/Invoice/Finance owners; a zero-row result is not evidence that all GL/FX/tax/period/reversal paths reconcile.

## Verification performed and not performed

- Verified the **five SELECT statements** from the same query structure on a disposable **in-memory synthetic SQLite fixture** representing the source, invoice, allocation, and lineage tables. The fixtures included unresolved currency, mismatched currency, null supplier, valid allocation, missing note-level allocation conservation and an unresolved Return→GRN→PO chain. The SQL returned the expected flagged cases, including the previously missed null-supplier condition.
- Confirmed this addition is **read-only SQL plus documentation**; it does not change production application code.
- **NOT executed:** populated MySQL 8.4 copies, actual customer financial rows, Finance journal tie-out, production upgrades, migration rollback, InnoDB contention, security audits, UAT, deployment, or a new exact-head full application suite. Previously supplied user log for `588c10303dc47d3510425269ebb25fe48c2538d7` verified 921 Laravel tests / 10,110 assertions, 432 Vitest, lint, typecheck and build on the **previous commit**, not the new documentation commit.

## Safe next execution path

Only with a properly authorized isolated backup/restored copy: confirm the Purchase upgrade columns exist, run the five SELECTs, preserve anomalies as review evidence (without exposing customer data publicly), reconcile questionable allocations and source snapshots with Invoice/Finance postings, and independently validate fresh and populated MySQL upgrades, rollback and concurrency. No source currency or FX is guessed and no historical monetary data is rewritten. A temporary database for automated MySQL tests must be separately disposable, explicitly named with `_test` per `tests/bootstrap/mysql.php`; do **not** aim automated tests at the restored financial copy.

Issue #124's GitHub runner/account restriction, requested no-paid/no-Actions policy, absence of approving review, and nine unchecked `docs/vehicle-rental/TODO.md` §R release gates remain unaffected. PR #127 must stay draft until all required checks and approvals are met.
