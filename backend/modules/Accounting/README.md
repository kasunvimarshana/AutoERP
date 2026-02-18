# Accounting Module

The Accounting module provides comprehensive financial management capabilities for the AutoERP system, including:

- **Chart of Accounts**: Hierarchical account structure with support for assets, liabilities, equity, revenue, and expenses
- **Journal Entries**: Double-entry bookkeeping with debit/credit validation
- **Invoicing**: Generate invoices from sales orders, track payment status
- **Payments**: Record and allocate payments to invoices

## Features

### Chart of Accounts Management
- Create hierarchical account structures
- Support for multiple account types (Asset, Liability, Equity, Revenue, Expense)
- Multi-currency support
- System accounts protection

### Journal Entry Processing
- Double-entry bookkeeping validation
- Automatic account balance updates
- Post/unpost functionality
- Multi-line entries support

### Invoice Management
- Generate invoices from sales orders
- Automatic invoice numbering
- Multi-currency invoicing
- Track invoice status (Draft, Sent, Paid, Overdue, Cancelled)
- Calculate taxes and discounts

### Payment Processing
- Multiple payment methods (Cash, Bank Transfer, Credit Card, etc.)
- Payment allocation to invoices
- Automatic invoice status updates
- Payment tracking and reconciliation

## API Endpoints

### Accounts
- `GET /api/accounting/accounts` - List all accounts
- `GET /api/accounting/accounts/tree` - Get account hierarchy
- `POST /api/accounting/accounts` - Create new account
- `GET /api/accounting/accounts/{id}` - Get account details
- `PUT /api/accounting/accounts/{id}` - Update account
- `DELETE /api/accounting/accounts/{id}` - Delete account

### Journal Entries
- `GET /api/accounting/journal-entries` - List all entries
- `POST /api/accounting/journal-entries` - Create new entry
- `GET /api/accounting/journal-entries/{id}` - Get entry details
- `PUT /api/accounting/journal-entries/{id}` - Update entry
- `POST /api/accounting/journal-entries/{id}/post` - Post entry
- `DELETE /api/accounting/journal-entries/{id}` - Delete entry

### Invoices
- `GET /api/accounting/invoices` - List all invoices
- `POST /api/accounting/invoices` - Create new invoice
- `POST /api/accounting/invoices/from-order/{orderId}` - Generate from sales order
- `GET /api/accounting/invoices/{id}` - Get invoice details
- `PUT /api/accounting/invoices/{id}` - Update invoice
- `POST /api/accounting/invoices/{id}/send` - Send invoice to customer
- `POST /api/accounting/invoices/{id}/mark-paid` - Mark as paid
- `DELETE /api/accounting/invoices/{id}` - Delete invoice

### Payments
- `GET /api/accounting/payments` - List all payments
- `POST /api/accounting/payments` - Create new payment
- `GET /api/accounting/payments/{id}` - Get payment details
- `PUT /api/accounting/payments/{id}` - Update payment
- `POST /api/accounting/payments/{id}/allocate` - Allocate to invoices
- `POST /api/accounting/payments/{id}/complete` - Mark as completed
- `POST /api/accounting/payments/{id}/cancel` - Cancel payment
- `DELETE /api/accounting/payments/{id}` - Delete payment

## Database Schema

### accounts
- Hierarchical chart of accounts
- Supports parent-child relationships
- Balance tracking per account

### journal_entries
- Main journal entry records
- Tracks posted/unposted status
- Maintains debit/credit totals

### journal_entry_lines
- Individual debit/credit lines
- Links to accounts
- Supports multi-line entries

### invoices
- Customer invoices
- Links to sales orders
- Tracks payment status and amounts

### invoice_items
- Invoice line items
- Links to products
- Tax and discount calculations

### payments
- Payment records
- Multiple payment methods
- Status tracking

### payment_allocations
- Links payments to invoices
- Tracks allocated amounts
- Supports partial payments

## Events

- `InvoiceGenerated` - Fired when invoice is generated from sales order
- `InvoiceSent` - Fired when invoice is sent to customer
- `InvoicePaid` - Fired when invoice is fully paid
- `PaymentReceived` - Fired when payment is recorded
- `PaymentAllocated` - Fired when payment is allocated to invoice

## Business Rules

1. **Double-Entry Validation**: Total debits must equal total credits in journal entries
2. **Account Balance**: Automatically updated when journal entries are posted
3. **Invoice Status**: Automatically updated based on payments received
4. **System Accounts**: Cannot be modified or deleted
5. **Posted Entries**: Cannot be edited once posted
6. **Payment Allocation**: Cannot exceed invoice balance or payment amount
