# Accounting & Finance Module

---

**⚠️ IMPLEMENTATION PRINCIPLE**: Rely strictly on native Laravel and Vue features. Always implement functionality manually instead of using third-party libraries.

---


A comprehensive accounting and finance module for the multi-tenant ERP/CRM system, providing double-entry bookkeeping, invoicing, payment processing, and financial reporting capabilities.

## Features

### Core Functionality
- **Chart of Accounts**: Hierarchical account structure with support for multiple account types
- **Double-Entry Bookkeeping**: Automatic balance validation for all journal entries
- **Invoice Management**: Create, send, and track customer invoices
- **Payment Processing**: Record and reconcile customer payments
- **Fiscal Period Management**: Control accounting periods with open/close functionality
- **Multi-Currency Support**: Handle transactions in multiple currencies
- **Tax Calculations**: Automatic tax computation on invoices

### Key Capabilities
- ✅ Hierarchical chart of accounts (Assets, Liabilities, Equity, Revenue, Expenses)
- ✅ Journal entries with automatic balance validation
- ✅ Customer invoicing with line items
- ✅ Payment recording and application to invoices
- ✅ Invoice aging reports
- ✅ Fiscal period controls
- ✅ Auto-numbering for invoices, payments, and journal entries
- ✅ Multi-tenant isolation
- ✅ Audit trails for all transactions
- ✅ Integration with Sales module

## Architecture

### Entities (7)
1. **Account** - Chart of accounts with hierarchical structure
2. **JournalEntry** - Double-entry journal entries
3. **JournalEntryLine** - Individual debit/credit lines
4. **Invoice** - Customer invoices
5. **InvoiceLine** - Invoice line items
6. **Payment** - Payment records
7. **FiscalPeriod** - Accounting periods

### Services (4)
- **AccountService** - Chart of accounts management
- **JournalEntryService** - Journal entry operations with balance validation
- **InvoiceService** - Invoice generation and management
- **PaymentService** - Payment processing and reconciliation

### API Endpoints (40+)

#### Accounts
- `GET /api/accounting/v1/accounts` - List accounts
- `POST /api/accounting/v1/accounts` - Create account
- `GET /api/accounting/v1/accounts/{id}` - Get account details
- `PUT /api/accounting/v1/accounts/{id}` - Update account
- `DELETE /api/accounting/v1/accounts/{id}` - Delete account
- `GET /api/accounting/v1/accounts/chart-of-accounts` - Get hierarchical chart
- `GET /api/accounting/v1/accounts/by-type/{type}` - Get accounts by type
- `GET /api/accounting/v1/accounts/search` - Search accounts

#### Journal Entries
- `GET /api/accounting/v1/journal-entries` - List entries
- `POST /api/accounting/v1/journal-entries` - Create entry
- `GET /api/accounting/v1/journal-entries/{id}` - Get entry details
- `PUT /api/accounting/v1/journal-entries/{id}` - Update entry
- `DELETE /api/accounting/v1/journal-entries/{id}` - Delete entry
- `POST /api/accounting/v1/journal-entries/{id}/post` - Post entry
- `POST /api/accounting/v1/journal-entries/{id}/reverse` - Reverse entry
- `GET /api/accounting/v1/journal-entries/{id}/check-balance` - Validate balance

#### Invoices
- `GET /api/accounting/v1/invoices` - List invoices
- `POST /api/accounting/v1/invoices` - Create invoice
- `GET /api/accounting/v1/invoices/{id}` - Get invoice details
- `PUT /api/accounting/v1/invoices/{id}` - Update invoice
- `DELETE /api/accounting/v1/invoices/{id}` - Delete invoice
- `POST /api/accounting/v1/invoices/{id}/send` - Send invoice to customer
- `POST /api/accounting/v1/invoices/{id}/mark-paid` - Mark as paid
- `GET /api/accounting/v1/invoices/overdue` - Get overdue invoices
- `GET /api/accounting/v1/invoices/aging-report` - Get aging report

#### Payments
- `GET /api/accounting/v1/payments` - List payments
- `POST /api/accounting/v1/payments` - Create payment
- `GET /api/accounting/v1/payments/{id}` - Get payment details
- `PUT /api/accounting/v1/payments/{id}` - Update payment
- `DELETE /api/accounting/v1/payments/{id}` - Delete payment
- `POST /api/accounting/v1/payments/{id}/apply-to-invoice` - Apply to invoice
- `POST /api/accounting/v1/payments/{id}/process` - Process payment

#### Fiscal Periods
- `GET /api/accounting/v1/fiscal-periods` - List periods
- `POST /api/accounting/v1/fiscal-periods` - Create period
- `GET /api/accounting/v1/fiscal-periods/{id}` - Get period details
- `PUT /api/accounting/v1/fiscal-periods/{id}` - Update period
- `DELETE /api/accounting/v1/fiscal-periods/{id}` - Delete period
- `POST /api/accounting/v1/fiscal-periods/{id}/open` - Open period
- `POST /api/accounting/v1/fiscal-periods/{id}/close` - Close period
- `GET /api/accounting/v1/fiscal-periods/current` - Get current period

## Installation

1. The module is already included in the project structure
2. Run migrations:
   ```bash
   php artisan migrate
   ```

3. Seed the chart of accounts:
   ```bash
   php artisan db:seed --class=Modules\\Accounting\\Database\\Seeders\\AccountingSeeder
   ```

## Usage Examples

### Create an Invoice

```php
POST /api/accounting/v1/invoices
{
  "customer_id": 1,
  "invoice_date": "2024-02-09",
  "due_date": "2024-03-09",
  "currency": "USD",
  "lines": [
    {
      "account_id": 4100,
      "description": "Consulting Services",
      "quantity": 10,
      "unit_price": 150.00,
      "tax_rate": 10
    }
  ]
}
```

### Record a Payment

```php
POST /api/accounting/v1/payments
{
  "customer_id": 1,
  "invoice_id": 123,
  "payment_date": "2024-02-09",
  "amount": 1650.00,
  "currency": "USD",
  "payment_method": "bank_transfer",
  "reference": "TXN-12345"
}
```

### Create a Journal Entry

```php
POST /api/accounting/v1/journal-entries
{
  "entry_date": "2024-02-09",
  "description": "Rent payment for February",
  "currency": "USD",
  "lines": [
    {
      "account_id": 5220,
      "description": "Rent Expense",
      "debit_amount": 2000.00,
      "credit_amount": 0
    },
    {
      "account_id": 1110,
      "description": "Cash",
      "debit_amount": 0,
      "credit_amount": 2000.00
    }
  ]
}
```

## Business Logic

### Double-Entry Validation
All journal entries must be balanced (total debits = total credits). The system automatically validates this before posting.

### Automatic Journal Entries
The system automatically creates journal entries when:
- An invoice is sent (DR: Accounts Receivable, CR: Revenue)
- A payment is received (DR: Cash/Bank, CR: Accounts Receivable)

### Fiscal Period Controls
- Journal entries can only be posted to open fiscal periods
- Closed periods prevent new entries
- Locked periods cannot be reopened

### Invoice Aging
The system automatically tracks invoice aging in buckets:
- Current (not yet due)
- 1-30 days overdue
- 31-60 days overdue
- 61-90 days overdue
- Over 90 days overdue

## Database Schema

### Key Tables
- `accounts` - Chart of accounts
- `journal_entries` - Journal entry headers
- `journal_entry_lines` - Journal entry lines (debits/credits)
- `invoices` - Customer invoices
- `invoice_lines` - Invoice line items
- `payments` - Payment records
- `fiscal_periods` - Accounting periods

All tables include:
- Multi-tenant isolation (`tenant_id`)
- UUID support
- Soft deletes
- Audit timestamps
- Proper indexes for performance

## Events

- `InvoiceCreated` - Fired when an invoice is created
- `PaymentReceived` - Fired when a payment is processed
- `JournalEntryPosted` - Fired when an entry is posted
- `FiscalPeriodClosed` - Fired when a period is closed

## Testing

Run module tests:
```bash
php artisan test --testsuite=Accounting
```

## Integration

### With Sales Module
- Invoices can be linked to customers from the Sales module
- Automatic revenue recognition for sales orders

### With Inventory Module
- Invoice lines can reference products from Inventory
- Cost of goods sold tracking

## Configuration

Configuration file: `config/accounting.php`

Key settings:
- Default currency
- Auto-numbering prefixes
- Default payment terms
- Invoice reminder days

## Dependencies

- Laravel 11.x
- PHP 8.2+
- PostgreSQL
- Sales module (for customer integration)
- Core module (for base services and traits)

## Security

- All operations require authentication
- Multi-tenant data isolation
- Audit trails on all financial transactions
- System accounts protected from deletion
- Posted entries protected from modification

## Performance

- Indexed foreign keys and date columns
- Efficient queries with eager loading
- Repository pattern for data access
- Database transactions for consistency

## Compliance

- Double-entry bookkeeping principles
- GAAP-compatible chart of accounts structure
- Audit trail requirements
- Period-end controls

## License

This module is part of the AutoERP project.
