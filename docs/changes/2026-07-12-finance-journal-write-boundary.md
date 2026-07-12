# Finance journal write-boundary hardening

Date: 2026-07-12

## Problem

FinanceJournalEntry retained a permissive `guarded = ['id']` override even though Finance already owns journal creation, editing, posting, and reversal. This allowed future broad model writes to bypass the intended owner-service boundary for journal status, totals, source identity, posting profile, and audit fields.

## Correction

- Removed the permissive guard override so FinanceJournalEntry inherits Core's deny-by-default policy.
- Updated JournalEntryCreationService to explicitly construct and `forceFill()` the authoritative journal payload before saving.
- Preserved the existing explicit update and cancellation paths.
- Added a Finance-owned boundary test that protects the guarded model and explicit owner-service creation contract.

## Scope

Journal calculations, lines, posting, reversal, immutability, idempotency, source traceability, API contracts, and schema are unchanged.

## Verification

- PHP syntax validation passed for the model, owner service, and boundary test.
- Repository search confirmed JournalEntryCreationService is the only production journal creation owner.
- Re-fetch verification is required together with the normal full Laravel test suite before release.
