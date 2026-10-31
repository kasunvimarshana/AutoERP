# Finance posted-journal replay and reversal integrity guard — 2026-11-01

## Root cause

A journal marked **Posted** could previously be acknowledged as a successful idempotent replay solely because of the journal status, even when an imported/deleted/corrupted ledger row no longer matched the immutable source lines. The same incomplete source could be reversed, generating a nominally balanced reversal against materially incomplete original ledger evidence. Existing two-way and three-way read-only audits can detect this, but the runtime Finance posting boundary still needed to fail closed.

## Finance-owned implementation

- `FinanceValidationService::assertPostedLedgerIntegrity()` verifies that every posted source journal line has exactly one persisted ledger entry, with matching tenant/organization scope, journal owner, account, dimension, original transaction-currency debit/credit and header totals. Missing lines, duplicate ledger entries, wrong account, edited amount or mismatching totals now raise `InvalidArgumentException`; no historical amounts are rewritten.
- `JournalPostingService::post()` calls that validation before returning a successful **Posted** idempotency response.
- `FinancePostingService` now delegates both direct Posted-journal replay and source-fingerprint replay through that same Finance-owned guard, rather than bypassing it.
- `FinanceValidationService::assertReversible()` checks the original posted ledger before creating a reversal. The existing source-module boundary, source fingerprints, posting permissions and closed-period checks remain intact.

This explicitly favors **fail closed** over automatic data repair. No journal, exchange rate, tax, account assignment, source document, API, permission, migration, or production database was changed outside the Finance owner.

## Regression cases committed

Added two Laravel integration tests in `FinanceHardeningTest`: simulate one missing ledger entry, verify that all three replay entry points and reversal fail without creating a journal/ledger entry; simulate a mutated ledger debit and ensure direct posted replay refuses it. Historical corruption is simulated via raw SQL in disposable `RefreshDatabase` fixtures; the application continues to prohibit editing immutable ledger models. Updated Finance's existing static idempotency boundary test for the changed source-fingerprint guard.

## Verification status

The change was reviewed against the exact GitHub branch source. **Full Laravel/PHP unit/MySQL tests for these new runtime edits have not been run**, because this session is still isolated from the Windows checkout and lacks Composer/vendor, BCMath and PDO test databases. No passing Laravel/production-build assertions are claimed. The standalone Finance SQL audit *was* executed earlier on isolated SQLite (11 tests passing, separate checked hashes), which does not substitute for testing these runtime changes.

Required before merging: execute both `FinanceHardeningTest` and full backend/frontend suites against this exact release candidate; apply the read-only Finance/Purchase audit against an authorized restored historical dataset; independently approve monetary liability/FX/tax settlement policies, outstanding Vehicle Rental §R acceptance and required branch checks. No GitHub Actions, paid services or live data mutations were performed.
