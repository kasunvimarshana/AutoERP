# Purchase historical-currency audit regression test — 2026-10-14

## Reason

Repeated reviews discovered false negatives in the SELECT-only Purchase reconciliation report: asymmetric NULL organization scopes, an allocation organization differing from the invoice, and identical but invalid `customer`/`customer` party types. These are **investigation-query defects**; they do not establish that the live application is vulnerable or that any historical financial row is wrong.

## Implementation

Added `docs/audits/test_purchase_debit_note_currency_reconciliation.py` beside its owning `purchase-debit-note-currency-reconciliation.sql`. The tests use Python 3 standard-library `unittest` and `sqlite3` only; the database is created exclusively with `sqlite3.connect(':memory:')`. No Laravel application connection, credentials, network, GitHub Actions, paid dependencies, or production data are involved.

The fixture covers: missing note/return currencies; unresolvable historical allocations; mismatched currencies; mismatched and NULL organization scopes on both the Purchase note and the Invoice-owned allocation row; mismatched/same-invalid supplier party types; missing parties; wrong invoice direction; cross-tenant credit-source identifiers; amount/credit-allocation conservation; and source lineage without backfilling currency. Each of the five audit SELECT statements is executed with SQLite's read-only authorizer enabled.

## Actual local verification

Executed:

```shell
python -m unittest discover -s docs/audits -p 'test_*.py' -v
```

The initial run against a prior audit query reproduced known false negatives (one test failed, four passed). The same test suite against the corrected query structure produced **5 tests passed / 0 failures**, with all expected anomaly classes and clean cases checked. The tests were rerun after committing their exact text. Local `git hash-object` of the tested Python file is `7709e52194d26d345c3f920b746fb0cf5c29d8e5`, matching the new GitHub source blob.

The five executed SQL statements were checked against the exact current GitHub SQL source by whitespace-normalized FNV-1a fingerprints: `134c1fc4`, `1764e032`, `6898ad9d`, `70822b3a`, `5012fef9`. These refer to statement text, not historical data or statement comments.

## Limitations and release gate

This is a **synthetic SQLite audit-regression suite**, not proof that MySQL 8, decimal behavior on populated data, Finance/Invoice posting, migrations, concurrency or UAT are correct. User-supplied Laravel 921 / 10,110, frontend 432, typecheck/lint/build evidence was for **earlier integration commit `17190eef50aceede432d7c36dee774ccaded0a8f`**, not this latest test addition. No fresh full Laravel/frontend/MySQL suite was run on this new HEAD in this environment.

The published SQL remains SELECT-only and must be executed only on an authorized isolated restored financial database by an accountable reviewer. Currency selection, foreign exchange, repair and ledger posting **must never** be automated on speculative historical data. Issue #128 and all nine Rental TODO §R gates remain open. Issue #124's failed GitHub-hosted checks and the NOT APPROVED review continue to block PR #127. Do not merge or declare production readiness until all genuine release requirements are satisfied.
