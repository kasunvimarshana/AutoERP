# Change Record: Currency Defaults Audit and Free Plan Remediation

**Date:** 2026-11-20  
**Scope:** `resources/js/modules/tenant`, `resources/js/modules/customer`, `resources/js/modules/supplier`, `resources/js/modules/item`, `resources/js/shared`  
**Purpose:** Deep audit, inspection, and hardening of currency input fields across tenant plans and application entities, resolving dirty state defects on free plans, ensuring default currency markers in lookups, and prefilling default currencies in bank account forms.

## Summary of Changes

1. **Free Tenant Plan Editing Remediation (`TenantPlanEditor.tsx` & `TenantPlanEditor.test.tsx`)**:
   - Fixed defect where editing an existing free plan (`price === '0.000000'`, `currency_id === null`) caused `useEffect` to populate `currencyId = defaultCurrencyId` without updating `initial.currencyId`, making the form immediately dirty on render.
   - Guarded currency pre-fill in `TenantPlanEditor` so default currency is only populated if creating a new plan (`!plan`) or if editing an existing paid plan (`isPaidRevision`).
   - Retained automatic population of default currency when a user changes price from free (`0`) to positive (`> 0`).
   - Preserved identity-only edits (e.g. name or slug updates) on free plans without forcing revision change notes or sending unexpected `currency_id` fields.
   - Added unit tests verifying clean initial clean state for free plans, identity update submission without revision notes, and price transition from 0 to positive.

2. **Tenant Profile Currency Default & Marker (`TenantProfilePanel.tsx`)**:
   - Resolved default currency for draft tenants without a base currency set (`!tenant.base_currency_id`).
   - Appended `(Default)` label marker to the configured default currency option in the base accounting currency select dropdown.

3. **Customer & Supplier Bank Account Creation Pre-fill (`CustomerBankAccountTab.tsx`, `SupplierBankAccountTab.tsx`)**:
   - Auto-loaded default currency when opening the form to add a new bank account (`!row`), eliminating repetitive manual searches.
   - Preserved existing bank account currencies when editing (`row !== null`).

4. **Lookup Select Label Uniformity (`CustomerCurrencySelect.tsx`, `SupplierCurrencySelect.tsx`, `ItemCurrencySelect.tsx`)**:
   - Updated currency label formatters to append `(Default)` marker when `currency.is_default` is true, providing clear visual guidance across all entity forms.

5. **Type Safety & Hardcoding Clean-up (`common.ts`, `currency.ts`)**:
   - Added optional `is_default?: boolean` to `NamedResource` in `common.ts` for uniform type coverage.
   - Refactored fallback currency codes in `currency.ts` to `FALLBACK_DEFAULT_CURRENCY_CODES` constant.

## Verification
- **PHPUnit Tenant & ReferenceData Suite**: `php artisan test tests/Unit/ReferenceData/ app/Modules/Tenant/Tests/` passed (54 passed, 663 assertions).
- **Vitest Tenant Suite**: `npx vitest run resources/js/modules/tenant/` passed (7 test files, 35 passed).
- **Vitest Cross-Module Suite**: `npx vitest run resources/js/shared/utils/currency.test.ts resources/js/modules/customer/ resources/js/modules/supplier/ resources/js/modules/item/ resources/js/modules/hr/` passed (9 test files, 22 passed).
- **TypeScript Typecheck**: `npm run typecheck` passed (0 errors).
- **Vite Production Build**: `npm run build` passed (0 errors, 8.38s).
- **Git Diff Hygiene**: `git diff --check` passed cleanly with 0 whitespace issues.
