# Finance ledger balance foundation correction

Date: 2026-07-11

## Problem

Finance account masters stored mutable opening and current balances while journal posting also created ledger entries and updated account-balance rows. Ledger entries additionally stored a posting-time `balance_after` value without rebuilding later rows after a backdated journal.

That produced competing financial sources of truth and allowed chronological ledger projections to become stale.

## Correction

The Finance module now follows this ownership model:

```text
Finance account master
→ identity and classification only

Opening balance
→ governed opening journal

Debit and credit facts
→ immutable ledger entries

Current/date-range balance
→ ledger-derived calculation

Account balance row
→ rebuildable cumulative projection

Ledger balance_after
→ rebuildable chronological projection
```

Implemented changes:

- removed `opening_balance` and `current_balance` from the Finance account migration, model, DTO, service, seeder, API resource, and frontend forms;
- explicitly reject account-level balance fields at the HTTP request boundary;
- preserve opening amounts through the existing `JournalType::Opening` lifecycle;
- classify opening-journal and later-period amounts separately in the cumulative projection;
- stop mutating account masters during posting;
- lock all affected accounts in deterministic identifier order;
- retain immutable debit and credit ledger facts;
- rebuild `balance_after` for affected accounts in chronological `entry_date`, then `id`, order after every posting;
- retain `balance_after` only as a documented rebuildable projection;
- use restrictive deletion semantics for account balance projections;
- update account, journal, and ledger UI contracts to use ledger-derived values;
- add behavioral coverage for opening journals, reversals, and a backdated posting that changes a later ledger row from 40 to 100;
- add an architecture contract preventing account-master balances from returning.

## Verification

- exact branch diff reviewed against `worktree-0.0.8`;
- removed `CreateAccountData` argument callers inspected;
- Finance API test now rejects submitted account opening balances;
- Finance engine test covers cumulative and chronological projections;
- architecture test enforces account-master and ledger-projection boundaries;
- no GitHub Actions, force push, or paid tools were used.

## Scope

This correction fixes the confirmed Finance balance source-of-truth defect at the owning module. It does not claim unrelated project audit findings are closed.
