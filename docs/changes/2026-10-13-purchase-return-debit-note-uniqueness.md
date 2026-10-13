# Purchase return debit-note uniqueness — 2026-10-13

## Context and evidence

In the Purchase domain, `PurchaseReturn` has a single `debit_note_id` pointer, and `PurchaseReturnService::post()` creates one linked supplier debit note. The debit-note creation service nevertheless accepted repeated calls with the same `purchase_return_id`. Its table had a non-unique lookup index on `purchase_return_id`, leaving it possible for service replays or concurrent creators to create duplicate financial-source notes.

## Source-owned correction

- `PurchaseDebitNoteService::assertSourceContract()` now rejects a linked purchase return that already has a debit note, including a soft-deleted historical debit note. It uses the existing canonical supplier-party enum, not a new literal.
- An explicit **Purchase-owned upgrade migration** adds a unique key to `(tenant_id, purchase_return_id)` so races and direct writes cannot defeat the service guard. Independent manual debit notes keep `purchase_return_id = NULL`; both SQLite and MySQL permit multiple NULLs in a unique key.
- `PurchaseEngineTest::test_purchase_return_from_grn_line_posts_inventory_and_creates_debit_note()` now tests rejected source replay without changing the existing linked-note count and verifies the SQL unique constraint rejects a direct duplicate.

This enforces the existing one-to-one source ownership model. It does **not** assume rates, invoice currency, Finance journals, or external contracts. Other modules were not modified.

## Upgrade/data-integrity policy

Before running on a populated database, **audit existing duplicates**; do not silently merge, delete, or backfill historical financial notes. The following query reads only the affected entity:

```sql
SELECT tenant_id, purchase_return_id, COUNT(*) AS note_count
FROM purchase_debit_notes
WHERE purchase_return_id IS NOT NULL
GROUP BY tenant_id, purchase_return_id
HAVING COUNT(*) > 1;
```

Any returned row requires an accountable reconciliation before the unique index migration; deployment must fail explicitly rather than discarding evidence. The new migration is additive, portable Laravel Schema Builder and fully reversible at schema level.

## Verification boundary

PHP parse checks should be performed for changed files, followed by full SQLite/MySQL tests, fresh and populated-schema upgrade/rollback, concurrency tests, and final-head CI. The earlier user-provided 920-test local transcript and old passing hosted build predate this new commit. GitHub-hosted CI currently fails before job execution (issue #124). **Do not merge/deploy** PR #127 until this new head and the remaining Vehicle Rental §R gates are verified/approved.

The unrelated Purchase debit-note currency-provenance blocker (#128) remains open; the uniqueness constraint is not a replacement for currency/FX or Finance reconciliation work.
