# Accounting Period Posting Enforcement

**Date:** 2026-07-13  
**Branch:** `worktree-0.0.8`

## Context

The final Laravel and MySQL regression failure showed that closed accounting periods were modeled and validated correctly by `AccountingPeriodService`, but the journal posting orchestrator did not invoke that policy before writing ledger entries.

## Change

- Injected `AccountingPeriodService` into `JournalPostingService`.
- Enforced the persisted journal date against the journal tenant and organization-unit scope before ledger posting.
- Kept draft journal creation available independently of period state; the policy is enforced at the financial posting boundary.
- Preserved idempotent reads of journals that are already posted or reversed.
- Preserved transactional locking so a period close cannot race a journal posting decision.

## Design boundary

The closed-period rule remains owned by Finance. No business module duplicates period logic, and no compatibility fallback bypasses configured periods.

## Verification status

The fix is covered by `AccountingPeriodServiceTest::test_close_blocks_journal_posting_and_reopen_restores_it`. The branch must be pulled and the targeted test plus full Laravel and MySQL suites rerun before the remediation is considered fully verified.
