# Contracts Module

## Overview

The Contracts module provides full contract lifecycle management. It enables tenant-scoped creation, activation, and termination of business contracts with parties (customers, vendors, partners), BCMath-accurate total values, payment terms, and domain event emission.

---

## Features

- **Contract CRUD** – Create, read, update, delete contracts per tenant
- **Lifecycle management** – `draft → active → terminated / expired`
- **BCMath financial values** – `total_value` stored as `DECIMAL(18,8)`, all arithmetic via `bcadd`
- **Party details** – Name, email, reference for the contracting party
- **Payment terms** – Free-text payment terms field
- **Domain events** – `ContractCreated`, `ContractActivated`, `ContractTerminated`
- **Tenant isolation** – Global tenant scope via `HasTenantScope` trait

---

## Status Lifecycle

```
draft ──activate──► active ──terminate──► terminated
                         └──(auto/cron)──► expired
```

- **Activate guard**: only `draft` contracts can be activated
- **Terminate guard**: `terminated` and `expired` contracts cannot be terminated again

---

## API Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| GET | `api/v1/contracts/contracts` | List tenant contracts |
| POST | `api/v1/contracts/contracts` | Create a new contract |
| GET | `api/v1/contracts/contracts/{id}` | Get contract details |
| PUT | `api/v1/contracts/contracts/{id}` | Update contract |
| DELETE | `api/v1/contracts/contracts/{id}` | Soft-delete contract |
| POST | `api/v1/contracts/contracts/{id}/activate` | Activate a draft contract |
| POST | `api/v1/contracts/contracts/{id}/terminate` | Terminate a contract |

---

## Domain Events

| Event | Payload | Trigger |
|-------|---------|---------|
| `ContractCreated` | `contractId`, `tenantId`, `title` | New contract created |
| `ContractActivated` | `contractId`, `tenantId` | Contract moved to active |
| `ContractTerminated` | `contractId`, `tenantId`, `reason` | Contract terminated |

---

## Architecture

- **Domain**: `ContractStatus` enum, `ContractType` enum, `ContractRepositoryInterface`, domain events
- **Application**: `CreateContractUseCase`, `ActivateContractUseCase`, `TerminateContractUseCase`
- **Infrastructure**: `ContractModel` (SoftDeletes + HasTenantScope), migration, `ContractRepository`
- **Presentation**: `ContractController`, `StoreContractRequest`

---

## Integration Notes

- `ContractCreated` can trigger Notification dispatch (welcome/acknowledgement to party)
- `ContractActivated` can trigger Accounting journal entry for contract revenue recognition
- `ContractTerminated` can trigger Audit module logging and Notification to party
- A scheduled command can scan `end_date < now()` and transition `active → expired`
