# Historical Purchase credit-note sign and amount conservation audit — 2026-11-01

## Evidence of a blind spot

The existing read-only Purchase financial reconciliation SELECTs correctly checked source currency/supplier/organization, and `amount = allocated_amount + remaining_amount = SUM(invoice_credit_allocations)`. These equalities alone are insufficient: a historical debit note with `amount=30`, `allocated_amount=-5`, `remaining_amount=35`, and one `-5` invoice credit allocation **passes all equalities** while violating the business rule that a posted debit note and each actual allocation have positive amounts. A `0/0/0` note and a negative face-value note could similarly avoid detection.

## Correction in owning Purchase audit

Updated **SELECT-only** `docs/audits/purchase-debit-note-currency-reconciliation.sql`, preserving the existing five report interfaces:

- Report 3 flags negative, zero or NULL `invoice_credit_allocations.allocated_amount` with review reason `invalid_allocation_amount`, even when note/invoice currency and scope otherwise match.
- Report 4 additionally flags NULL/nonpositive note face value and NULL/negative stored allocated or remaining amounts, even when conservation sums balance algebraically.
- Reports 1, 2 and 5 and all existing tenant-scoped joins remain unchanged.
- No backfill, update, delete, inferred currency, FX conversion or GL write was added.

Two regression cases in the existing dependency-free `docs/audits/test_purchase_debit_note_currency_reconciliation.py` fixture demonstrate offsetting negative credits, an otherwise valid invoice with a zero credit entry, and zero/negative face value notes. The suite is now expected to contain eight tests.

## Verification

In the restricted sandbox, a **focused SQLite execution** of the new report-4 sign and conservation predicate passed for four synthetic note rows (three flagged, one valid). Repository branches and file diffs were inspected via GitHub. The exact checked-in eight-case Python suite and any historical MySQL/GL reconciliation were **not** executed here; the SQLite predicate smoke is not a substitute for MySQL or real-data financial sign-off. A real historical dataset is not present or accessible in this sandbox.

The original authoritative `worktree-0.0.8` and PR #127 remain untouched. No paid resources, Actions, finance-number guesses, production database changes or rule bypass.
