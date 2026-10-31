# Finance journal-to-ledger read-only conservation audit — 2026-11-01

## Purpose and boundaries

Journal headers can balance while their document lines or persisted ledger entries disagree. The Finance trial balance alone may not expose balanced-but-wrong journal totals, missing entries, incorrect line/account lineage or cross-tenant imported records. This is a **read-only diagnostic**, not a financial repair or proof of historical reconciliation.

The new `docs/audits/finance-journal-ledger-integrity.sql` contains exactly two SELECT reports for an **authorized isolated MySQL 8+ restoration**:

1. Checks every posted/reversed journal for header debit=credit, header-versus-document-line amounts, document lines versus ledger entries (number and amounts), absent lines and invalid negative/zero/two-sided ledger entries. Totals use the transaction-currency `debit` and `credit` columns, **not base-currency amounts**, and require no inferred exchange rate.
2. Checks each persisted ledger entry's journal/line tenant identity, parent journal, account, organization and original debit/credit amounts; emits a review reason for anomalies.

Both reports include tenant IDs and original document identifiers for accountable reconciliation. Neither uses user-defined rates, source-specific GL assumptions, automatic fixing, data imports, writes or production database credentials.

## Actual execution evidence

Created `docs/audits/test_finance_journal_ledger_integrity.py` using only standard-library SQLite. This test file creates isolated, disposable in-memory tables and asserts that both SQL statements are SELECT-only using SQLite's SQL authorizer. Cases cover valid journals, balanced-but-wrong headers, absent ledger entries/document lines, account and monetary mismatches, negative/zero entries, cross-tenant references, NULL-sensitive organization mismatches and reversed journal conservation.

**Actually ran:** `python -B -m unittest -v test_finance_journal_ledger_integrity.py` in the isolated Linux container: **9 tests passed**, all in 0.006 seconds.

The exact local test and SQL file Git blob hashes match the committed GitHub blobs:
- SQL: `fad201a5ae476d7840c4996ba953e37a0c0fdb0a`
- Python: `46664a96cd763aff42698e3e41cace51e8687550`

This is genuine SQLite synthetic test evidence. **It is not a populated MySQL run, real-data accounting reconciliation, full Laravel verification or production acceptance.** This environment has no access to the user's Windows checkout, MySQL database, Composer/vendor or BCMath.

This audit complements the Finance journal header/line posting validation added on the same candidate; it does not create a second ledger or override Finance accounting rules. Existing PR #127, issues #124/#128 and independent financial review remain open. No paid services, Actions workflow runs or live database modifications occurred.
