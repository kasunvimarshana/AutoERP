# Finance Module Deep Audit, Remediation & Verification Review

- **Date:** 2026-11-27
- **Author:** Kasun Vimarshana <kasunvmail@gmail.com>
- **Phone:** +94713525253

## 1. Context & Problem Statement

Following the initial audit of the Finance module, an adversarial secondary review of the codebase identified several subtle but critical flaws, architectural regressions, and unaddressed edge cases:

1. **Reversal Blocking on Historical Posting Profiles:** The initial fix rejected journal creation if its posting profile was inactive (`! $profile->is_active`). However, because reversals invoke `validateJournalCreation` with the original journal's `profile_id`, deactivating a historical posting profile made all previously posted journals linked to it completely impossible to reverse, violating financial reversal invariants.
2. **Account Inactivity and Posting Account Check Gaps:** When creating normal draft journals, deactivated accounts (`is_active = false`) or parent/summary non-posting accounts (`is_posting_account = false`) were not validated, allowing manual journals or legacy profiles to direct entries into header-only or disabled accounts.
3. **Consolidated Tenant Reports Dropping Branch Data:** When querying financial reports (`TrialBalanceService`, `FinanceStatementService`, `CashFlowReportService`, `AgingReportService`, `FinanceTaxReportService`, `GeneralLedgerReportService`) at the tenant root context (`organizationUnitId === null`), the queries applied `whereNull('organization_unit_id')`. This incorrectly excluded all branch-level ledger entries, invoices, and tax transactions from consolidated tenant statements, causing trial balances to fall out of balance when inter-branch or branch-assigned transactions occurred.
4. **Pessimistic Lock Removal in Balance Updates:** `AccountBalanceService::applyJournalLine` previously used `lockForUpdate()->first()` but was altered to `firstOrNew()`, removing pessimistic concurrency row locking on `finance_account_balances` during live journal postings.
5. **Role Validation Bypass in Posting Profiles:** `PostingProfileService::saveRules()` accepted any `account_role_id` without verifying that the role exists, belongs to the current tenant, and is active (`is_active = true`).
6. **Budget Lifecycle Tampering:** `BudgetService::save()` permitted updating already approved or finalized budgets, and `FinanceBudget` / `FinanceBudgetLine` lacked lifecycle model `booted()` deletion and mutation guards.
7. **Chart of Accounts Mutation & Deletion Vulnerabilities:** `FinanceAccount` lacked model-level lifecycle protection. An account with posted ledger entries, child accounts, active role assignments, or journal lines could be deleted, and critical immutable attributes (`tenant_id`, `organization_unit_id`, `normal_balance`) could be modified in place on posted accounts.
8. **Bank Reconciliation In-Place Modification:** `FinanceBankReconciliation` and `FinanceBankStatementLine` lacked `updating()` guards, allowing finalized reconciliations or statement lines to have dates or amounts modified after closure.

## 2. Root Cause Analysis

- **Distinction Between Forward Posting and Audit Reversals:** Accounting reversals restore historical ledger balance; they must succeed even if the chart of accounts, posting profile, or account status has changed in the intervening period. Validation logic failed to differentiate between normal forward journal creation and compensatory reversal journals (`$data->journalType === JournalType::Reversal`).
- **Consolidated vs Branch-Filtered Scoping:** In multi-branch ERP architecture, when a tenant administrator runs a consolidated trial balance or P&L, `organization_unit_id` is null (representing all branches of the tenant). Querying `whereNull('organization_unit_id')` treats null as an exact filter for root-only rows instead of aggregating across all branches. The correct pattern is: when `$organizationUnitId !== null`, scope to the branch; when `null`, include all tenant data.
- **Missing Concurrency Protection:** High-frequency journal posting requires pessimistic row locking (`lockForUpdate()`) on account balances to prevent lost updates under concurrent transactional load.
- **Missing Lifecycle Hooks (Rule 6):** Core transaction and master models must enforce immutability at the Eloquent lifecycle layer (`booted()`), throwing `LogicException` on illegal mutations or deletions.

## 3. Key Changes

### Validation & Posting Security
- **`app/Modules/Finance/Validators/FinanceValidationService.php`**:
  - In `validateJournalCreation()`, allowed reversals (`$data->journalType === JournalType::Reversal`) to bypass inactive profile checks.
  - Enforced validation rejecting inactive accounts (`! $account->is_active`) and summary non-posting accounts (`! $account->is_posting_account`) during forward draft journal creation.

### Concurrency & Ledger Consistency
- **`app/Modules/Finance/Services/AccountBalanceService.php`**:
  - Restored `lockForUpdate()->first()` in `applyJournalLine()`, maintaining pessimistic row locks on `finance_account_balances` rows before balance calculation.

### Tenant-Wide & Branch-Isolated Reporting
- **`app/Modules/Finance/Services/TrialBalanceService.php`**:
  - Updated `scopeOrganization()`: when `$organizationUnitId === null`, no organization filter is applied, aggregating all branch and root accounts into the tenant consolidated trial balance. When `$organizationUnitId` is provided, includes both branch accounts and tenant fallback accounts.
- **`app/Modules/Finance/Services/FinanceStatementService.php`**:
  - Updated `accounts()`: when `$organizationUnitId === null`, includes accounts across the entire tenant; passes `$organizationUnitId` down to `AccountBalanceService::forAccounts()`.
- **`app/Modules/Finance/Services/CashFlowReportService.php`**:
  - Fixed `calculate()`: only applies `where('finance_ledger_entries.organization_unit_id', $organizationUnitId)` when `$organizationUnitId !== null`.
- **`app/Modules/Finance/Services/AgingReportService.php`**:
  - Fixed AR and AP aging queries: only filters `organization_unit_id` when explicitly provided.
- **`app/Modules/Finance/Services/FinanceTaxReportService.php`**:
  - Fixed tax summary reporting: only filters `organization_unit_id` when explicitly provided.
- **`app/Modules/Finance/Services/GeneralLedgerReportService.php`**:
  - Fixed `scopeOrganization()`: only restricts `organization_unit_id` when non-null.

### Lifecycle Immutability & Model Integrity
- **`app/Modules/Finance/Models/FinanceAccount.php`**:
  - Added `booted()` deletion protection: throws `LogicException` if the account is a system account, has posted ledger entries, has journal lines, has child accounts, or has active role assignments.
  - Added `booted()` update protection: throws `LogicException` if attempting to modify `organization_unit_id` or `normal_balance` once ledger entries exist.
- **`app/Modules/Finance/Models/FinanceBudget.php` & `FinanceBudgetLine.php`**:
  - Added `booted()` hooks blocking update and deletion of non-draft (`Draft`) budgets and their associated lines.
  - In `BudgetService::save()`, disallowed mutating non-draft budgets (`InvalidArgumentException('Only draft budgets can be modified.')`).
- **`app/Modules/Finance/Models/FinanceBankReconciliation.php` & `FinanceBankStatementLine.php`**:
  - Added `booted()` update hooks preventing in-place mutations on completed reconciliations.
  - Added deletion protection on matched bank statement lines belonging to completed reconciliations.
- **`app/Modules/Finance/Services/PostingProfileService.php`**:
  - In `saveRules()`, validated that every `account_role_id` exists in `FinanceAccountRole`, belongs to `$tenantId`, and is active (`is_active = true`).
- **`app/Modules/Finance/Http/Resources/JournalEntryResource.php`**:
  - Robustified `can_reverse` capability check to handle both string and enum representations of `JournalType::Reversal`.

### Test Coverage
- **`app/Modules/Finance/Tests/FinanceHardeningTest.php`**:
  - Expanded test suite to 25 automated tests covering:
    - Reversal of historical journals referencing deactivated posting profiles.
    - Rejection of inactive and non-posting accounts on forward journal creation.
    - Tenant-wide consolidated financial reports aggregating branch ledger entries (Trial Balance, P&L, Balance Sheet, Cash Flow, AR Aging, AP Aging, Tax Summary).
    - Pessimistic row locking on `FinanceAccountBalance`.
    - Lifecycle deletion and mutation guardrails on `FinanceAccount`, `FinanceBudget`, and `FinanceBankReconciliation`.
    - Active account role validation on posting profile rules.

## 4. Verification & Test Results

All quality gates passed with 100% success across both backend and frontend:

- `php artisan test --filter FinanceHardeningTest`: 25 passed (93 assertions).
- `php artisan test --filter FinancePostingProfileScopeTest`: 3 passed (13 assertions).
- `php artisan test --filter Finance`: 108 passed (3,644 assertions).
- `php artisan test --filter ModuleMigrationBaselineTest`: 1 passed (3 assertions).
- `php artisan test --filter HistoricalRetentionMigrationTest`: 1 passed (22 assertions).
- `php artisan test` (Full Repository): 1,501 passed (15,612 assertions).
- `npm test` (Vitest): 181 passed (765 assertions).
- `npm run typecheck`: Passed with 0 errors.
- `npm run lint`: Passed with 0 errors.
- `php vendor/bin/pint --test`: Passed with 0 PSR-12 violations.

## 5. Impact Assessment & Migrations

- **Zero Schema Migrations Required:** All fixes strictly leverage existing relational tables and portable Eloquent lifecycle guardrails.
- **Consolidated Reporting Correctness:** Tenant financial statements now accurately reflect all organization units without dropping branch-specific revenues or expenses.
- **Strict Immutability:** Financial ledgers, master accounts with history, approved budgets, and completed bank reconciliations are protected from accidental deletion or in-place tampering.
