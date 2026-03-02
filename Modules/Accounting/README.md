# Accounting Module

Double-entry bookkeeping with journal entries, chart of accounts, tax rates and fiscal period management.

## Responsibilities
- Chart of accounts (hierarchical, with normal balance per account type)
- Double-entry journal entries (total debits must equal total credits - enforced via BCMath)
- Trial balance generation
- Tax rate management
- Fiscal period management with close workflow
- Pessimistic locking on accounts during journal posting

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/v1/accounting/accounts | List accounts |
| POST | /api/v1/accounting/accounts | Create account |
| GET | /api/v1/accounting/journal | List journal entries |
| POST | /api/v1/accounting/journal | Post journal entry |
| GET | /api/v1/accounting/trial-balance | Generate trial balance |

## Double-Entry Invariant
Every journal entry is validated before persistence: sum(debits) == sum(credits). Any unbalanced entry throws a DomainException. All arithmetic uses BCMath with scale=4.
