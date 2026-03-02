# Inventory Module

## Overview

The **Inventory** module implements a **ledger-driven, transactional, immutable** inventory management system. Stock is never edited directly — all changes occur via transactions logged in the stock ledger.

This module handles both standard inventory management and **pharmaceutical compliance mode**. When pharmaceutical mode is enabled for a tenant, all pharmaceutical-specific rules become mandatory and cannot be bypassed.

---

## Core Rules

- Stock is **never edited directly**
- All stock changes occur via **transactions**
- Reservation **precedes** deduction
- Deduction must be **atomic**
- Historical ledger entries are **immutable**

---

## Supported Flows

| Flow | Description |
|---|---|
| Purchase Receipt | Stock in from supplier |
| Sales Shipment | Stock out to customer |
| Internal Transfer | Move stock between locations |
| Adjustment | Manual correction with reason |
| Return | Customer/supplier returns |

---

## Responsibilities

- Stock ledger (immutable transaction log)
- Stock deduction with pessimistic locking
- Stock reservation system
- FIFO / LIFO / Weighted Average costing
- Multi-warehouse support
- Multi-bin location tracking
- Expiry date tracking
- Serial / Batch / Lot traceability (optional in standard mode; mandatory in pharmaceutical mode)
- Cycle counting
- Reorder rules and procurement suggestions
- Backorder management
- Drop-shipping support
- Damage handling and quarantine workflows
- Idempotent stock APIs

---

## Pharmaceutical Compliance Mode

When pharmaceutical compliance mode is enabled for a tenant, the following rules are enforced and **cannot be bypassed**:

| Rule | Enforcement |
|---|---|
| Lot tracking | Mandatory |
| Expiry date tracking | Mandatory |
| FEFO (First-Expired, First-Out) | Strictly enforced (overrides FIFO/LIFO) |
| Serial tracking (where applicable) | Required |
| Audit trail | Cannot be disabled |
| Quarantine for expired/recalled items | Enforced |

### Regulatory Compliance

- **FDA** (Food and Drug Administration) alignment
- **DEA** (Drug Enforcement Administration) alignment
- **DSCSA** (Drug Supply Chain Security Act) adherence
- Drug serial number tracking
- Tamper-resistant audit logs
- Expiry override logging (with justification)
- High-risk medication access logging

### Pharmaceutical Responsibilities

- FEFO picking enforcement
- Expiry alert and quarantine workflows
- Lot/batch mandatory traceability
- High-risk medication flagging and restricted access
- Controlled substance tracking
- Recall management workflows
- Regulatory compliance reports (FDA/DEA/DSCSA)
- Tamper-proof audit trail for all stock mutations

---

## Concurrency Controls

- **Pessimistic locking** for all stock deduction operations
- **Optimistic locking** for non-critical updates
- **Atomic transactions** for all stock mutations
- **Deadlock-aware retry** mechanisms

## Financial Rules

- All cost calculations use **BCMath only** — minimum **4 decimal places**
- Intermediate calculations (further divided or multiplied before final rounding): **8+ decimal places**
- Final monetary values: rounded to the **currency's standard precision (typically 2 decimal places)**
- No floating-point arithmetic permitted
- Costing method must reconcile with Accounting module

---

## Architecture Layer

```
Modules/Inventory/
 ├── Application/       # Stock in/out/transfer/adjust use cases, reservation service,
 │                      # FEFO picking, quarantine, recall, compliance report use cases
 ├── Domain/            # StockLedger entity, StockReservation entity,
 │                      # InventoryRepository contract, PharmaceuticalStockRule,
 │                      # ExpiryAlert, RecallEvent entities
 ├── Infrastructure/    # InventoryRepository, InventoryServiceProvider, locking strategies,
 │                      # compliance report generators
 ├── Interfaces/        # StockController, StockResource, StockAdjustmentRequest,
 │                      # PharmaceuticalStockController, ComplianceReportController
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
| All stock mutations wrapped in DB transactions with pessimistic locking | ✅ Enforced |
| Reservation precedes deduction | ✅ Enforced |
| Historical ledger entries are immutable | ✅ Enforced |
| Pharmaceutical compliance mode enforced when enabled (FEFO, lot/expiry mandatory, audit trail) | ✅ Enforced |
| Full audit trail (cannot be disabled) | ✅ Enforced |
| No cross-module coupling (communicates via contracts/events) | ✅ Enforced |

---

## Dependencies

- `core`
- `tenancy`
- `product`

---

## Status

🔴 **Planned** — See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
