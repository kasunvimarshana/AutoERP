# Leave Management Module

## Overview

The Leave Management module provides employee leave allocation, request handling, and approval workflow. It covers leave type definitions, per-employee entitlement (allocation) tracking, request submission, and manager approval/rejection. It integrates with the HR module for employee records.

## Features

- **Leave Types**: Define named leave categories (Annual, Sick, Unpaid, etc.) with optional maximum-day limits and paid/unpaid flags.
- **Leave Allocations**: Grant employees a quota of days for a given leave type and period (`draft → approved`). Tracks `total_days`, `used_days`, and implicit remaining balance using BCMath. Optional `valid_from`/`valid_to` date ranges.
- **Balance Enforcement**: `RequestLeaveUseCase` checks the approved allocation balance (BCMath scale 2) before creating a draft request, preventing over-allocation.
- **Leave Requests**: Employees submit requests specifying leave type, date range, and duration in days.
- **Approval Workflow**: Managers approve or reject draft requests; domain events are emitted on both transitions.
- **Tenant Isolation**: Every record is scoped to `tenant_id` via the global `HasTenantScope` trait.

## API Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| GET/POST | `api/v1/leave/types` | List / create leave types |
| GET/PUT/DELETE | `api/v1/leave/types/{id}` | Read / update / soft-delete leave type |
| GET/POST | `api/v1/leave/allocations` | List / create leave allocations |
| GET/DELETE | `api/v1/leave/allocations/{id}` | Read / soft-delete allocation |
| POST | `api/v1/leave/allocations/{id}/approve` | Approve a draft allocation |
| GET/POST | `api/v1/leave/requests` | List / create leave requests |
| GET/DELETE | `api/v1/leave/requests/{id}` | Read / soft-delete leave request |
| POST | `api/v1/leave/requests/{id}/approve` | Approve a draft leave request |
| POST | `api/v1/leave/requests/{id}/reject` | Reject a draft leave request |

## Domain Events

| Event | Trigger |
|-------|---------|
| `LeaveAllocated` | When a leave allocation is created (draft) |
| `LeaveAllocationApproved` | When an allocation transitions from `draft` to `approved` |
| `LeaveRequestApproved` | When a leave request transitions from `draft` to `approved` |
| `LeaveRequestRejected` | When a leave request transitions from `draft` to `rejected` |

## Status Lifecycles

```
# Allocation
draft → approved
approved (may be transitioned to expired by a scheduled command)

# Leave Request
draft → approved
draft → rejected
```

## Architecture

| Layer | Component |
|-------|-----------|
| Application | `CreateLeaveTypeUseCase`, `CreateLeaveAllocationUseCase`, `ApproveLeaveAllocationUseCase`, `DeductLeaveAllocationUseCase`, `RequestLeaveUseCase` (with optional balance check), `ApproveLeaveRequestUseCase`, `RejectLeaveRequestUseCase` |
| Domain | `LeaveStatus` / `LeaveAllocationStatus` enums; `LeaveAllocated`, `LeaveAllocationApproved`, `LeaveRequestApproved`, `LeaveRequestRejected` events; repository contracts |
| Infrastructure | `LeaveTypeModel`, `LeaveAllocationModel`, `LeaveRequestModel`, repositories, migrations |
| Presentation | `LeaveTypeController`, `LeaveAllocationController`, `LeaveRequestController`, form request validators |

## Integration Notes

- `LeaveAllocationApproved` event can trigger Notification dispatches (entitlement grant notification to employee).
- `LeaveRequestApproved` event should trigger `DeductLeaveAllocationUseCase` to deduct `days_requested` from the employee's active allocation.
- A scheduled command can scan allocations where `valid_to < now()` and transition `approved → expired`.
