# Accounting Module

## Overview

The Accounting module provides comprehensive double-entry bookkeeping, financial reporting, and accounting management capabilities for the enterprise ERP/CRM system. It follows clean architecture principles with strict separation of concerns and maintains complete tenant isolation.

## Features

- **Chart of Accounts**: Hierarchical account structure with support for multiple account types
- **General Ledger**: Double-entry bookkeeping with journal entries and automated posting
- **Fiscal Periods**: Period-based accounting with open, closed, and locked states
- **Financial Reporting**: 
  - Trial Balance
  - Balance Sheet
  - Income Statement
  - Cash Flow Statement
  - Account Ledger
- **Multi-Currency Support**: Ready for multi-currency transactions (future enhancement)
- **Audit Trail**: Complete audit logging of all accounting transactions

## Architecture

### Models

- **Account**: Chart of accounts with hierarchical parent-child relationships
- **JournalEntry**: Double-entry journal entries with source tracking
- **JournalLine**: Individual debit/credit lines within journal entries
- **FiscalPeriod**: Accounting periods within fiscal years
- **FiscalYear**: Annual fiscal periods

### Services

- **AccountingService**: Core accounting operations and account management
- **GeneralLedgerService**: Journal entry management, posting, and reversal
- **ChartOfAccountsService**: Account hierarchy and structure management
- **TrialBalanceService**: Trial balance report generation
- **FinancialStatementService**: Financial statement generation

### Repositories

- **AccountRepository**: Account data access and queries
- **JournalEntryRepository**: Journal entry data access
- **FiscalPeriodRepository**: Fiscal period management

## API Endpoints

### Accounts

```
GET    /api/accounting/accounts              - List accounts
POST   /api/accounting/accounts              - Create account
GET    /api/accounting/accounts/{id}         - Get account
PUT    /api/accounting/accounts/{id}         - Update account
DELETE /api/accounting/accounts/{id}         - Delete account
GET    /api/accounting/accounts/{id}/balance - Get account balance
```

### Journal Entries

```
GET    /api/accounting/journal-entries               - List entries
POST   /api/accounting/journal-entries               - Create entry
GET    /api/accounting/journal-entries/{id}          - Get entry
PUT    /api/accounting/journal-entries/{id}          - Update entry
DELETE /api/accounting/journal-entries/{id}          - Delete entry
POST   /api/accounting/journal-entries/{id}/post     - Post entry
POST   /api/accounting/journal-entries/{id}/reverse  - Reverse entry
```

### Fiscal Periods

```
GET    /api/accounting/fiscal-periods               - List periods
POST   /api/accounting/fiscal-periods               - Create period
GET    /api/accounting/fiscal-periods/{id}          - Get period
PUT    /api/accounting/fiscal-periods/{id}          - Update period
DELETE /api/accounting/fiscal-periods/{id}          - Delete period
POST   /api/accounting/fiscal-periods/{id}/close    - Close period
POST   /api/accounting/fiscal-periods/{id}/reopen   - Reopen period
POST   /api/accounting/fiscal-periods/{id}/lock     - Lock period
```

### Reports

```
GET /api/accounting/reports/chart-of-accounts   - Chart of accounts
GET /api/accounting/reports/trial-balance       - Trial balance
GET /api/accounting/reports/balance-sheet       - Balance sheet
GET /api/accounting/reports/income-statement    - Income statement
GET /api/accounting/reports/cash-flow-statement - Cash flow statement
GET /api/accounting/reports/account-ledger      - Account ledger
```

## Configuration

Configuration is located in `Config/accounting.php`:

- **Account Code Generation**: Prefix and length settings
- **Journal Entry Settings**: Numbering, backdating, approval requirements
- **Fiscal Period Settings**: Year start month, period closing rules
- **Decimal Precision**: Calculation and display precision (BCMath)
- **Financial Statements**: Report formatting and options
- **Integration Settings**: Auto-posting from Sales, Purchase, Inventory

## Events

- `AccountCreated`: Fired when an account is created
- `AccountUpdated`: Fired when an account is updated
- `JournalEntryCreated`: Fired when a journal entry is created
- `JournalEntryPosted`: Fired when a journal entry is posted
- `JournalEntryReversed`: Fired when a journal entry is reversed
- `FiscalPeriodCreated`: Fired when a fiscal period is created
- `FiscalPeriodClosed`: Fired when a fiscal period is closed
- `FiscalPeriodReopened`: Fired when a fiscal period is reopened

## Permissions

- `accounts.view`: View accounts
- `accounts.create`: Create accounts
- `accounts.update`: Update accounts
- `accounts.delete`: Delete accounts
- `journal_entries.view`: View journal entries
- `journal_entries.create`: Create journal entries
- `journal_entries.update`: Update journal entries
- `journal_entries.delete`: Delete journal entries
- `journal_entries.post`: Post journal entries
- `journal_entries.reverse`: Reverse journal entries
- `fiscal_periods.view`: View fiscal periods
- `fiscal_periods.create`: Create fiscal periods
- `fiscal_periods.update`: Update fiscal periods
- `fiscal_periods.delete`: Delete fiscal periods
- `fiscal_periods.close`: Close fiscal periods
- `fiscal_periods.reopen`: Reopen fiscal periods
- `fiscal_periods.lock`: Lock fiscal periods

## Usage Examples

### Create Account

```json
POST /api/accounting/accounts
{
  "code": "1000",
  "name": "Cash in Bank",
  "type": "asset",
  "normal_balance": "debit",
  "is_bank_account": true,
  "is_reconcilable": true
}
```

### Create Journal Entry

```json
POST /api/accounting/journal-entries
{
  "entry_date": "2024-01-15",
  "description": "Sales invoice payment received",
  "lines": [
    {
      "account_id": "cash-account-id",
      "description": "Cash received",
      "debit": "1000.00",
      "credit": "0.00"
    },
    {
      "account_id": "accounts-receivable-id",
      "description": "Accounts receivable",
      "debit": "0.00",
      "credit": "1000.00"
    }
  ]
}
```

### Post Journal Entry

```json
POST /api/accounting/journal-entries/{id}/post
```

### Generate Trial Balance

```json
GET /api/accounting/reports/trial-balance?organization_id={id}&start_date=2024-01-01&end_date=2024-12-31
```

## Technical Details

- **Precision**: All financial calculations use BCMath with configurable precision (default 6 decimals)
- **Transaction Safety**: All mutations are wrapped in database transactions with retry logic
- **Tenant Isolation**: Strict tenant-level data isolation using TenantScoped trait
- **Audit Logging**: Comprehensive audit trail using Auditable trait
- **Validation**: Automatic balance validation for journal entries
- **Period Control**: Posting restrictions based on fiscal period status

## Dependencies

- Core Module: MathHelper, TransactionHelper, BaseRepository
- Tenant Module: Multi-tenancy support
- Auth Module: Authentication and authorization
- Audit Module: Audit logging

## Integration

The Accounting module integrates with:

- **Sales Module**: Auto-posting of invoices and payments
- **Purchase Module**: Auto-posting of bills and payments
- **Inventory Module**: Auto-posting of stock movements and valuations

## Future Enhancements

- Multi-currency support with exchange rates
- Budget management and variance analysis
- Bank reconciliation
- Fixed assets management
- Cost center and project accounting
- Tax management and reporting
- Automated recurring journal entries
- Financial consolidation
