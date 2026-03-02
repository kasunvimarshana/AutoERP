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

## Status

🔴 **Planned** — See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
