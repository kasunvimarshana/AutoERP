# Accounting Module

Double-entry bookkeeping with Chart of Accounts and Journal Entries for the KV Enterprise Dynamic SaaS CRM/ERP platform.

## Overview

The Accounting module provides a complete double-entry bookkeeping system, including:

- **Chart of Accounts** — hierarchical account structure with five account types
- **Journal Entries** — double-entry transactions with draft/post workflow

## Architecture

Follows the platform's **Controller → Service → Handler → Repository → Entity** pattern.

## Account Types

| Type       | Normal Balance |
|------------|---------------|
| Asset      | Debit         |
| Expense    | Debit         |
| Liability  | Credit        |
| Equity     | Credit        |
| Revenue    | Credit        |

## API Endpoints

### Chart of Accounts

| Method | Endpoint               | Description          |
|--------|------------------------|----------------------|
| GET    | /api/v1/accounts       | List accounts        |
| POST   | /api/v1/accounts       | Create account       |
| GET    | /api/v1/accounts/{id}  | Get account by ID    |
| PUT    | /api/v1/accounts/{id}  | Update account       |
| DELETE | /api/v1/accounts/{id}  | Delete account       |

### Journal Entries

| Method | Endpoint                                    | Description            |
|--------|---------------------------------------------|------------------------|
| GET    | /api/v1/accounting/journal-entries          | List journal entries   |
| POST   | /api/v1/accounting/journal-entries          | Create journal entry   |
| GET    | /api/v1/accounting/journal-entries/{id}     | Get journal entry      |
| POST   | /api/v1/accounting/journal-entries/{id}/post | Post journal entry    |
| DELETE | /api/v1/accounting/journal-entries/{id}     | Delete draft entry     |

## Journal Entry Workflow

1. Create a journal entry (status: `draft`)
2. Entry must have at least 2 lines with `total_debit == total_credit`
3. Post the entry (status: `posted`) — updates account balances
4. Posted entries cannot be deleted

## Financial Precision

All monetary values use BCMath with 4 decimal places.
