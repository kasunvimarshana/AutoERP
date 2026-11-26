# Finance Module End-to-End Audit & Hardening

- **Date:** 2026-11-26
- **Author:** Kasun Vimarshana <kasunvmail@gmail.com>
- **Phone:** +94713525253

## 1. Context & Problem Statement

A comprehensive end-to-end audit of AutoERP's Finance module (`app/Modules/Finance`, its service layer, reporting layer, validation layer, and cross-module boundaries with Billing, Invoicing, Payments, Payroll, and Vehicle Rental) identified several critical edge cases, data scope oversights, and lifecycle immutability gaps:
1. **Tenant Fallback Accounts in Organization Units:** Root/tenant-level fallback accounts (`organization_unit_id IS NULL`, such as Customer Security Deposit `2350`, Realized FX Gain `4300`, Realized FX Loss `5300`, and default COA templates) were strictly excluded when querying lookups, accounts, trial balance, and balance sheets under an organization unit / branch context (`WHERE organization_unit_id = ?`).
2. **Missing Scope in Ledger Calculations:** `AccountBalanceService::forAccounts()` did not filter ledger entries by `tenant_id` or `organization_unit_id`, causing branch trial balances and statements to aggregate ledger entries across all branches.
3. **Account Balances Unique Key Collision:** `AccountBalanceService::applyJournalLine()` queried `firstOrNew(['tenant_id' => $journal->tenant_id, 'organization_unit_id' => $journal->organization_unit_id, 'account_id' => $line->account_id])`. Because `finance_account_balances` has a unique key on `['tenant_id', 'account_id']`, using a tenant fallback account across different branches caused a fatal `UniqueConstraintViolationException`.
4. **Fragile Balance Sheet Account Categorization:** In `FinanceStatementService::balanceSheet`, strict equality on `$typeCode === 'ASSET'` / `'LIABILITY'` / `'EQUITY'` dropped account type code variants (such as `CURRENT_ASSET`, `FIXED_ASSET`, `CURRENT_LIABILITY`) from balance sheet aggregations.
5. **Budgets & Bank Reconciliation Organization Scope Mismatch:** `BudgetService::save()` and `BankReconciliationService::assertBankAccount()` threw scope mismatch exceptions when branch operations referenced tenant-default accounts (`organization_unit_id IS NULL`). Additionally, `BudgetService::save()` did not validate `dimension_id` scope.
6. **Reversal Protection Gap:** `assertReversible()` allowed reversal journals (`JournalType::Reversal` or `reversal_of_id !== null`) to be reversed again, creating cyclical journal chains and violating audit trails.
7. **Inactive Posting Profile Creation:** `FinanceValidationService::validateJournalCreation` did not reject drafts referencing inactive posting profiles.
8. **Lifecycle Immutability Enforcement:** `FinanceJournalEntry`, `FinanceBankReconciliation`, and `FinanceBankStatementLine` lacked strict `deleting` and `updating` lifecycle hooks preventing accidental deletion or in-place tampering of finalized records.

## 2. Root Cause Analysis

- **Tenant Fallback vs Organization Scoping Inconsistency:** AutoERP architecture defines accounts with `organization_unit_id === null` as valid fallback accounts accessible to all organization units in that tenant. Filtering exclusively by `= $organizationUnitId` silently dropped these accounts.
- **Ledger Ingestion & Balance Projection:** `FinanceAccountBalance` represents the cached balance for an account in a tenant (unique by `['tenant_id', 'account_id']`). Scoping `firstOrNew` by the journal's `organization_unit_id` caused duplicate inserts instead of finding the account's existing cached balance record.
- **Reporting & Statement Filtering:** Date range queries and trial balance services must pass `organizationUnitId` down to `AccountBalanceService::forAccounts()` so ledger entries are filtered to the specific organization unit while accounts include both branch-specific and tenant fallback accounts.

## 3. Key Changes

### Service & Reporting Layer
- **`AccountBalanceService`**:
  - In `applyJournalLine()`, resolved `FinanceAccountBalance` by `['tenant_id' => $account->tenant_id, 'account_id' => $line->account_id]` and filled `organization_unit_id => $account->organization_unit_id`.
  - In `forDateRange()` and `forAccounts()`, added optional `$organizationUnitId` parameter and filtered `finance_ledger_entries` by `tenant_id` and `$organizationUnitId` when provided.
- **`TrialBalanceService`**:
  - Updated `scopeOrganization()` to allow tenant-default accounts: `WHERE organization_unit_id IS NULL OR organization_unit_id = ?`.
  - Passed `$organizationUnitId` to `AccountBalanceService::forAccounts()`.
- **`FinanceStatementService`**:
  - Updated `accounts()` to allow tenant fallback accounts: `WHERE organization_unit_id IS NULL OR organization_unit_id = ?`.
  - Passed `$organizationUnitId` to `AccountBalanceService::forAccounts()` in `profitAndLoss()` and `balanceSheet()`.
  - In `balanceSheet()`, robustly categorized account types by substring/normal balance fallback (`ASSET`, `LIABILITY`, `EQUITY`).
- **`BudgetService`**:
  - In `save()`, permitted tenant fallback accounts (`$account->organization_unit_id === null || $account->organization_unit_id === $organizationUnitId`).
  - Added strict validation for `dimension_id` ensuring dimensions belong to `$tenantId` and are scope-compatible with `$organizationUnitId`.
- **`BankReconciliationService`**:
  - In `assertBankAccount()`, permitted tenant fallback bank accounts (`$account->organization_unit_id === null || $account->organization_unit_id === $organizationUnitId`).

### Controller & Resource Layer
- **`FinanceConfigurationController`**:
  - In `lookups()`, used `scopeOrganizationWithTenantFallback` ordered by `organization_unit_id IS NULL ASC` for `$accounts`, `$dimensions`, and `$bankAccounts`.
- **`FinanceController`**:
  - Scoped accounts and bank statement lines with fallback logic.
  - Passed `organization_unit_id` to ledger and balance service calls.
  - Supported `as_of_date` query parameter in `arAging()` and `apAging()`.
- **`JournalEntryResource`**:
  - Updated `can_reverse` to ensure `reversal_of_id === null` and `journal_type !== JournalType::Reversal`.

### Validation & Model Protection
- **`FinanceValidationService`**:
  - In `validateJournalCreation()`, rejected creation with inactive posting profiles (`! (bool) $profile->is_active`).
  - In `assertReversible()`, threw `InvalidArgumentException('Reversal journals cannot be reversed.')` if `reversal_of_id !== null` or `journal_type === JournalType::Reversal`.
- **`FinanceJournalEntry`**:
  - In `booted()`, prevented updating cancelled journals and blocked deletion of any non-draft journals (`$status !== JournalStatus::Draft`).
- **`FinanceBankReconciliation` & `FinanceBankStatementLine`**:
  - In `booted()`, blocked deletion of completed reconciliations and matched bank statement lines.

## 4. Verification & Test Results

1. **New Automated Hardening Tests (`FinanceHardeningTest`):**
   - `test_inactive_posting_profile_rejected_on_journal_creation` (passed)
   - `test_reversal_journal_cannot_be_reversed` (passed)
   - `test_cancelled_and_posted_journal_mutation_and_deletion_protection` (passed)
   - `test_completed_bank_reconciliation_cannot_be_deleted` (passed)
   - `test_organization_unit_trial_balance_and_fallback_accounts` (passed)
   - `test_budget_allows_tenant_fallback_accounts_and_validates_dimension_scope` (passed)
2. **Quality Gates:**
   - `php artisan test --filter Finance`: 100 passed (3,617 assertions).
   - `php vendor/bin/pint --test`: Passed (0 style violations).
   - `npm run typecheck`: Passed (0 TypeScript errors).
   - `npm run lint`: Passed (0 ESLint errors).
   - `npm test` (Vitest): 181 passed (765 assertions).
   - `php artisan test --filter ModuleMigrationBaselineTest`: 1 passed (3 assertions).
   - `php artisan test --filter HistoricalRetentionMigrationTest`: 1 passed (22 assertions).
   - `php artisan test` (Full Repository): 1,493 passed (15,585 assertions).

## 5. Impact Assessment

- **Data Integrity:** Eliminates potential duplicate balance entries and unique constraint violations across branch operations. Prevents cyclical reversal spam.
- **Reporting Accuracy:** Multi-branch tenants now get accurate, branch-isolated trial balances and financial statements while retaining access to shared chart-of-accounts templates.
- **Zero Breaking Changes:** All 1,493 existing automated tests pass without regressions. Backward-compatible APIs remain intact.
