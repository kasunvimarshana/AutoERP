# Finance reversed-journal posting replay protection — 2026-11-01

## Defect and consequences

The Finance posting contract previously treated both **Posted** and **Reversed** journal states as successful idempotent results in two places:

- `FinancePostingService::postJournal()`, used by `FinancePostingInterface::post()`.
- `JournalPostingService::post()`, the owner of ledger posting.

A reissued posting command for a reversed source could therefore return a *reversed* journal result instead of failing. An upstream module could incorrectly interpret the absence of an error as a successful accounting posting. This was not a ledger duplication (the guard prevented one) but it was an unsafe success response for a terminal reversal.

## Implementation

Both Finance-owned posting entry points now return the existing journal only if it is **Posted** (true idempotent replay). Reversed, Cancelled and other non-Draft states reject the operation. The existing Draft flow, posting profile/account/period checks, and ledger atomicity remain intact. No historical journal data, GL balances, posting profiles, or financial documents were rewritten.

## Regression coverage

- `FinanceHardeningTest::test_posted_replay_is_idempotent_but_reversed_finance_journal_cannot_post_again` creates a finance-owned posting on a disposable database, verifies successful Posted replay, reverses it, then checks that the contract's `postJournal`, `post`, and the underlying JournalPostingService all reject reversed replays. Original and reversal ledger-entry counts must remain unchanged.
- Updated `FinancePostingIdempotencyBoundaryTest` to assert the new terminal-state guard in both Finance services, without weakening the existing unique-source/fingerprint protections.

## Verification boundary

This session could inspect current source files and commit exact GitHub changes, but did not execute Laravel's test suite against the changed release candidate. The accessible command sandbox is Linux, does not have the Windows development directory `D:\\project-new\\kasun\\AutoERP`, and lacks Composer, PHP BCMath and PDO database drivers. Tests must run against this exact SHA in the authorized local environment before merging.

This is a root-cause correctness fix. It **does not** resolve the open Invoice/Purchase/Rental financial posting design and historical financial reconciliation requirements, which remain release acceptance gates. No paid tools, GitHub Actions, production DB operations, or approval bypass were used.
