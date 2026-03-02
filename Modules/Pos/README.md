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

## Status

🔴 **Planned** — See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
