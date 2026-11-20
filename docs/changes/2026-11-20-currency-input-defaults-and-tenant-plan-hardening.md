# Change Record: Currency Input Defaults and Tenant Plan Hardening

**Date:** 2026-11-20  
**Scope:** `app/Modules/ReferenceData`, `resources/js/shared/utils`, `resources/js/modules/tenant`, `resources/js/modules/customer`, `resources/js/modules/supplier`, `resources/js/modules/item`, `resources/js/modules/hr`  
**Purpose:** End-to-end review, audit, and hardening of currency input fields across tenant plans and entity creation workflows (tenants, customers, suppliers, item prices, employee rates), ensuring default currency loads consistently while respecting user selections and integrating with existing tenant plan configurations.

## Summary of Changes

1. **Authoritative Backend Default Currency Indication (`CurrencyResource.php` & `referenceDataTypes.ts`)**:
   - Added `'is_default'` boolean property to `CurrencyResource` computed dynamically from application configuration `config('app.bootstrap.currency_code', 'USD')`.
   - Updated TypeScript interface `ReferenceRecord` in `referenceDataTypes.ts` to include optional `is_default?: boolean`.

2. **Canonical Currency Default Resolution Helper (`resources/js/shared/utils/currency.ts`)**:
   - Created `findDefaultCurrency()` and `resolveDefaultCurrencyId()` utility functions adhering to single-source-of-truth precedence:
     1. Record with `is_default === true` from backend API.
     2. Configured fallback codes (`LKR`, `USD`).
     3. First active record (`is_active === true`).
     4. First available record.
   - Added unit test suite `resources/js/shared/utils/currency.test.ts` covering all edge cases (empty list, boolean flag, code fallback, active-only fallback, and single item).

3. **Tenant Plan Editor Currency Defaults & UI/UX Hardening (`TenantPlanEditor.tsx`)**:
   - Loaded resolved default currency on tenant plan creation (`!plan`) while strictly preserving existing `revision.currency_id` when editing existing plans.
   - Tracked user modifications with a `currencyTouched` ref to prevent unintentional clobbering of explicit user currency choices when asynchronously updating options.
   - When users enter a positive price on an initially free plan, automatically pre-populated the default currency if none was selected.
   - Added human-readable `(Default)` visual marker on the system default currency option within the dropdown selector.
   - Fixed `DecimalInput` `onChange` handler to read `event.target.value` instead of passing the raw event to price state.

4. **Platform Tenant Creation Currency Defaults (`PlatformTenantForm.tsx`)**:
   - Auto-selected resolved default currency for `base_currency_id` on new tenant creation while allowing full user customization before saving.
   - Retained lock for active tenant base currency immutability.

5. **Cross-Application Entity Creation Currency Consistency**:
   - **Customer Creation (`customerCreateDefaults.ts`, `CustomerCreatePage.tsx`, `CustomerOneShotBuilder.tsx`)**: Replaced brittle hardcoded currency lookups with `findDefaultCurrency()`, and passed the default currency into draft bank accounts in `CustomerOneShotBuilder`.
   - **Supplier Creation (`SupplierCreatePage.tsx`, `SupplierOneShotBuilder.tsx`)**: Automatically fetched reference currencies on mount, preselected default currency, set `initialCurrencyId` to prevent false dirty states, and seeded bank account drafts with the default currency.
   - **Item Creation (`ItemCreatePage.tsx`, `ItemOneShotBuilder.tsx`)**: Pre-populated initial price drafts with the default currency and base UOM.
   - **Employee Rates (`EmployeeRateTab.tsx`)**: Fixed functional deficiency where editing an existing employee rate failed to prefill its saved currency, and ensured new rate creation preselects the tenant's default currency.

6. **Comprehensive Automated Test Coverage Expansion**:
   - `TenantPlanEditor.test.tsx`: Added 4 new tests verifying default loading on plan creation, user override preservation, currency retention when editing, and currency modifications when editing.
   - `PlatformTenantForm.test.tsx`: Added tests verifying default currency preselection and user override on tenant creation.
   - `currency.test.ts`: Added 8 tests covering resolution hierarchy and edge cases.

## Verification
- **PHPUnit Tenant & ReferenceData Suite**: 54 passed (663 assertions).
- **Vitest Suite**: 54 tests passed across 16 test files.
- **TypeScript Typecheck**: `npm run typecheck` passed (0 errors).
- **Vite Production Build**: `npm run build` passed (0 errors, 8.35s).
- **Git Hygiene**: `git diff --check` passed cleanly with 0 whitespace issues.
