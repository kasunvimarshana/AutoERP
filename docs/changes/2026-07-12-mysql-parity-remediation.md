# MySQL and MariaDB test-parity remediation

Date: 2026-07-12

## Evidence

The guarded MySQL profile executed all 647 tests and exposed 45 errors that the SQLite profile did not surface. The errors reduced to two root causes:

1. Test helpers generated pseudo currency codes longer than the authoritative three-character currency schema.
2. Fast Purchase manual-price confirmation hashes included mutable price-source metadata even though the confirmation contract is defined by item, supplier, UOM, currency, date, tenant, organization, and variant dimensions.

## Corrections

- Preserved the authoritative three-character currency database contract.
- Added one shared `CurrencyFixture` that generates unique schema-valid codes and fails explicitly for invalid supplied codes.
- Replaced module-local invalid currency inserts in Item, HR, Supplier, Vehicle, and Purchase tests.
- Kept descriptive test labels in currency names instead of overloading the business code field.
- Updated Purchase lookup assertions to use the actual persisted currency codes.
- Stabilized the Fast Purchase pricing-context hash around its named commercial dimensions.
- Added regression tests for currency fixture validity and pricing-context stability.

## Scope

No production currency column was widened, no MySQL strictness was disabled, and no application validation was bypassed. Purchase prices, calculations, idempotency identity, posting behavior, and public API response shapes remain unchanged.

## Verification status

- The pre-change default suite passed 647 tests with 8,229 assertions.
- TypeScript, ESLint, Vite build, and 244 frontend tests passed before this remediation.
- Repository compare confirms the large test files changed only at their currency fixture imports, helpers, and one lookup setup.
- The normal Laravel suite and guarded MySQL/MariaDB suite must be rerun from the latest `worktree-0.0.8` head before release approval.
