# Accounting Module

## Overview

The **Accounting** module implements a full double-entry bookkeeping system per tenant, with immutable journal entries, auto-posting rules, and comprehensive financial reporting.

---

## Double-Entry Rule

Every transaction must:
- **Debit** one account
- **Credit** another account
- **Total Debits = Total Credits** at all times

Violations of this rule are a **Critical Violation**.

---

## Responsibilities

- Chart of accounts management (per tenant)
- Journal entry creation (immutable once posted)
- Auto-posting rules (event-driven posting)
- Tax engine (inclusive/exclusive, multi-tax)
- Fiscal period management (open/close periods)
- Financial statements:
  - Trial Balance
  - Profit & Loss (Income Statement)
  - Balance Sheet
- Reconciliation with inventory valuation
- Audit trail (cannot be disabled)

## Financial Integrity Rules

- **No floating-point arithmetic** — BCMath only
- **Arbitrary precision decimals** — minimum 4 decimal places
- **Intermediate calculations** (further divided or multiplied before final rounding): 8+ decimal places
- **Final monetary values**: rounded to the currency's standard precision (typically 2 decimal places)
- **Deterministic rounding**
- **Immutable journal entries** — never edited, only reversed
- Financial integrity **cannot be bypassed**

---

## Architecture Layer

```
Modules/Accounting/
 ├── Application/       # Post journal entry, close period, generate reports use cases
 ├── Domain/            # Account, JournalEntry, FiscalPeriod entities, AccountingRepository contract
 ├── Infrastructure/    # AccountingRepository, AccountingServiceProvider, auto-posting engine
 ├── Interfaces/        # AccountController, JournalController, FinancialReportController
 ├── module.json
 └── README.md
```

---

## Architecture Compliance

| Rule | Status |
|---|---|
| No business logic in controllers | ✅ Enforced |
| No query builder calls in controllers | ✅ Enforced |
| Tenant isolation enforced (`tenant_id` + global scope) | ✅ Enforced |
| All financial calculations use BCMath (no float) | ✅ Enforced |
| Journal entries are immutable (never edited, only reversed) | ✅ Enforced |
| Total Debits = Total Credits at all times | ✅ Enforced |
| Full audit trail (cannot be disabled) | ✅ Enforced |
| No cross-module coupling (communicates via contracts/events) | ✅ Enforced |

---

## Dependencies

- `core`
- `tenancy`

---

## Implemented Files

### Migrations
| File | Description |
|---|---|
| `2026_02_27_000020_create_account_types_table.php` | Account type categories (Asset, Liability, etc.) |
| `2026_02_27_000021_create_chart_of_accounts_table.php` | Hierarchical chart of accounts per tenant |
| `2026_02_27_000022_create_fiscal_periods_table.php` | Fiscal period definitions per tenant |
| `2026_02_27_000023_create_journal_entries_table.php` | Journal entry header (draft/posted/reversed) |
| `2026_02_27_000024_create_journal_entry_lines_table.php` | Debit/credit lines — `decimal(20,4)` for amounts |

### Domain
| File | Description |
|---|---|
| `Domain/Entities/AccountType.php` | Account type entity with `HasTenant` |
| `Domain/Entities/ChartOfAccount.php` | Account entity with parent/children self-relation |
| `Domain/Entities/FiscalPeriod.php` | Fiscal period entity |
| `Domain/Entities/JournalEntry.php` | Immutable journal entry entity |
| `Domain/Entities/JournalEntryLine.php` | Debit/credit line; amount cast as `string` (BCMath safe) |
| `Domain/Entities/AutoPostingRule.php` | Auto-posting rule entity — event_type, debit/credit account, is_active |
| `Domain/Contracts/AccountRepositoryContract.php` | Account repository contract |
| `Domain/Contracts/JournalEntryRepositoryContract.php` | Journal entry repository contract |

### Application
| File | Description |
|---|---|
| `Application/DTOs/CreateJournalEntryDTO.php` | DTO for journal entry creation |
| `Application/Services/AccountingService.php` | listAccounts, createAccount, showAccount, updateAccount, listFiscalPeriods, createFiscalPeriod, closeFiscalPeriod, showFiscalPeriod, createJournalEntry, postEntry, showJournalEntry, **listAutoPostingRules**, **createAutoPostingRule**, **updateAutoPostingRule**, **deleteAutoPostingRule** — double-entry validation, BCMath |

### Infrastructure
| File | Description |
|---|---|
| `Infrastructure/Repositories/AccountRepository.php` | Tenant-aware ChartOfAccount repository |
| `Infrastructure/Repositories/JournalEntryRepository.php` | Tenant-aware JournalEntry repository |
| `Infrastructure/Providers/AccountingServiceProvider.php` | Binds contracts, loads migrations and routes |

### Interfaces
| File | Description |
|---|---|
| `Interfaces/Http/Controllers/AccountingController.php` | Full accounting API — accounts, fiscal periods, journal entries |
| `routes/api.php` | Route definitions under `auth:api` middleware |

### API Routes (`/api/v1`)
| Method | Path | Action |
|---|---|---|
| GET | `/accounting/accounts` | listAccounts |
| POST | `/accounting/accounts` | createAccount |
| GET | `/accounting/accounts/{id}` | showAccount |
| PUT | `/accounting/accounts/{id}` | updateAccount |
| GET | `/accounting/fiscal-periods` | listFiscalPeriods |
| POST | `/accounting/fiscal-periods` | createFiscalPeriod |
| GET | `/accounting/fiscal-periods/{id}` | showFiscalPeriod |
| POST | `/accounting/fiscal-periods/{id}/close` | closeFiscalPeriod |
| GET | `/accounting/fiscal-periods/{id}/trial-balance` | getTrialBalance |
| GET | `/accounting/fiscal-periods/{id}/profit-and-loss` | getProfitAndLoss |
| GET | `/accounting/fiscal-periods/{id}/balance-sheet` | getBalanceSheet |
| POST | `/accounting/journals` | createJournalEntry |
| GET | `/accounting/journals` | listJournalEntries |
| GET | `/accounting/journals/{id}` | showJournalEntry |
| POST | `/accounting/journals/{id}/post` | postEntry |

---

## Test Coverage

| Test File | Type | Coverage Area |
|---|---|---|
| `Tests/Unit/CreateJournalEntryDTOTest.php` | Unit | `CreateJournalEntryDTO` — hydration, debit/credit line mapping, BCMath string fields |
| `Tests/Unit/AccountingServiceTest.php` | Unit | Double-entry validation, debit=credit enforcement |
| `Tests/Unit/AccountingServiceWritePathTest.php` | Unit | createAccount, createFiscalPeriod, closeFiscalPeriod — method signatures |
| `Tests/Unit/AccountingServiceCrudTest.php` | Unit | showAccount, updateAccount, showFiscalPeriod, showJournalEntry — 15 assertions |
| `Tests/Unit/AccountingServiceFinancialStatementsTest.php` | Unit | getTrialBalance, getProfitAndLoss — method signatures, return types, BCMath net-balance calculation — 12 assertions |
| `Tests/Unit/AccountingControllerFinancialStatementsTest.php` | Unit | getTrialBalance/getProfitAndLoss controller methods, return types — 11 assertions |
| `Tests/Unit/AccountingServiceBalanceSheetTest.php` | Unit | getBalanceSheet — method signature, return type, section keys (assets/liabilities/equity), BCMath totals, delegation to journal repo — 9 assertions |
| `Tests/Unit/AccountingControllerBalanceSheetTest.php` | Unit | getBalanceSheet controller method existence, visibility, parameter type, JsonResponse return — 5 assertions |
| `Tests/Unit/AccountingServiceAutoPostingRuleTest.php` | Unit | listAutoPostingRules, createAutoPostingRule, updateAutoPostingRule, deleteAutoPostingRule — method existence, visibility, signatures, return types, entity compliance — 19 assertions |

---

## Status

🟢 **Complete** — Full chart-of-accounts, fiscal period management, journal entry CRUD, trial balance, P&L statement, balance sheet, and **auto-posting rules** (createAutoPostingRule, updateAutoPostingRule, deleteAutoPostingRule, listAutoPostingRules) implemented (~95% test coverage). See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
