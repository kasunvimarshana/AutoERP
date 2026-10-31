# Finance journal-line cardinality audit — 2026-11-01

## Root cause and fix

A posted journal can have perfectly matching **header, journal-line and ledger totals and counts**, yet still be wrong when two equal-valued ledger entries reference one journal line and a second equal-valued line has no ledger entry. Existing audit reports checked aggregate totals and per-existing-entry amount/account/scope but did not establish the **one-ledger-entry-per-journal-line** invariant.

Extended the existing read-only `docs/audits/finance-journal-ledger-integrity.sql` with report 3. It starts from every posted/reversed journal's document lines, left-joins ledger entries by journal, tenant and original journal-line ID, and reports zero or multiple ledger entries for a line. It does not infer account policies, exchange rates, amounts or automatic financial adjustments. The first two reports remain unchanged.

Added two regression cases in the accompanying standard-library SQLite test: missing entry; and a deliberately constructed balanced journal with four lines where duplicate ledger entries on one line exactly mask another missing line in **both prior reports**. Verified that the new third report detects both defects.

## Executed evidence

- `python -B -m unittest -v test_finance_journal_ledger_integrity.py` — **11 tests passed**, in isolated disposable SQLite memory with a read-only SQL authorizer, after first observing and correcting a test-fixture mistake.
- Matching Git blob SHA for executed SQL: `6dbfb76fe4e5b8e6720d958b910b19168c7f6f17`.
- Matching Git blob SHA for executed Python tests: `ab941edd4ebc01cb10944dfa5f1ae5f032f049f4`.

This provides executable **audit SQL syntax and synthetic detection** evidence, not successful Laravel or MySQL execution on the user's Windows checkout and not historical production reconciliation. The work made no database writes outside synthetic in-memory tests; no source posting, GL mutation, migrations, payments, workflows, paid services, GitHub Actions or protected-branch changes were attempted. A populated historical-data review and genuine journal/ledger tie-out are still required for release.
