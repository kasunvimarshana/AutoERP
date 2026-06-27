# Finance role-based posting foundation

Date: 2026-06-28

## Reason

Operational modules selected or persisted concrete Finance accounts, posting profiles mapped directly to account IDs, account masters stored mutable balances, and journals lacked a deterministic source identity and immutable account snapshots. Tax also retained a second ledger-mapping surface. These designs created duplicated accounting configuration, replay risk, mutable historical evidence, and tight coupling between operational modules and the chart of accounts.

## Decision

Finance is the sole owner of account resolution and journals. Operational modules emit semantic posting lines. Finance resolves each line through a posting profile, stable account role, and effective tenant/organization/context assignment.

## Changes

- Added tenant-owned account roles and effective-dated account assignments.
- Posting-profile rules now reference account roles rather than actual accounts.
- Added deterministic source posting identity and source fingerprints.
- Journal lines retain immutable account code, account name, and account-role snapshots.
- Removed opening and current balances from account masters; balances are ledger-derived.
- Removed destructive journal and account deletion paths; corrections use governed reversals.
- Added optimistic versions to accounts, assignments, profiles, and journals.
- Removed concrete account-code/account-ID selection from Purchase, Sales, and Tax posting paths.
- Removed Purchase adjustment Finance-account/profile columns.
- Removed the Tax-owned posting-profile and ledger-mapping surface.
- Tax transactions retain tax facts only; actual account evidence remains in Finance journal lines.
- Global Finance accounts and profiles are visible to OU workflows while mutations preserve the stored scope.

## Verification completed in this checkpoint

- Changed PHP syntax validation passed.
- TypeScript semantic check passed.
- ESLint passed with zero errors and warnings.
- Internal import and migration structure checks are recorded with the checkpoint artifacts.

## Remaining work

Payment lifecycle/schema, Invoice source-allocation trust boundaries, affected test fixtures, full database runtime verification, and end-to-end regression verification remain separate release gates.
