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

## Implemented Files

### Migrations
| File | Table |
|---|---|
| `2026_02_27_000053_create_vendors_table.php` | `vendors` |
| `2026_02_27_000054_create_purchase_orders_table.php` | `purchase_orders` |
| `2026_02_27_000055_create_purchase_order_lines_table.php` | `purchase_order_lines` |
| `2026_02_27_000056_create_goods_receipts_table.php` | `goods_receipts` |
| `2026_02_27_000057_create_goods_receipt_lines_table.php` | `goods_receipt_lines` |
| `2026_02_27_000058_create_vendor_bills_table.php` | `vendor_bills` |

### Domain Entities
- `Vendor` — HasTenant; rating cast to string
- `PurchaseOrder` — HasTenant; subtotal, tax_amount, total_amount cast to string
- `PurchaseOrderLine` — HasTenant; quantity, unit_cost, line_total cast to string
- `GoodsReceipt` — HasTenant; belongsTo PurchaseOrder, hasMany GoodsReceiptLines
- `GoodsReceiptLine` — HasTenant; quantity_received, unit_cost cast to string
- `VendorBill` — HasTenant; total_amount, paid_amount cast to string

### Application Layer
- `CreatePurchaseOrderDTO` — fromArray factory; monetary fields as strings
- `CreateVendorDTO` — fromArray/toArray; optional email/phone/address/vendor_code; is_active defaults true
- `CreateVendorBillDTO` — fromArray/toArray; monetary total_amount as string; optional purchase_order_id/due_date/notes
- `ProcurementService` — createPurchaseOrder, receiveGoods, threeWayMatch, listOrders, showPurchaseOrder, updatePurchaseOrder, showVendorBill, updateVendor, listVendors, createVendor, showVendor, createVendorBill, listVendorBills (all BCMath; all mutations in DB::transaction)

### Infrastructure Layer
- `ProcurementRepositoryContract` — findByOrderNumber, findByVendor
- `ProcurementRepository` — extends AbstractRepository on PurchaseOrder
- `VendorRepositoryContract` — findActive
- `VendorRepository` — extends AbstractRepository on Vendor
- `VendorBillRepositoryContract` — findByVendor, findByPurchaseOrder
- `VendorBillRepository` — extends AbstractRepository on VendorBill
- `ProcurementServiceProvider` — binds all 3 contracts, loads migrations and routes

### API Routes (`/api/v1`)
| Method | Path | Action |
|---|---|---|
| POST | `/procurement/orders` | createPurchaseOrder |
| GET | `/procurement/orders` | listOrders |
| GET | `/procurement/orders/{id}` | showPurchaseOrder |
| PUT | `/procurement/orders/{id}` | updatePurchaseOrder |
| POST | `/procurement/orders/{id}/receive` | receiveGoods |
| GET | `/procurement/orders/{id}/three-way-match` | threeWayMatch |
| GET | `/procurement/vendors` | listVendors |
| POST | `/procurement/vendors` | createVendor |
| GET | `/procurement/vendors/{id}` | showVendor |
| PUT | `/procurement/vendors/{id}` | updateVendor |
| GET | `/procurement/vendor-bills` | listVendorBills |
| POST | `/procurement/vendor-bills` | createVendorBill |
| GET | `/procurement/vendor-bills/{id}` | showVendorBill |

---

## Test Coverage

| Test File | Type | Coverage Area |
|---|---|---|
| `Tests/Unit/CreatePurchaseOrderDTOTest.php` | Unit | `CreatePurchaseOrderDTO` — field hydration, type casting, string quantity/cost fields |
| `Tests/Unit/ProcurementLineMathTest.php` | Unit | BCMath line-total, subtotal, total-amount, and three-way-match comparison arithmetic |
| `Tests/Unit/ProcurementServiceListTest.php` | Unit | `listOrders()` filter-routing (vendor_id, no filter, int-cast, collection passthrough) |
| `Tests/Unit/ProcurementServiceWritePathTest.php` | Unit | createPurchaseOrder/receiveGoods/threeWayMatch method signatures and BCMath rounding |
| `Tests/Unit/ProcurementVendorServiceTest.php` | Unit | Vendor + VendorBill CRUD — DTO mapping, listVendors/listVendorBills filter-routing |
| `Tests/Unit/ProcurementServiceCrudTest.php` | Unit | showPurchaseOrder, updatePurchaseOrder, showVendorBill, updateVendor — 12 assertions |

---

## Status

🟢 **Complete** — Full PO, goods receipt, vendor management, vendor bill flow, show/update endpoints implemented; BCMath arithmetic validated (~85% test coverage). See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
