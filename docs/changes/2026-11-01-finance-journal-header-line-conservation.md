# Finance journal header-versus-line monetary conservation — 2026-11-01

## Verified source defect

`FinanceValidationService::validateForPosting()` checked that a journal's **stored header** debit and credit totals balance and validated individual lines, but did not check that the sum of those lines matches the stored header. It also did not reject an empty set of journal lines.

A historical/imported Draft with header debits/credits both `90.000000`, yet actual lines `100.000000` per side, could pass and post ledger entries inconsistent with its header. A Draft with balanced `100/100` header and **no lines** could transition to Posted with zero ledger entries. A balanced header is not proof of balanced, complete underlying books.

## Root-cause Finance change

The owner module's posting validation now rejects empty journal-line sets and calculates exact six-place debit/credit totals from the same validated lines that the ledger will consume. Either side differing from the header blocks posting before any ledger entry is written. This is independent of posting profile, currency, tenant and source; those existing checks remain in force. It uses `DecimalMath`, no floats or silent rounding, and does not rewrite historical money.

## Executable regression tests added

`FinanceHardeningTest` now creates genuine Finance Drafts and simulates a stale header and a missing-line import, respectively. Both cases require explicit exceptions, the original journal staying Draft and **zero ledger entries**. The separate reversed-journal replay regression is retained in the same test module.

**Verification limitation:** These Laravel tests were committed but could not run in this session's isolated Linux environment; the user Windows project and database are not mounted, Composer and BCMath are unavailable. They require execution on the exact candidate SHA with the existing test profiles before release. No historic-data or GL tie-out is claimed.

The implementation does not add rates, policy assumptions, account codes, migrations, paid tools, Actions workflow runs, protected target-branch writes or production data changes.
