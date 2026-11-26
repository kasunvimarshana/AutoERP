# 2026-11-26 — Optional Customer and Supplier Credit Privileges and Limits Audit

**Author:** Kasun Vimarshana <kasunvmail@gmail.com>  
**Phone:** +94713525253

## Summary
- **Domain Decoupling: Credit Status vs. Credit Limits**:
  - Addressed fundamental design flaw where `credit_limit` was forced to non-null default `'0.000000'` in database schemas and application models. This previously caused any customer or supplier with open balances (> 0) to be flagged as exceeding credit limits, blocking valid operational workflows.
  - Decoupled credit privileges (`credit_allowed` / `is_credit_allowed`) from credit ceilings (`credit_limit`).
  - Credit status/privilege is an optional boolean entitlement; credit limit is an optional numeric ceiling.
  - When credit privileges are granted but no credit limit is assigned (`credit_limit === null`), credit transactions are unconstrained by artificial limits (unlimited).
  - When credit privileges are not granted (`credit_allowed === false` or inactive profile), credit purchases / keeping invoices on credit are strictly blocked.
- **Multi-Currency Credit Exposure & Limit Assessment in FastPurchase**:
  - Fixed currency mismatch flaw in `FastPurchaseService::validateCredit`: Resolved supplier target currency (supplier default currency or base currency) and converted the new unpaid purchase amount and open invoice exposure into the supplier limit currency using `InvoiceBalanceProvider::getPartyExposureInCurrency`.
  - Added guard against missing exchange rates on open foreign-currency supplier invoices when `allow_over_credit` is false.
- **Model Dynamic Accessors & Relation Preload Invariance**:
  - Added `getIsAdvanceAllowedAttribute(): bool` on `Customer` model, defaulting `advanceAllowed` to `true` when no profile exists.
  - Added `getCreditLimitAttribute(): ?string` and `getIsCreditAllowedAttribute(): bool` on `Supplier` model, properly casting `credit_limit` with `'decimal:6'` format and validating active profile status.
  - Updated `CustomerSummaryResource` and `SupplierSummaryResource` to read through authoritative model accessors rather than checking `$this->relationLoaded('creditProfile')`, ensuring consistent serialization regardless of eager-loading state.
  - Synchronized `$profile->setRelation('supplier', $supplier)` in `SupplierCreditProfileService::set` to prevent redundant queries.
- **Frontend Form Consistency & Over-Credit Invariant Enforcement**:
  - In `CustomerCreditProfileTab.tsx`, `CustomerOneShotBuilder.tsx`, `SupplierCreditProfileTab.tsx`, and `SupplierOneShotBuilder.tsx`: When `credit_allowed` / `is_credit_allowed` is unchecked, `allow_over_credit` is automatically reset to `false` and disabled in the UI.
  - In `SupplierUpdateService`: When master `is_credit_allowed` is updated to `false`, `allow_over_credit` on any existing credit profile is automatically reset to `false`.
  - In `SupplierValidationService`: Explicitly validated that `allowOverCredit` cannot be true when credit is disabled during creation or update.

## Database Schema (Zero-Patch Baseline Migration Hygiene)
- `app/Modules/Customer/Database/Migrations/2026_06_12_110008_create_customer_credit_profiles_table.php`: `credit_limit` nullable decimal.
- `app/Modules/Supplier/Database/Migrations/2026_06_12_100001_create_suppliers_table.php`: `credit_limit` nullable decimal.
- `app/Modules/Supplier/Database/Migrations/2026_06_12_100009_create_supplier_credit_profiles_table.php`: `credit_limit` nullable decimal.

## Verification
- **Code Style (Pint)**:
  - `php vendor/bin/pint --test`: PASSED (0 violations).
- **TypeScript & Frontend Quality Gates**:
  - `npm run typecheck`: PASSED (0 errors).
  - `npm run lint`: PASSED (0 errors).
  - `npx vitest run`: PASSED (181 test files passed, 765 tests passed).
- **Migration & Architecture Baseline Gates**:
  - `php artisan test --filter ModuleMigrationBaselineTest`: PASSED (1 passed, 3 assertions).
  - `php artisan test --filter HistoricalRetentionMigrationTest`: PASSED (1 passed, 22 assertions).
- **Automated Backend Test Suites**:
  - `php artisan test app/Modules/Customer`: PASSED (14 passed, 94 assertions).
  - `php artisan test app/Modules/Supplier`: PASSED (16 passed, 76 assertions).
  - `php artisan test app/Modules/Purchase`: PASSED (123 passed, 843 assertions).
  - `php artisan test app/Modules/VehicleService`: PASSED (115 passed, 835 assertions).
  - `php artisan test tests/Feature/Customer`: PASSED (7 passed, 124 assertions).
  - `php artisan test tests/Feature/Supplier/SupplierApiTest.php`: PASSED (11 passed, 157 assertions).
