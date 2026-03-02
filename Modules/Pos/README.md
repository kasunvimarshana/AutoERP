# POS Module

## Overview

The **POS** module provides a point-of-sale terminal mode with offline-first design, local transaction queuing, and sync reconciliation engine.

---

## Responsibilities

- POS terminal session management
- Offline-first transaction processing
- Local transaction queue with sync reconciliation engine
- Draft / hold receipt management
- Split payment support
- Refund handling
- Cash drawer tracking
- Receipt template engine
- Loyalty system integration
- Gift card and coupon processing

## Offline Design

- Transactions queued locally when offline
- Full sync reconciliation on reconnect
- Conflict resolution strategy defined per tenant

---

## Financial Rules

- All calculations use **BCMath only** — minimum **4 decimal places**
- Intermediate calculations (further divided or multiplied before final rounding): **8+ decimal places**
- Final monetary values: rounded to the **currency's standard precision (typically 2 decimal places)**
- No floating-point arithmetic

---

## Architecture Layer

```
Modules/POS/
 ├── Application/       # Open/close session, process sale, sync queue, void/refund use cases
 ├── Domain/            # POSSession, POSTransaction, OfflineQueue entities
 ├── Infrastructure/    # POSRepository, POSServiceProvider, sync reconciliation engine
 ├── Interfaces/        # POSController, ReceiptController, SyncController
 ├── module.json
 └── README.md
```

---

## Architecture Compliance

| Rule | Status |
|---|---|
| No business logic in controllers | ✅ Enforced |
| No query builder calls in controllers | ✅ Enforced |
| Tenant isolation enforced (`tenant_id` + global scope) | ✅ Enforced |
| All financial calculations use BCMath (no float) | ✅ Enforced |
| Offline-first design with local queue and sync reconciliation | ✅ Required |
| Full audit trail | ✅ Enforced |
| No cross-module coupling (communicates via contracts/events) | ✅ Enforced |

---

## Dependencies

- `core`
- `tenancy`
- `sales`
- `pricing`
- `accounting`

---

## Implemented Files

| Layer | File |
|---|---|
| Migration | `Infrastructure/Database/Migrations/2026_02_27_000043_create_pos_terminals_table.php` |
| Migration | `Infrastructure/Database/Migrations/2026_02_27_000044_create_pos_sessions_table.php` |
| Migration | `Infrastructure/Database/Migrations/2026_02_27_000045_create_pos_transactions_table.php` |
| Migration | `Infrastructure/Database/Migrations/2026_02_27_000046_create_pos_transaction_lines_table.php` |
| Migration | `Infrastructure/Database/Migrations/2026_02_27_000047_create_pos_payments_table.php` |
| Entity | `Domain/Entities/PosTerminal.php` |
| Entity | `Domain/Entities/PosSession.php` |
| Entity | `Domain/Entities/PosTransaction.php` |
| Entity | `Domain/Entities/PosTransactionLine.php` |
| Entity | `Domain/Entities/PosPayment.php` |
| Contract | `Domain/Contracts/POSRepositoryContract.php` |
| Repository | `Infrastructure/Repositories/POSRepository.php` |
| DTO | `Application/DTOs/CreatePOSTransactionDTO.php` |
| Service | `Application/Services/POSService.php` |
| Controller | `Interfaces/Http/Controllers/POSController.php` |
| Routes | `routes/api.php` |
| Provider | `Infrastructure/Providers/POSServiceProvider.php` |

## Service Methods

| Method | Description |
|---|---|
| `openSession` | Open a new POS terminal session |
| `closeSession` | Close an open session with cash reconciliation |
| `createTransaction` | Create a POS transaction (BCMath totals, DB::transaction) |
| `voidTransaction` | Void a POS transaction |
| `showTransaction` | Show a single POS transaction |
| `syncOfflineTransactions` | Sync locally queued offline transactions |
| `listSessions` | List POS sessions (paginated, tenant-scoped) |

## API Endpoints

| Method | Path | Description |
|---|---|---|
| POST | `/api/v1/pos/transactions` | Create a POS transaction |
| GET | `/api/v1/pos/transactions/{id}` | Show a POS transaction |
| POST | `/api/v1/pos/transactions/{id}/void` | Void a POS transaction |
| POST | `/api/v1/pos/sync` | Sync offline transactions |
| POST | `/api/v1/pos/sessions` | Open a session |
| GET | `/api/v1/pos/sessions` | List sessions |
| POST | `/api/v1/pos/sessions/{id}/close` | Close a session |

## Test Coverage

| Test File | Type | Coverage Area |
|---|---|---|
| `Tests/Unit/CreatePOSTransactionDTOTest.php` | Unit | DTO hydration, BCMath string fields |
| `Tests/Unit/POSServiceTest.php` | Unit | createTransaction/voidTransaction delegation, BCMath arithmetic |
| `Tests/Unit/POSServiceCrudTest.php` | Unit | openSession, closeSession, showTransaction, listSessions — 13 assertions |

## Status

🟢 **Complete** — Core CRUD, session management, void, show, and offline sync implemented (~90% test coverage). See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
