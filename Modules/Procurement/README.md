# Procurement Module

## Overview

The **Procurement** module manages the full purchase-to-pay cycle from purchase request through to payment, with three-way matching and vendor management.

---

## Procurement Flow

```
Purchase Request → RFQ → Vendor Selection → Purchase Order → Goods Receipt → Vendor Bill → Payment
```

---

## Responsibilities

- Purchase request management
- Request for Quotation (RFQ)
- Vendor comparison and selection
- Purchase order management
- Goods receipt processing
- Three-way matching (PO ↔ Goods Receipt ↔ Invoice)
- Vendor bill management
- Vendor scoring
- Price comparison engine

## Financial Rules

- All calculations use **BCMath only** — minimum **4 decimal places**
- Intermediate calculations (further divided or multiplied before final rounding): **8+ decimal places**
- Final monetary values: rounded to the **currency's standard precision (typically 2 decimal places)**
- No floating-point arithmetic

---

## Architecture Layer

```
Modules/Procurement/
 ├── Application/       # Create PO, receive goods, match PO/receipt/invoice, approve payment use cases
 ├── Domain/            # PurchaseOrder, GoodsReceipt, VendorBill entities, ProcurementRepository contract
 ├── Infrastructure/    # ProcurementRepository, ProcurementServiceProvider
 ├── Interfaces/        # PurchaseOrderController, GoodsReceiptController, VendorBillController
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
| Three-way matching (PO ↔ Goods Receipt ↔ Invoice) enforced | ✅ Required |
| Full audit trail | ✅ Enforced |
| No cross-module coupling (communicates via contracts/events) | ✅ Enforced |

---

## Dependencies

- `core`
- `tenancy`
- `product`
- `inventory`
- `accounting`
- `workflow`

---

## Status

🔴 **Planned** — See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
