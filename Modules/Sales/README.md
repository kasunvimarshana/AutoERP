# Sales Module

## Overview

The **Sales** module manages the full sales lifecycle from quotation to payment, with backorder support, rule-based discount engine, tax calculation, and e-commerce API compatibility.

---

## Sales Flow

```
Quotation → Sales Order → Delivery → Invoice → Payment
```

---

## Responsibilities

- Quotation management
- Sales order management
- Delivery / shipment management
- Invoice generation
- Payment recording
- Backorder management
- Rule-based discount engine
- Tax calculation (inclusive/exclusive)
- Commission engine
- E-commerce API compatibility
- Multi-currency support (BCMath precision)

## Financial Rules

- All calculations use **BCMath only** — minimum **4 decimal places**
- Intermediate calculations (further divided or multiplied before final rounding): **8+ decimal places**
- Final monetary values: rounded to the **currency's standard precision (typically 2 decimal places)**
- Tax calculations are deterministic
- No floating-point arithmetic

---

## Architecture Layer

```
Modules/Sales/
 ├── Application/       # Create order, confirm, ship, invoice, receive payment use cases
 ├── Domain/            # SalesOrder, Delivery, Invoice entities, SalesRepository contract
 ├── Infrastructure/    # SalesRepository, SalesServiceProvider, event publishers
 ├── Interfaces/        # SalesOrderController, InvoiceController, SalesOrderResource
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
| Full audit trail | ✅ Enforced |
| No cross-module coupling (communicates via contracts/events) | ✅ Enforced |

---

## Dependencies

- `core`
- `tenancy`
- `product`
- `inventory`
- `pricing`
- `accounting`

---

## Status

🔴 **Planned** — See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
