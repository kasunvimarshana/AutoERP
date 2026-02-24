# Expense Management Module

## Overview

The Expense Management module handles employee expense claims including categorised line items, BCMath total calculation, and a full approval/reimbursement workflow. It bridges the HR and Accounting modules.

## Features

- **Expense Categories**: Tenant-scoped categories (Travel, Meals, Accommodation, Office Supplies, etc.)
- **Expense Claims**: Employee-submitted claims with multiple line items; BCMath total aggregation ensures financial accuracy.
- **Approval Workflow**: `draft → submitted → approved → reimbursed` (or skipped to rejected).
- **BCMath Totals**: All monetary amounts stored as `DECIMAL(18,8)`; `bcadd` used for line aggregation — no floating-point arithmetic.
- **Tenant Isolation**: Every record is scoped to `tenant_id` via the global `HasTenantScope` trait.

## API Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `api/v1/expense/categories` | List / create expense categories |
| GET/PUT/DELETE | `api/v1/expense/categories/{id}` | Read / update / soft-delete category |
| GET/POST | `api/v1/expense/claims` | List / create expense claims (with lines) |
| GET/DELETE | `api/v1/expense/claims/{id}` | Read / soft-delete claim |
| POST | `api/v1/expense/claims/{id}/submit` | Submit a draft claim for approval |
| POST | `api/v1/expense/claims/{id}/approve` | Approve a submitted claim |
| POST | `api/v1/expense/claims/{id}/reimburse` | Mark an approved claim as reimbursed |

## Domain Events

| Event | Trigger |
|-------|---------|
| `ExpenseClaimSubmitted` | When a claim transitions from `draft` to `submitted` |
| `ExpenseClaimApproved` | When a claim transitions from `submitted` to `approved` |
| `ExpenseClaimReimbursed` | When a claim transitions from `approved` to `reimbursed` |

## Status Lifecycle

```
draft → submitted → approved → reimbursed
```

## Architecture

| Layer | Component |
|-------|-----------|
| Application | `CreateExpenseCategoryUseCase`, `CreateExpenseClaimUseCase`, `SubmitExpenseClaimUseCase`, `ApproveExpenseClaimUseCase`, `ReimburseExpenseClaimUseCase` |
| Domain | `ExpenseStatus` enum, domain events, repository contracts |
| Infrastructure | `ExpenseCategoryModel`, `ExpenseClaimModel`, `ExpenseClaimLineModel`, repositories, migrations |
| Presentation | `ExpenseCategoryController`, `ExpenseClaimController`, form request validators |
