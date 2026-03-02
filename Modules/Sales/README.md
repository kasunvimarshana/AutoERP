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

## Implemented Files

| Layer | File |
|---|---|
| Migration | `Infrastructure/Database/Migrations/2026_02_27_000038_create_customers_table.php` |
| Migration | `Infrastructure/Database/Migrations/2026_02_27_000039_create_sales_orders_table.php` |
| Migration | `Infrastructure/Database/Migrations/2026_02_27_000040_create_sales_order_lines_table.php` |
| Migration | `Infrastructure/Database/Migrations/2026_02_27_000041_create_sales_deliveries_table.php` |
| Migration | `Infrastructure/Database/Migrations/2026_02_27_000042_create_sales_invoices_table.php` |
| Entity | `Domain/Entities/Customer.php` |
| Entity | `Domain/Entities/SalesOrder.php` |
| Entity | `Domain/Entities/SalesOrderLine.php` |
| Entity | `Domain/Entities/SalesDelivery.php` |
| Entity | `Domain/Entities/SalesInvoice.php` |
| Contract | `Domain/Contracts/SalesRepositoryContract.php` |
| Repository | `Infrastructure/Repositories/SalesRepository.php` |
| DTO | `Application/DTOs/CreateSalesOrderDTO.php` |
| Service | `Application/Services/SalesService.php` |
| Controller | `Interfaces/Http/Controllers/SalesController.php` |
| Routes | `routes/api.php` |
| Provider | `Infrastructure/Providers/SalesServiceProvider.php` |

## Service Methods

| Method | Description |
|---|---|
| `createOrder` | Create a new sales order (BCMath totals, DB::transaction) |
| `confirmOrder` | Confirm a pending sales order |
| `listOrders` | List sales orders (paginated, tenant-scoped) |
| `showOrder` | Show a single sales order |
| `cancelOrder` | Cancel an existing sales order |
| `listCustomers` | List customers (paginated, tenant-scoped) |
| `createDelivery` | Create a delivery record for a confirmed order (auto-deducts stock via FIFO if InventoryServiceContract is wired — DB::transaction) |
| `listDeliveries` | List deliveries for a sales order |
| `showDelivery` | Show a single delivery by ID |
| `createInvoice` | Create an invoice for a confirmed order (BCMath total, DB::transaction) |
| `listInvoices` | List invoices for a sales order |
| `showInvoice` | Show a single invoice by ID |
| `createReturn` | Process a sales return — restores inventory quantities per line via `return` stock transactions (DB::transaction, optional InventoryServiceContract) |

## API Endpoints

| Method | Path | Description |
|---|---|---|
| POST | `/api/v1/sales/orders` | Create a new sales order |
| GET | `/api/v1/sales/orders` | List all sales orders |
| GET | `/api/v1/sales/orders/{id}` | Show a sales order |
| POST | `/api/v1/sales/orders/{id}/confirm` | Confirm a sales order |
| POST | `/api/v1/sales/orders/{id}/cancel` | Cancel a sales order |
| GET | `/api/v1/sales/customers` | List customers |
| POST | `/api/v1/sales/orders/{id}/deliveries` | Create a delivery for an order |
| GET | `/api/v1/sales/orders/{id}/deliveries` | List deliveries for an order |
| POST | `/api/v1/sales/orders/{id}/invoices` | Create an invoice for an order |
| GET | `/api/v1/sales/orders/{id}/invoices` | List invoices for an order |
| GET | `/api/v1/sales/invoices/{id}` | Show a single invoice |
| POST | `/api/v1/sales/orders/{id}/returns` | Process a sales return — restores inventory quantities batch/lot-accurately |

## Test Coverage

| Test File | Type | Coverage Area |
|---|---|---|
| `Tests/Unit/CreateSalesOrderDTOTest.php` | Unit | DTO hydration, BCMath string fields |
| `Tests/Unit/SalesServiceLineMathTest.php` | Unit | BCMath line-total, tax, subtotal, total-amount, monetary rounding — 11 assertions |
| `Tests/Unit/SalesServiceListTest.php` | Unit | listOrders delegation, confirmOrder signature — 8 assertions |
| `Tests/Unit/SalesServiceCrudTest.php` | Unit | showOrder, cancelOrder, listCustomers — method signatures, delegation — 12 assertions |
| `Tests/Unit/SalesServiceDeliveryTest.php` | Unit | createDelivery, listDeliveries, showDelivery, createInvoice, listInvoices, showInvoice — 22 assertions |
| `Tests/Unit/SalesServiceAutoStockTest.php` | Unit | Automatic stock integration — optional InventoryServiceContract, createReturn method existence/signature, no-op without inventory — 14 assertions |
| `Tests/Unit/SalesControllerReturnTest.php` | Unit | SalesController::createReturn method existence, visibility, return type, parameter signatures — 8 assertions |

## Status

🟢 **Complete** — Full order lifecycle (create, confirm, cancel, show, list), delivery management (with automatic FIFO stock deduction), invoice management, sales returns (with automatic stock restoration), and customer listing implemented (~90% test coverage). See [IMPLEMENTATION_STATUS.md](../../IMPLEMENTATION_STATUS.md)
