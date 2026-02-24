# Budget Management Module

Departmental and project budget planning with line-level category tracking, BCMath financial accuracy, and draft/approved/closed lifecycle.

## Features

- **Budgets** — create budgets with period (monthly/quarterly/annually), date range, and line items by category
- **Budget Lines** — per-category planned vs. actual spend tracked with `DECIMAL(18,8)` BCMath precision
- **Approval Workflow** — `draft → approved → closed`; only draft budgets can be approved
- **Close Guard** — already-closed budgets cannot be closed again
- Tenant-isolated; all queries scoped via `HasTenantScope`
- All amounts stored as `DECIMAL(18,8)`; `bcadd` normalisation throughout

## Status Lifecycles

### Budget
`draft` → `approved` → `closed`

## API Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `api/v1/budget/budgets` | List / create budgets (with lines) |
| GET/PUT/DELETE | `api/v1/budget/budgets/{id}` | Read / update / soft-delete budget |
| POST | `api/v1/budget/budgets/{id}/approve` | Approve a draft budget |
| POST | `api/v1/budget/budgets/{id}/close` | Close an approved budget |

## Domain Events

| Event | Trigger |
|-------|---------|
| `BudgetCreated` | New budget created with draft status |
| `BudgetApproved` | Budget approved by a reviewer |
| `BudgetClosed` | Budget closed at period end |

## Dependencies

- Tenant, User, Accounting
