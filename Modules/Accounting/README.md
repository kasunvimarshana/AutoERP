# Accounting Module

## Overview

The Accounting module provides a complete double-entry bookkeeping system with Chart of Accounts management, journal entries, and invoicing for the ERP/CRM SaaS platform.

## Features

- **Chart of Accounts** — Hierarchical account structure with types: asset, liability, equity, revenue, expense
- **Double-Entry Journal Entries** — Debit/credit line validation enforcing balanced entries (debits == credits)
- **Invoicing** — Customer and vendor invoices with line-item calculations, status lifecycle, and payment recording
- **BCMath arithmetic** — All monetary calculations use BCMath with DECIMAL(18,8) precision
- **Multi-tenancy** — Every record is tenant-scoped via `HasTenantScope` global scope
- **Domain Events** — `InvoiceCreated`, `InvoicePosted`, `PaymentRecorded`, `JournalEntryPosted`

## API Endpoints

All endpoints are prefixed with `/api/v1` and require `auth:sanctum`.

### Chart of Accounts

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/accounting/accounts` | List accounts (paginated) |
| POST | `/accounting/accounts` | Create account |
| GET | `/accounting/accounts/{id}` | Get account |
| PUT | `/accounting/accounts/{id}` | Update account |
| DELETE | `/accounting/accounts/{id}` | Delete account |

### Journal Entries

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/accounting/journal-entries` | List journal entries (paginated) |
| POST | `/accounting/journal-entries` | Create journal entry (draft) |
| GET | `/accounting/journal-entries/{id}` | Get journal entry with lines |
| DELETE | `/accounting/journal-entries/{id}` | Delete journal entry |
| POST | `/accounting/journal-entries/{id}/post` | Post entry (validates balanced debits/credits) |

### Invoices

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/accounting/invoices` | List invoices (paginated) |
| POST | `/accounting/invoices` | Create invoice |
| GET | `/accounting/invoices/{id}` | Get invoice |
| DELETE | `/accounting/invoices/{id}` | Delete invoice |
| POST | `/accounting/invoices/{id}/post` | Post invoice (draft → sent) |
| POST | `/accounting/invoices/{id}/payments` | Record payment against invoice |

## Architecture

Follows strict Clean Architecture:

- **Domain** — Pure PHP entities, enums, contracts, and domain events (no framework dependency)
- **Application** — Use cases orchestrate business logic within `DB::transaction()`, emit domain events
- **Infrastructure** — Eloquent models with `HasTenantScope`, repositories extending `BaseEloquentRepository`
- **Presentation** — Form Requests for validation, Controllers using `ResponseFormatter`, no business logic

## Business Rules

- Journal entries must have balanced debits and credits before posting
- Payments cannot exceed the invoice's remaining `amount_due`
- Paid/cancelled invoices cannot receive additional payments
- All monetary values use BCMath arithmetic — floating-point is forbidden
- Auto-generated sequential numbers: `JE-000001`, `INV-000001`
