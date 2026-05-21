# Section - 01
---

# 1. Item Master Structure

## items

```sql
items
(
    id BIGINT PK,

    tenant_id BIGINT FK,
    organization_unit_id BIGINT FK NULL,

    item_code VARCHAR(50) UNIQUE,
    sku VARCHAR(100) NULL,
    barcode VARCHAR(100) NULL,

    name VARCHAR(255),
    description TEXT NULL,

    item_type_id BIGINT FK,
    item_category_id BIGINT FK,
    item_brand_id BIGINT FK NULL,
    item_uom_id BIGINT FK,

    inventory_tracking BOOLEAN DEFAULT TRUE,
    serial_tracking BOOLEAN DEFAULT FALSE,
    batch_tracking BOOLEAN DEFAULT FALSE,

    valuation_method ENUM(
        'FIFO',
        'WEIGHTED_AVERAGE',
        'SPECIFIC_IDENTIFICATION'
    ),

    costing_method ENUM(
        'STANDARD',
        'ACTUAL',
        'AVERAGE'
    ),

    standard_cost DECIMAL(18,4) NULL,

    purchase_price DECIMAL(18,4) NULL,
    sales_price DECIMAL(18,4) NULL,

    minimum_stock DECIMAL(18,4) NULL,
    maximum_stock DECIMAL(18,4) NULL,
    reorder_level DECIMAL(18,4) NULL,

    is_stock_item BOOLEAN,
    is_purchasable BOOLEAN,
    is_sellable BOOLEAN,
    is_manufacturable BOOLEAN,

    status ENUM(
        'ACTIVE',
        'INACTIVE',
        'DISCONTINUED'
    ),

    notes TEXT NULL,

    created_by BIGINT,
    updated_by BIGINT NULL,

    created_at,
    updated_at,
    deleted_at
)
```

---

# 2. Supporting Masters

## item_types

```sql
RAW_MATERIAL
FINISHED_GOOD
SEMI_FINISHED
SERVICE
CONSUMABLE
SPARE_PART
ASSET
```

---

## item_categories

```sql
id
parent_id
code
name
description
```

Supports hierarchy.

```text
Electronics
 ├─ Mobile
 ├─ Laptop

Automotive
 ├─ Tyres
 ├─ Batteries
```

---

## item_brands

```sql
id
code
name
```

---

## units_of_measure

```sql
id
code
name
symbol
```

Examples

```text
PCS
KG
LTR
BOX
PACK
MTR
```

---

# 3. Warehouse Structure

## warehouses

```sql
warehouses
(
    id,

    tenant_id,
    organization_unit_id,

    code,
    name,

    address,

    warehouse_type,

    is_default,

    status
)
```

---

## warehouse_locations

```sql
warehouse_locations
(
    id,

    warehouse_id,

    code,
    name,

    parent_location_id
)
```

Example

```text
Main Warehouse
 ├─ Rack A
 ├─ Rack B
 ├─ Rack C
```

---

# 4. Inventory Balance Tables

Never calculate stock from purchase tables directly.

Keep balance tables.

---

## inventory_balances

Current stock snapshot.

```sql
inventory_balances
(
    id,

    tenant_id,
    warehouse_id,
    location_id NULL,

    item_id,

    quantity_on_hand,
    quantity_reserved,
    quantity_available,

    average_cost,

    inventory_value,

    last_transaction_at
)
```

---

## inventory_lots

For batch tracking.

```sql
inventory_lots
(
    id,

    item_id,
    warehouse_id,

    lot_number,

    manufacture_date,
    expiry_date,

    quantity,

    unit_cost
)
```

---

## inventory_serials

```sql
inventory_serials
(
    id,

    item_id,

    serial_number,

    warehouse_id,

    status
)
```

Status

```text
AVAILABLE
SOLD
RETURNED
DAMAGED
SCRAPPED
```

---

# 5. Inventory Transactions (Most Important)

Single source of truth.

---

## inventory_transactions

```sql
inventory_transactions
(
    id,

    tenant_id,

    transaction_no,

    transaction_date,

    item_id,

    warehouse_id,
    location_id NULL,

    transaction_type,

    reference_type,
    reference_id,

    quantity_in,
    quantity_out,

    unit_cost,

    total_cost,

    running_quantity,

    running_value,

    remarks,

    created_by
)
```

---

## Transaction Types

```text
OPENING_STOCK

PURCHASE_RECEIPT
PURCHASE_RETURN

SALES_ISSUE
SALES_RETURN

STOCK_TRANSFER_OUT
STOCK_TRANSFER_IN

ADJUSTMENT_IN
ADJUSTMENT_OUT

PRODUCTION_CONSUMPTION
PRODUCTION_OUTPUT

SCRAP
DAMAGE

COUNT_ADJUSTMENT
```

---

# 6. FIFO Layers

Required for FIFO valuation.

## inventory_cost_layers

```sql
inventory_cost_layers
(
    id,

    item_id,

    warehouse_id,

    source_transaction_id,

    received_quantity,

    remaining_quantity,

    unit_cost,

    received_date
)
```

Example

```text
100 pcs @ 100

50 pcs sold

remaining = 50
```

---

# 7. Stock Transfers

## stock_transfers

```sql
stock_transfers
(
    id,

    transfer_no,

    source_warehouse_id,
    destination_warehouse_id,

    transfer_date,

    status
)
```

---

## stock_transfer_items

```sql
id
stock_transfer_id
item_id
quantity
unit_cost
```

---

# 8. Stock Adjustments

## stock_adjustments

```sql
id
adjustment_no
warehouse_id
adjustment_date
reason
```

---

## stock_adjustment_items

```sql
id
adjustment_id
item_id

expected_qty
actual_qty

adjustment_qty

unit_cost
```

---

# 9. Purchasing Structure

## purchase_orders

```sql
id

po_number

supplier_id

warehouse_id

order_date

currency_id

status

subtotal
discount
tax
total
```

---

## purchase_order_items

```sql
id
purchase_order_id

item_id

quantity
received_quantity

unit_price

discount

tax

line_total
```

---

## goods_receipts

Inventory increases here.

```sql
goods_receipts
(
    id,

    grn_number,

    supplier_id,

    purchase_order_id,

    warehouse_id,

    receipt_date
)
```

---

## goods_receipt_items

```sql
id
goods_receipt_id

item_id

received_qty

unit_cost

line_total
```

Creates:

```text
Inventory Transaction
FIFO Layer
Journal Entry
```

---

# 10. Sales Structure

## sales_orders

```sql
id

order_number

customer_id

warehouse_id

order_date

status
```

---

## sales_order_items

```sql
id
sales_order_id

item_id

quantity

unit_price

discount
tax
```

---

## deliveries

```sql
id

delivery_number

sales_order_id

warehouse_id

delivery_date
```

---

## delivery_items

```sql
id

delivery_id

item_id

quantity
```

Creates

```text
Inventory Transaction
COGS
FIFO Consumption
Journal Entry
```

---

# 11. Accounting Integration

Every inventory transaction must generate accounting entries.

---

## inventory_accounts

Per Item Category.

```sql
inventory_accounts
(
    id,

    item_category_id,

    inventory_account_id,

    cogs_account_id,

    purchase_account_id,

    sales_account_id,

    adjustment_account_id,

    inventory_gain_account_id,

    inventory_loss_account_id
)
```

---

# Example Journal Entries

## Purchase Receipt

```text
Dr Inventory Asset

Cr GRN Clearing
```

---

## Supplier Invoice

```text
Dr GRN Clearing

Cr Accounts Payable
```

---

## Sales Delivery

```text
Dr Cost Of Goods Sold

Cr Inventory Asset
```

---

## Customer Invoice

```text
Dr Accounts Receivable

Cr Sales Revenue
```

---

# Recommended Additional Tables

## item_prices

```sql
id
item_id
price_list_id
price
effective_from
effective_to
```

---

## item_images

```sql
id
item_id
file_path
```

---

## item_attributes

```sql
id
attribute_name
```

---

## item_attribute_values

```sql
id
item_id
attribute_id
value
```

Example

```text
Color = Red
Size = XL
Capacity = 64GB
```

---

# Enterprise ERP Final Schema Modules

```text
Inventory
│
├── Item Master
│   ├── items
│   ├── categories
│   ├── brands
│   ├── uoms
│   └── attributes
│
├── Warehouses
│   ├── warehouses
│   └── locations
│
├── Inventory Control
│   ├── inventory_balances
│   ├── inventory_transactions
│   ├── inventory_cost_layers
│   ├── inventory_lots
│   └── inventory_serials
│
├── Purchasing
│   ├── purchase_orders
│   ├── goods_receipts
│   └── purchase_returns
│
├── Sales
│   ├── sales_orders
│   ├── deliveries
│   └── sales_returns
│
├── Transfers
│   ├── stock_transfers
│   └── transfer_items
│
├── Adjustments
│   ├── stock_adjustments
│   └── adjustment_items
│
└── Accounting Integration
    ├── inventory_accounts
    └── journal_entries
```
---

# Section - 02

---

# 🔥 Allocation Use Location

## 1. Month End Process (MOST IMPORTANT)

```text
Step:
- collect expenses
- apply allocation rules
- distribute cost
- update GL
```

---

## 2. Cost Centers

```text
Head Office Rent
→ distribute to branches
```

---

## 3. Product Profitability

```text
shared marketing cost → products
```

---

## 4. Project Costing

```text
Project A
Project B
→ shared developer cost
```

---

# 3. ERP Full Flow (REAL SYSTEM)

## 🔷 Step 1: Purchase

```text
Inventory In
→ valuation method applied
→ stock updated
→ ledger entry created
```

---

## 🔷 Step 2: Sales

```text
stock out
→ valuation method calculates COGS
→ inventory reduced
→ revenue + COGS posted
```

---

## 🔷 Step 3: Month End Allocation

```text
expenses collected
→ allocation engine runs
→ cost distributed
→ profitability updated
```

---

# 4. Simple Architecture View

```text id="arch1"
                 ┌──────────────┐
Purchase ───────▶│ Inventory In │
                 └──────┬───────┘
                        │
                        ▼
              Valuation Engine (FIFO / AVG / SPEC)
                        │
                        ▼
                 Stock Ledger

Sales ───────────────▶ COGS Calculation

Month End ───────────▶ Allocation Engine
                        │
                        ▼
              Cost Centers / Products / Projects
```

---

# 5. Key Difference (Very Important)

| Concept    | Purpose                | When Used             |
| ---------- | ---------------------- | --------------------- |
| Valuation  | Stock value + COGS     | Every stock movement  |
| Allocation | Distribute shared cost | Month end / reporting |

---

# 6. Real ERP Rule (IMPORTANT)

✔ Valuation = Real-time (transaction level)
✔ Allocation = Batch process (end of period)

---

# 7. Simple Memory Trick

```text
Valuation → "How much is stock worth?"
Allocation → "Where should shared cost go?"
```

---

# Section - 03

---

# 🧠 CORE IDEA (Very Important)

| Concept                                  | When used                           |
| ---------------------------------------- | ----------------------------------- |
| **Valuation Method (FIFO / AVG / SPEC)** | Every stock movement (real-time)    |
| **Allocation Method**                    | Month-end / cost distribution       |
| **Transaction Type**                     | Determines *what logic engine runs* |

---

# 1. TRANSACTION → ENGINE MAPPING STRATEGY

```text
Transaction Type
      ↓
Handler (Service Layer)
      ↓
Valuation Engine (FIFO / AVG / SPEC)
      ↓
Inventory Ledger Update
      ↓
Accounting Journal Posting
      ↓
(Optional) Allocation Engine (Month End)
```

---

# 2. TRANSACTION WISE BEHAVIOR

---

# 🔷 OPENING_STOCK

### 👉 Purpose:

Initial stock load

### Valuation:

* Fixed cost entry
* No FIFO logic

```text
quantity_in = X
unit_cost = given
```

### Allocation:

❌ Not used

---

# 🔷 PURCHASE_RECEIPT

### 👉 Purpose:

Stock IN from supplier

### Valuation:

✔ FIFO / AVG / SPEC active

### Behavior:

```text
Increase stock
Create FIFO layer
Update avg cost
```

### Accounting:

```text
Dr Inventory
Cr GRN Clearing / AP
```

---

# 🔷 PURCHASE_RETURN

### 👉 Purpose:

Return stock to supplier

### Valuation:

✔ Reverse valuation

### Behavior:

* Remove FIFO layer (or reverse avg impact)

```text
quantity_out
COGS reversal
```

---

# 🔷 SALES_ISSUE

### 👉 Purpose:

Stock OUT (MOST IMPORTANT)

### Valuation:

🔥 FULLY ACTIVE

### Behavior:

```text
FIFO → consume oldest layers
AVG → use weighted cost
SPEC → pick serial
```

### Accounting:

```text
Dr COGS
Cr Inventory
```

---

# 🔷 SALES_RETURN

### 👉 Purpose:

Customer returns stock

### Valuation:

✔ Reverse sales issue

### Behavior:

```text
quantity_in
restore FIFO layer or avg value
```

---

# 🔷 STOCK_TRANSFER_OUT

### 👉 Purpose:

Move stock between warehouses

### Valuation:

✔ SAME COST transfer (NO profit/loss)

### Behavior:

```text
Warehouse A → B
same unit_cost
```

### Important:

❌ NO COGS
❌ NO Revenue impact

---

# 🔷 STOCK_TRANSFER_IN

### 👉 Purpose:

Receive transferred stock

### Valuation:

✔ mirror of OUT

```text
keep same unit_cost
```

---

# 🔷 ADJUSTMENT_IN

### 👉 Purpose:

Stock increase correction

### Valuation:

✔ depends on method

* FIFO → new layer
* AVG → recalculation

```text
quantity_in + correction
```

### Accounting:

```text
Dr Inventory
Cr Inventory Gain
```

---

# 🔷 ADJUSTMENT_OUT

### 👉 Purpose:

Stock reduction correction

### Valuation:

✔ FIFO/AVG reduce stock

```text
quantity_out
```

### Accounting:

```text
Dr Inventory Loss
Cr Inventory
```

---

# 🔷 PRODUCTION_CONSUMPTION

### 👉 Purpose:

Raw material usage

### Valuation:

✔ FULL FIFO/AVG

```text
Raw Material OUT
```

### Accounting:

```text
Dr WIP / Production Cost
Cr Inventory
```

---

# 🔷 PRODUCTION_OUTPUT

### 👉 Purpose:

Finished goods creation

### Valuation:

✔ COST BUILD UP

```text
quantity_in finished goods
```

### Cost = from consumed materials + labor + overhead

---

# 🔷 SCRAP

### 👉 Purpose:

Unusable stock removal

### Valuation:

✔ FIFO/AVG reduce value

```text
write-off cost
```

### Accounting:

```text
Dr Scrap Expense
Cr Inventory
```

---

# 🔷 DAMAGE

### 👉 Purpose:

Damaged stock

### Valuation:

✔ similar to scrap

```text
loss recognition
```

---

# 🔷 COUNT_ADJUSTMENT

### 👉 Purpose:

Stock take correction

### Valuation:

✔ reconciliation engine

```text
system_qty vs actual_qty
```

### Behavior:

| If actual > system | IN |
| If actual < system | OUT |

---

# 3. WHERE VALUATION METHOD IS USED

## ALWAYS used in:

### 1. PURCHASE_RECEIPT

→ cost entry

### 2. SALES_ISSUE

→ COGS calculation

### 3. RETURN

→ reverse valuation

### 4. ADJUSTMENT

→ correction value

### 5. PRODUCTION

→ cost build-up

---

# 4. WHERE ALLOCATION METHOD IS USED

👉 NOT per transaction

Used in:

---

## 🔷 Month End Process ONLY

### Example:

```text id="al1"
Electricity = 100,000
```

Allocated based on:

* machine hours
* revenue
* labor hours
* space

---

## 🔷 Used For:

### 1. Production Costing

```text
overhead → products
```

### 2. Department Costing

```text
HR, IT, Sales
```

### 3. Project Costing

```text
Project A / B
```

---

# 5. SIMPLE RULE (VERY IMPORTANT)

```text
Transaction Types → Inventory Movement + Valuation

Allocation → Cost Redistribution (Month End)
```

---

# 6. FINAL ARCHITECTURE (REAL ERP STYLE)

```text
Transaction Posted
      ↓
Inventory Service
      ↓
Valuation Engine (FIFO / AVG / SPEC)
      ↓
Stock Ledger Updated
      ↓
Accounting Journal Posted
      ↓
Month End Job
      ↓
Allocation Engine
      ↓
Cost Centers / Products / Projects
```

---

# 7. KEY DESIGN INSIGHT (IMPORTANT)

✔ Transaction = Real-time truth
✔ Valuation = Real-time costing
✔ Allocation = Analytical redistribution

---

# 8. SIMPLE MEMORY TRICK

```text
Transaction → "What happened to stock?"
Valuation → "What is stock worth now?"
Allocation → "Who should bear the cost?"
```

---

# Section - 04

---
* Multi-tenant ERP readiness Extensible (FIFO / AVG / Allocation engines)

---

# 🧱 1. High-Level Architecture

```text
Controller
   ↓
Action / Application Service
   ↓
Domain Service (Business Logic)
   ↓
Strategy (Valuation / Allocation)
   ↓
Repository (Data Access)
   ↓
Eloquent Models
```

---

# 📦 2. Folder Structure (Laravel)

```text
app/
 ├── Domain/
 │    ├── Inventory/
 │    │     ├── Models/
 │    │     ├── Services/
 │    │     ├── ValueObjects/
 │    │     ├── Enums/
 │    │     └── Contracts/
 │    │
 │    ├── Accounting/
 │    │     ├── Services/
 │    │     ├── Contracts/
 │    │
 │    └── Allocation/
 │          ├── Services/
 │          ├── Strategies/
 │          └── Contracts/
 │
 ├── Application/
 │    ├── Inventory/
 │    │     ├── Actions/
 │    │     ├── DTOs/
 │    │     └── Handlers/
 │
 │    └── Accounting/
 │
 ├── Infrastructure/
 │    ├── Persistence/
 │    │     ├── Repositories/
 │    │     └── Models/
 │    └── Services/
 │
 ├── Http/
 │    ├── Controllers/
 │    └── Requests/
```

---

# ⚙️ 3. Core Design Concept

## ✔ Single Responsibility Rule

| Layer          | Responsibility      |
| -------------- | ------------------- |
| Controller     | HTTP only           |
| Action         | Orchestration       |
| Domain Service | Business rules      |
| Strategy       | FIFO / AVG logic    |
| Repository     | DB access           |
| Model          | Data structure only |

---

# 📥 4. Inventory Posting Flow

```text
Transaction Received
   ↓
InventoryAction
   ↓
InventoryService
   ↓
ValuationStrategy (FIFO / AVG)
   ↓
StockLedgerService
   ↓
AccountingService
```

---

# 🧩 5. Core Interfaces (Contracts)

## Inventory Valuation Contract

```php
namespace App\Domain\Inventory\Contracts;

interface ValuationStrategy
{
    public function process(array $context): ValuationResult;
}
```

---

## Allocation Contract

```php
namespace App\Domain\Allocation\Contracts;

interface AllocationStrategy
{
    public function allocate(array $costPool): array;
}
```

---

## Repository Contract

```php
interface InventoryRepository
{
    public function saveTransaction(array $data);
    public function updateBalance(int $itemId, array $data);
}
```

---

# 🧠 6. Application Layer (Action)

## Inventory Transaction Action

```php
class PostInventoryTransactionAction
{
    public function __construct(
        private InventoryService $inventoryService
    ) {}

    public function execute(InventoryTransactionDTO $dto)
    {
        return $this->inventoryService->process($dto);
    }
}
```

👉 ONLY orchestration — no business logic here

---

# 🏭 7. Domain Service (Core Logic)

## Inventory Service

```php
class InventoryService
{
    public function __construct(
        private ValuationStrategyFactory $factory,
        private StockLedgerService $ledgerService,
        private AccountingService $accountingService
    ) {}

    public function process($dto)
    {
        // 1. Get strategy
        $strategy = $this->factory->make($dto->valuationMethod);

        // 2. Calculate valuation
        $result = $strategy->process($dto->toArray());

        // 3. Update stock ledger
        $ledger = $this->ledgerService->record($result);

        // 4. Post accounting entry
        $this->accountingService->post($ledger);

        return $ledger;
    }
}
```

---

# 🧮 8. Strategy Pattern (Valuation Engine)

## FIFO Strategy

```php
class FifoValuationStrategy implements ValuationStrategy
{
    public function process(array $context): ValuationResult
    {
        $quantity = $context['quantity'];
        $layers = $this->getLayers($context['item_id']);

        $remaining = $quantity;
        $cost = 0;

        foreach ($layers as $layer) {
            if ($remaining <= 0) break;

            $consume = min($remaining, $layer->remaining_qty);

            $cost += $consume * $layer->unit_cost;

            $remaining -= $consume;
        }

        return new ValuationResult($quantity, $cost);
    }
}
```

---

## Weighted Average Strategy

```php
class AverageValuationStrategy implements ValuationStrategy
{
    public function process(array $context): ValuationResult
    {
        $balance = $this->getBalance($context['item_id']);

        $newQty = $balance->qty + $context['quantity_in'];
        $newCost = $balance->value + $context['total_cost'];

        $avg = $newCost / $newQty;

        return new ValuationResult($context['quantity_in'], $avg);
    }
}
```

---

# 🏭 9. Strategy Factory

```php
class ValuationStrategyFactory
{
    public function make(string $method): ValuationStrategy
    {
        return match ($method) {
            'FIFO' => new FifoValuationStrategy(),
            'AVERAGE' => new AverageValuationStrategy(),
            'SPECIFIC' => new SpecificValuationStrategy(),
        };
    }
}
```

---

# 📊 10. Stock Ledger Service

```php
class StockLedgerService
{
    public function record(ValuationResult $result)
    {
        return InventoryTransaction::create([
            'quantity_in' => $result->in,
            'quantity_out' => $result->out,
            'unit_cost' => $result->unitCost,
            'total_cost' => $result->totalCost,
            'balance_quantity' => $result->balanceQty,
            'balance_value' => $result->balanceValue,
        ]);
    }
}
```

---

# 💰 11. Accounting Service

```php
class AccountingService
{
    public function post($ledger)
    {
        JournalEntry::create([
            'debit_account' => $this->getInventoryAccount(),
            'credit_account' => $this->getGRNOrRevenueAccount(),
            'amount' => $ledger->total_cost,
        ]);
    }
}
```

---

# 🔄 12. Allocation Engine (Month-End Only)

## Allocation Service

```php
class AllocationService
{
    public function __construct(
        private AllocationStrategyFactory $factory
    ) {}

    public function run(CostPool $pool)
    {
        $strategy = $this->factory->make($pool->method);

        return $strategy->allocate($pool->toArray());
    }
}
```

---

## Example Allocation Strategy

```php
class UsageBasedAllocation implements AllocationStrategy
{
    public function allocate(array $pool): array
    {
        $totalUsage = array_sum($pool['usage']);

        $result = [];

        foreach ($pool['usage'] as $key => $value) {
            $result[$key] = ($value / $totalUsage) * $pool['cost'];
        }

        return $result;
    }
}
```

---

# 🧪 13. Controller (Clean)

```php
class InventoryController extends Controller
{
    public function store(Request $request, PostInventoryTransactionAction $action)
    {
        return $action->execute(
            InventoryTransactionDTO::fromRequest($request)
        );
    }
}
```

---

# Section - 05


---

* No DB logic inside strategy
* Repository abstraction
* Clear domain result object
* Safe for queues / high volume ERP usage

---

# 🧱 1. Shared Domain Result Object

## ValuationResult

```php
<?php

namespace App\Domain\Inventory\ValueObjects;

class ValuationResult
{
    public function __construct(
        public readonly float $quantity,
        public readonly float $unitCost,
        public readonly float $totalCost,
        public readonly float $balanceQuantity,
        public readonly float $balanceValue,
    ) {}
}
```

---

# 📦 2. Shared Contracts (Repository)

```php
interface InventoryRepository
{
    public function getLayers(int $itemId, int $warehouseId): array;

    public function getBalance(int $itemId, int $warehouseId): object;

    public function addLayer(array $data): void;

    public function updateBalance(int $itemId, int $warehouseId, array $data): void;
}
```

---

# 🔥 3. FIFO VALUATION STRATEGY (REAL IMPLEMENTATION)

## Logic:

* Consume oldest stock layers first
* Reduce layer quantities
* Maintain remaining balance

---

## Implementation

```php
<?php

namespace App\Domain\Inventory\Strategies;

use App\Domain\Inventory\Contracts\ValuationStrategy;
use App\Domain\Inventory\Repositories\InventoryRepository;
use App\Domain\Inventory\ValueObjects\ValuationResult;

class FifoValuationStrategy implements ValuationStrategy
{
    public function __construct(
        private InventoryRepository $repo
    ) {}

    public function process(array $context): ValuationResult
    {
        $itemId = $context['item_id'];
        $warehouseId = $context['warehouse_id'];
        $quantityOut = $context['quantity_out'] ?? 0;

        $layers = $this->repo->getLayers($itemId, $warehouseId);

        $remaining = $quantityOut;
        $totalCost = 0;

        foreach ($layers as $layer) {

            if ($remaining <= 0) break;

            $available = $layer->remaining_quantity;

            if ($available <= 0) continue;

            $consume = min($available, $remaining);

            $cost = $consume * $layer->unit_cost;

            $totalCost += $cost;

            // update layer
            $layer->remaining_quantity -= $consume;

            $this->repo->updateLayer($layer->id, [
                'remaining_quantity' => $layer->remaining_quantity
            ]);

            $remaining -= $consume;
        }

        $balance = $this->repo->getBalance($itemId, $warehouseId);

        return new ValuationResult(
            quantity: $quantityOut,
            unitCost: $quantityOut > 0 ? $totalCost / $quantityOut : 0,
            totalCost: $totalCost,
            balanceQuantity: $balance->quantity,
            balanceValue: $balance->value
        );
    }
}
```

---

# 📊 4. WEIGHTED AVERAGE VALUATION STRATEGY

## Logic:

* Maintain moving average cost
* Recalculate on every IN transaction

---

## Implementation

```php
<?php

namespace App\Domain\Inventory\Strategies;

use App\Domain\Inventory\Contracts\ValuationStrategy;
use App\Domain\Inventory\Repositories\InventoryRepository;
use App\Domain\Inventory\ValueObjects\ValuationResult;

class AverageValuationStrategy implements ValuationStrategy
{
    public function __construct(
        private InventoryRepository $repo
    ) {}

    public function process(array $context): ValuationResult
    {
        $itemId = $context['item_id'];
        $warehouseId = $context['warehouse_id'];

        $inQty = $context['quantity_in'] ?? 0;
        $outQty = $context['quantity_out'] ?? 0;
        $unitCost = $context['unit_cost'] ?? 0;

        $balance = $this->repo->getBalance($itemId, $warehouseId);

        $currentQty = $balance->quantity;
        $currentValue = $balance->value;

        // ➜ STOCK IN
        if ($inQty > 0) {

            $newQty = $currentQty + $inQty;
            $newValue = $currentValue + ($inQty * $unitCost);

            $avgCost = $newQty > 0 ? $newValue / $newQty : 0;

            $this->repo->updateBalance($itemId, $warehouseId, [
                'quantity' => $newQty,
                'value' => $newValue,
                'average_cost' => $avgCost,
            ]);

            return new ValuationResult(
                quantity: $inQty,
                unitCost: $avgCost,
                totalCost: $inQty * $avgCost,
                balanceQuantity: $newQty,
                balanceValue: $newValue
            );
        }

        // ➜ STOCK OUT
        $avgCost = $balance->average_cost;

        $newQty = $currentQty - $outQty;
        $newValue = $currentValue - ($outQty * $avgCost);

        $this->repo->updateBalance($itemId, $warehouseId, [
            'quantity' => $newQty,
            'value' => $newValue,
        ]);

        return new ValuationResult(
            quantity: $outQty,
            unitCost: $avgCost,
            totalCost: $outQty * $avgCost,
            balanceQuantity: $newQty,
            balanceValue: $newValue
        );
    }
}
```

---

# 🧾 5. SPECIFIC IDENTIFICATION STRATEGY

## Logic:

* Use serial / batch number
* Each unit has exact cost

---

## Requirements:

You MUST have:

```text
inventory_serials table
- item_id
- serial_number
- cost
- status
```

---

## Implementation

```php
<?php

namespace App\Domain\Inventory\Strategies;

use App\Domain\Inventory\Contracts\ValuationStrategy;
use App\Domain\Inventory\Repositories\InventoryRepository;
use App\Domain\Inventory\ValueObjects\ValuationResult;

class SpecificValuationStrategy implements ValuationStrategy
{
    public function __construct(
        private InventoryRepository $repo
    ) {}

    public function process(array $context): ValuationResult
    {
        $itemId = $context['item_id'];
        $warehouseId = $context['warehouse_id'];
        $serials = $context['serials'] ?? [];

        $totalCost = 0;
        $qty = count($serials);

        foreach ($serials as $serial) {

            $record = $this->repo->getSerial($serial);

            if (!$record || $record->status !== 'AVAILABLE') {
                throw new \Exception("Invalid serial: {$serial}");
            }

            $totalCost += $record->cost;

            $this->repo->updateSerial($serial, [
                'status' => 'SOLD'
            ]);
        }

        $balance = $this->repo->getBalance($itemId, $warehouseId);

        return new ValuationResult(
            quantity: $qty,
            unitCost: $qty > 0 ? $totalCost / $qty : 0,
            totalCost: $totalCost,
            balanceQuantity: $balance->quantity,
            balanceValue: $balance->value
        );
    }
}
```

---

# 🧠 6. HOW SYSTEM USES THESE STRATEGIES

```php
$strategy = match ($item->valuation_method) {

    'FIFO' => new FifoValuationStrategy($repo),

    'AVERAGE' => new AverageValuationStrategy($repo),

    'SPECIFIC' => new SpecificValuationStrategy($repo),

};
```

---

# 🔄 7. REAL ERP FLOW

## Purchase

```text
GRN → Average/FIFO → Balance Update → FIFO Layer Insert
```

## Sales

```text
Sales Issue → FIFO/Average/SERIAL → COGS → Stock Reduce
```

## Reporting

```text
Inventory Balance Table (fast)
+ Ledger (audit)
```

---

# Section - 06

---

# 🧠 1. Core Concept

```text
Inventory Transaction
        ↓
Accounting Mapping Engine
        ↓
Journal Builder (Debit / Credit Lines)
        ↓
Journal Entry Posted
```

---

# 🧱 2. Architecture (Clean Laravel Service Layer)

```text
app/
 ├── Domain/
 │    ├── Accounting/
 │    │     ├── Services/
 │    │     │     ├── JournalGeneratorService.php
 │    │     │     ├── AccountResolverService.php
 │    │     │     └── PostingService.php
 │    │     ├── Strategies/
 │    │     │     ├── PurchaseJournalStrategy.php
 │    │     │     ├── SalesJournalStrategy.php
 │    │     │     ├── InventoryAdjustmentStrategy.php
 │    │     │     └── TransferJournalStrategy.php
 │
 │    ├── Inventory/
 │    │     └── Events/
 │    │           └── InventoryTransactionPosted.php
```

---

# ⚙️ 3. EVENT-DRIVEN FLOW (BEST PRACTICE)

## When inventory is posted:

```text
InventoryTransactionPosted Event
        ↓
JournalGeneratorService
        ↓
Select Strategy
        ↓
Build Journal Entry
        ↓
Save journal_entries + lines
```

---

# 📦 4. MAIN JOURNAL GENERATOR SERVICE

```php
class JournalGeneratorService
{
    public function __construct(
        private PurchaseJournalStrategy $purchase,
        private SalesJournalStrategy $sales,
        private InventoryAdjustmentStrategy $adjustment,
        private TransferJournalStrategy $transfer
    ) {}

    public function generate(array $tx)
    {
        return match ($tx['transaction_type']) {

            'PURCHASE_RECEIPT' => $this->purchase->build($tx),

            'SALES_ISSUE' => $this->sales->build($tx),

            'ADJUSTMENT_IN', 'ADJUSTMENT_OUT' =>
                $this->adjustment->build($tx),

            'STOCK_TRANSFER_OUT' =>
                $this->transfer->build($tx),

            default => null
        };
    }
}
```

---

# 💰 5. ACCOUNT RESOLVER (VERY IMPORTANT)

This maps inventory → accounts dynamically.

```php
class AccountResolverService
{
    public function getInventoryAccount($item): int
    {
        return $item->category->inventory_account_id;
    }

    public function getCOGSAccount($item): int
    {
        return $item->category->cogs_account_id;
    }

    public function getAPAccount(): int
    {
        return config('accounts.ap');
    }

    public function getARAccount(): int
    {
        return config('accounts.ar');
    }

    public function getRevenueAccount($item): int
    {
        return $item->category->sales_account_id;
    }
}
```

---

# 🧾 6. PURCHASE JOURNAL (GRN)

## Logic

```text
Dr Inventory
    Cr Accounts Payable
```

## Implementation

```php
class PurchaseJournalStrategy
{
    public function __construct(
        private AccountResolverService $accounts
    ) {}

    public function build(array $tx)
    {
        $inventoryAccount = $this->accounts->getInventoryAccount($tx['item']);
        $apAccount = $this->accounts->getAPAccount();

        $amount = $tx['total_cost'];

        return [
            'description' => 'Purchase Receipt',
            'lines' => [
                [
                    'account_id' => $inventoryAccount,
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'account_id' => $apAccount,
                    'debit' => 0,
                    'credit' => $amount,
                ],
            ],
        ];
    }
}
```

---

# 💰 7. SALES JOURNAL

## Logic

```text
Dr COGS
Cr Inventory

Dr Accounts Receivable
Cr Revenue
```

## Implementation

```php
class SalesJournalStrategy
{
    public function __construct(
        private AccountResolverService $accounts
    ) {}

    public function build(array $tx)
    {
        $inventory = $this->accounts->getInventoryAccount($tx['item']);
        $cogs = $this->accounts->getCOGSAccount($tx['item']);
        $revenue = $this->accounts->getRevenueAccount($tx['item']);
        $ar = $this->accounts->getARAccount();

        $amount = $tx['total_cost'];
        $salesValue = $tx['sales_value'];

        return [
            'description' => 'Sales Issue',
            'lines' => [

                // COGS
                [
                    'account_id' => $cogs,
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'account_id' => $inventory,
                    'debit' => 0,
                    'credit' => $amount,
                ],

                // Revenue
                [
                    'account_id' => $ar,
                    'debit' => $salesValue,
                    'credit' => 0,
                ],
                [
                    'account_id' => $revenue,
                    'debit' => 0,
                    'credit' => $salesValue,
                ],
            ],
        ];
    }
}
```

---

# ⚠️ 8. INVENTORY ADJUSTMENT JOURNAL

## Logic

```text
Increase → Gain
Decrease → Loss
```

---

## Implementation

```php
class InventoryAdjustmentStrategy
{
    public function __construct(
        private AccountResolverService $accounts
    ) {}

    public function build(array $tx)
    {
        $inventory = $this->accounts->getInventoryAccount($tx['item']);

        $amount = $tx['total_cost'];

        if ($tx['quantity_in'] > 0) {

            return [
                'description' => 'Inventory Gain',
                'lines' => [
                    [
                        'account_id' => $inventory,
                        'debit' => $amount,
                        'credit' => 0,
                    ],
                    [
                        'account_id' => config('accounts.inventory_gain'),
                        'debit' => 0,
                        'credit' => $amount,
                    ],
                ],
            ];
        }

        return [
            'description' => 'Inventory Loss',
            'lines' => [
                [
                    'account_id' => config('accounts.inventory_loss'),
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'account_id' => $inventory,
                    'debit' => 0,
                    'credit' => $amount,
                ],
            ],
        ];
    }
}
```

---

# 🚚 9. STOCK TRANSFER JOURNAL (NO PROFIT!)

## Rule:

❌ NO income
❌ NO expense
✔ ONLY asset movement

---

```php
class TransferJournalStrategy
{
    public function build(array $tx)
    {
        $inventory = config('accounts.inventory');

        $amount = $tx['total_cost'];

        return [
            'description' => 'Stock Transfer',
            'lines' => [
                [
                    'account_id' => $inventory,
                    'debit' => $amount,
                    'credit' => 0,
                ],
                [
                    'account_id' => $inventory,
                    'debit' => 0,
                    'credit' => $amount,
                ],
            ],
        ];
    }
}
```

👉 In real ERP, this is split by warehouse sub-ledger

---

# 🧾 10. JOURNAL POSTING SERVICE

```php
class PostingService
{
    public function post(array $journal)
    {
        $entry = JournalEntry::create([
            'reference_no' => uniqid('JE'),
            'entry_date' => now(),
            'description' => $journal['description'],
        ]);

        foreach ($journal['lines'] as $line) {

            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $line['account_id'],
                'debit' => $line['debit'],
                'credit' => $line['credit'],
            ]);
        }

        return $entry;
    }
}
```

---

# 🔥 11. FULL FLOW (REAL ERP)

```text
Purchase / Sales / Adjustment
        ↓
Inventory Transaction Posted
        ↓
Event Fired
        ↓
Journal Generator
        ↓
Strategy Selected
        ↓
Debit / Credit Lines Created
        ↓
Journal Entry Saved
```

---

# 🧠 12. KEY DESIGN RULES

### ✔ Inventory NEVER directly writes accounting

It only triggers events.

### ✔ Accounting is RULE-BASED

Not hardcoded in controllers.

### ✔ Each transaction type = strategy

Open/Closed principle.

### ✔ Accounts are dynamic

Based on item category mapping.

---

# Section - 07

---

* FIFO / Average / Specific Valuation Engine
* Inventory Posting Service
* Allocation Engine (usage / percentage / revenue based)
* Clean architecture (SOLID, DRY, KISS)

This is the **core brain of your ERP inventory system**.

---

# 🧱 1. FINAL SERVICE LAYER STRUCTURE

```text
Domain/
 ├── Inventory/
 │    ├── Services/
 │    │     ├── InventoryPostingService.php
 │    │     ├── StockBalanceService.php
 │    │
 │    ├── Strategies/
 │    │     ├── Valuation/
 │    │     │     ├── FifoValuationStrategy.php
 │    │     │     ├── AverageValuationStrategy.php
 │    │     │     ├── SpecificValuationStrategy.php
 │
 ├── Allocation/
 │    ├── Services/
 │    │     ├── AllocationService.php
 │    │
 │    ├── Strategies/
 │    │     ├── UsageBasedAllocation.php
 │    │     ├── PercentageAllocation.php
 │    │     ├── RevenueBasedAllocation.php
```

---

# 🧠 2. CORE DTO (INPUT OBJECT)

```php
class InventoryTransactionDTO
{
    public function __construct(
        public int $itemId,
        public int $warehouseId,
        public string $type,

        public float $quantityIn = 0,
        public float $quantityOut = 0,

        public float $unitCost = 0,
        public float $salesValue = 0,

        public array $serials = [],
        public array $meta = []
    ) {}
}
```

---

# 📦 3. INVENTORY POSTING SERVICE (MAIN ENGINE)

```php
class InventoryPostingService
{
    public function __construct(
        private ValuationStrategyFactory $factory,
        private StockBalanceService $balanceService,
        private PostingRepository $repo
    ) {}

    public function process(InventoryTransactionDTO $dto)
    {
        $strategy = $this->factory->make($dto->type, $dto->itemId);

        $result = $strategy->process($dto);

        $this->repo->saveTransaction([
            'item_id' => $dto->itemId,
            'warehouse_id' => $dto->warehouseId,
            'type' => $dto->type,

            'quantity_in' => $dto->quantityIn,
            'quantity_out' => $dto->quantityOut,

            'unit_cost' => $result->unitCost,
            'total_cost' => $result->totalCost,

            'balance_quantity' => $result->balanceQuantity,
            'balance_value' => $result->balanceValue,
        ]);

        $this->balanceService->update(
            $dto->itemId,
            $dto->warehouseId,
            $result
        );

        return $result;
    }
}
```

---

# ⚙️ 4. STRATEGY FACTORY

```php
class ValuationStrategyFactory
{
    public function make(string $type, int $itemId)
    {
        $item = Item::findOrFail($itemId);

        return match ($item->valuation_method) {

            'FIFO' => new FifoValuationStrategy(),

            'AVERAGE' => new AverageValuationStrategy(),

            'SPECIFIC' => new SpecificValuationStrategy(),

        };
    }
}
```

---

# 🔥 5. FIFO VALUATION STRATEGY (FULL)

```php
class FifoValuationStrategy
{
    public function process(InventoryTransactionDTO $dto): ValuationResult
    {
        $layers = InventoryCostLayer::where('item_id', $dto->itemId)
            ->where('warehouse_id', $dto->warehouseId)
            ->orderBy('id')
            ->get();

        $remaining = $dto->quantityOut;
        $totalCost = 0;

        foreach ($layers as $layer) {

            if ($remaining <= 0) break;

            if ($layer->remaining_qty <= 0) continue;

            $consume = min($remaining, $layer->remaining_qty);

            $totalCost += $consume * $layer->unit_cost;

            $layer->remaining_qty -= $consume;
            $layer->save();

            $remaining -= $consume;
        }

        $balance = InventoryBalance::where('item_id', $dto->itemId)
            ->where('warehouse_id', $dto->warehouseId)
            ->first();

        return new ValuationResult(
            quantity: $dto->quantityOut,
            unitCost: $dto->quantityOut ? $totalCost / $dto->quantityOut : 0,
            totalCost: $totalCost,
            balanceQuantity: $balance->quantity,
            balanceValue: $balance->value
        );
    }
}
```

---

# 📊 6. WEIGHTED AVERAGE STRATEGY

```php
class AverageValuationStrategy
{
    public function process(InventoryTransactionDTO $dto): ValuationResult
    {
        $balance = InventoryBalance::firstOrCreate([
            'item_id' => $dto->itemId,
            'warehouse_id' => $dto->warehouseId
        ]);

        $qty = $balance->quantity;
        $value = $balance->value;

        if ($dto->quantityIn > 0) {

            $newQty = $qty + $dto->quantityIn;
            $newValue = $value + ($dto->quantityIn * $dto->unitCost);

            $avgCost = $newQty > 0 ? $newValue / $newQty : 0;

            return new ValuationResult(
                quantity: $dto->quantityIn,
                unitCost: $avgCost,
                totalCost: $dto->quantityIn * $avgCost,
                balanceQuantity: $newQty,
                balanceValue: $newValue
            );
        }

        $avgCost = $balance->average_cost;

        $newQty = $qty - $dto->quantityOut;
        $newValue = $value - ($dto->quantityOut * $avgCost);

        return new ValuationResult(
            quantity: $dto->quantityOut,
            unitCost: $avgCost,
            totalCost: $dto->quantityOut * $avgCost,
            balanceQuantity: $newQty,
            balanceValue: $newValue
        );
    }
}
```

---

# 🔢 7. SPECIFIC IDENTIFICATION STRATEGY

```php
class SpecificValuationStrategy
{
    public function process(InventoryTransactionDTO $dto): ValuationResult
    {
        $totalCost = 0;

        foreach ($dto->serials as $serial) {

            $record = InventorySerial::where('serial_number', $serial)->firstOrFail();

            if ($record->status !== 'AVAILABLE') {
                throw new \Exception("Serial not available: $serial");
            }

            $totalCost += $record->cost;

            $record->status = 'SOLD';
            $record->save();
        }

        $balance = InventoryBalance::where('item_id', $dto->itemId)
            ->where('warehouse_id', $dto->warehouseId)
            ->first();

        $qty = count($dto->serials);

        return new ValuationResult(
            quantity: $qty,
            unitCost: $qty ? $totalCost / $qty : 0,
            totalCost: $totalCost,
            balanceQuantity: $balance->quantity,
            balanceValue: $balance->value
        );
    }
}
```

---

# 📦 8. STOCK BALANCE SERVICE

```php
class StockBalanceService
{
    public function update(int $itemId, int $warehouseId, ValuationResult $result)
    {
        $balance = InventoryBalance::firstOrCreate([
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId
        ]);

        $balance->quantity = $result->balanceQuantity;
        $balance->value = $result->balanceValue;

        if ($result->balanceQuantity > 0) {
            $balance->average_cost = $result->balanceValue / $result->balanceQuantity;
        }

        $balance->save();
    }
}
```

---

# 🧮 9. ALLOCATION SERVICE (MONTH-END ENGINE)

```php
class AllocationService
{
    public function allocate(array $costPool)
    {
        $strategy = $this->resolve($costPool['method']);

        return $strategy->allocate($costPool);
    }

    private function resolve(string $method)
    {
        return match ($method) {

            'USAGE' => new UsageBasedAllocation(),

            'PERCENTAGE' => new PercentageAllocation(),

            'REVENUE' => new RevenueBasedAllocation(),

        };
    }
}
```

---

# 📊 10. USAGE BASED ALLOCATION

```php
class UsageBasedAllocation
{
    public function allocate(array $pool): array
    {
        $total = array_sum($pool['usage']);

        $result = [];

        foreach ($pool['usage'] as $key => $value) {

            $result[$key] = ($value / $total) * $pool['cost'];
        }

        return $result;
    }
}
```

---

# 📈 11. PERCENTAGE ALLOCATION

```php
class PercentageAllocation
{
    public function allocate(array $pool): array
    {
        $result = [];

        foreach ($pool['percentages'] as $key => $percent) {

            $result[$key] = ($percent / 100) * $pool['cost'];
        }

        return $result;
    }
}
```

---

# 💰 12. REVENUE BASED ALLOCATION

```php
class RevenueBasedAllocation
{
    public function allocate(array $pool): array
    {
        $totalRevenue = array_sum($pool['revenue']);

        $result = [];

        foreach ($pool['revenue'] as $key => $value) {

            $result[$key] = ($value / $totalRevenue) * $pool['cost'];
        }

        return $result;
    }
}
```

---

# 🚀 FINAL FLOW (REAL ERP)

```text
Transaction
   ↓
InventoryPostingService
   ↓
Valuation Strategy (FIFO / AVG / SPEC)
   ↓
Stock Balance Update
   ↓
Inventory Ledger Save
   ↓
(End of Month)
AllocationService
   ↓
Cost Distribution
```

---

# Section - 08

---

```php
$table->foreignId('income_account_id')->nullable()->constrained('accounts')->nullOnDelete()->comment('General ledger channel for product revenue');

$table->foreignId('cogs_account_id')->nullable()->constrained('accounts')->nullOnDelete()->comment('General ledger channel for inventory direct cost');

$table->foreignId('inventory_account_id')->nullable()->constrained('accounts')->nullOnDelete()->comment('General ledger channel for asset asset valuation');

$table->foreignId('expense_account_id')->nullable()->constrained('accounts')->nullOnDelete()->comment('General ledger channel for non-revenue overhead asset');
			
// 🔥 RETURNS
$table->foreignId('sales_return_account_id')
    ->nullable()
    ->constrained('accounts')
    ->nullOnDelete()
    ->comment('Used when customer returns goods (sales return adjustment)');

$table->foreignId('purchase_return_account_id')
    ->nullable()
    ->constrained('accounts')
    ->nullOnDelete()
    ->comment('Used when returning goods to supplier');

// 🔥 INVENTORY VARIANCE (VERY IMPORTANT FOR COUNT ADJUSTMENT)
$table->foreignId('inventory_gain_account_id')
    ->nullable()
    ->constrained('accounts')
    ->nullOnDelete()
    ->comment('Used when stock increase due to adjustment');

$table->foreignId('inventory_loss_account_id')
    ->nullable()
    ->constrained('accounts')
    ->nullOnDelete()
    ->comment('Used when stock decrease due to damage, scrap, shrinkage');

// 🔥 STOCK TRANSFER (OPTIONAL BUT PROFESSIONAL)
$table->foreignId('stock_transfer_account_id')
    ->nullable()
    ->constrained('accounts')
    ->nullOnDelete()
    ->comment('Used if transfer requires temporary clearing account');

// 🔥 PRODUCTION / WIP (if manufacturing module exists)
$table->foreignId('wip_account_id')
    ->nullable()
    ->constrained('accounts')
    ->nullOnDelete()
    ->comment('Work In Progress account for production consumption');

// 🔥 DISCOUNT / PRICE DIFFERENCE HANDLING
$table->foreignId('price_variance_account_id')
    ->nullable()
    ->constrained('accounts')
    ->nullOnDelete()
    ->comment('Used when cost vs invoice price mismatch occurs');
```

---

# 🧠 Why these are IMPORTANT (ERP logic)

| Scenario        | Account Used            |
| --------------- | ----------------------- |
| Sales return    | sales_return_account    |
| Purchase return | purchase_return_account |
| Stock damage    | inventory_loss_account  |
| Stock gain      | inventory_gain_account  |
| Production      | WIP account             |
| Price mismatch  | variance account        |

👉 Without these, your auto journal generator becomes **hardcoded & non-scalable**

---

# 🚀 PART 2 — FULL VALUATION + ALLOCATION SERVICE (FINAL CLEAN VERSION)

---

# 🧱 1. VALUE OBJECT (OUTPUT)

```php
final class ValuationResult
{
    public function __construct(
        public float $quantityIn = 0,
        public float $quantityOut = 0,
        public float $unitCost = 0,
        public float $totalCost = 0,
        public float $balanceQty = 0,
        public float $balanceValue = 0
    ) {}
}
```

---

# ⚙️ 2. MAIN SERVICE — Inventory Valuation Engine

```php
final class InventoryValuationService
{
    public function __construct(
        private FifoValuationStrategy $fifo,
        private AverageValuationStrategy $average,
        private SpecificValuationStrategy $specific
    ) {}

    public function resolve(string $method)
    {
        return match ($method) {
            'FIFO' => $this->fifo,
            'AVERAGE' => $this->average,
            'SPECIFIC' => $this->specific,
            default => throw new \InvalidArgumentException("Invalid valuation method"),
        };
    }
}
```

---

# 🔥 3. FIFO STRATEGY (FINAL CLEAN VERSION)

```php
final class FifoValuationStrategy
{
    public function __construct(
        private InventoryRepository $repo
    ) {}

    public function process(array $dto): ValuationResult
    {
        $layers = $this->repo->getLayers($dto['item_id'], $dto['warehouse_id']);

        $remaining = $dto['quantity_out'];
        $totalCost = 0;

        foreach ($layers as $layer) {

            if ($remaining <= 0) break;

            $consume = min($layer->remaining_qty, $remaining);

            $totalCost += $consume * $layer->unit_cost;

            $layer->remaining_qty -= $consume;
            $layer->save();

            $remaining -= $consume;
        }

        $balance = $this->repo->getBalance($dto['item_id'], $dto['warehouse_id']);

        return new ValuationResult(
            quantityOut: $dto['quantity_out'],
            unitCost: $dto['quantity_out'] ? $totalCost / $dto['quantity_out'] : 0,
            totalCost: $totalCost,
            balanceQty: $balance->quantity,
            balanceValue: $balance->value
        );
    }
}
```

---

# 📊 4. AVERAGE STRATEGY (FINAL)

```php
final class AverageValuationStrategy
{
    public function __construct(
        private InventoryRepository $repo
    ) {}

    public function process(array $dto): ValuationResult
    {
        $balance = $this->repo->getBalance($dto['item_id'], $dto['warehouse_id']);

        $qty = $balance->quantity;
        $value = $balance->value;

        // STOCK IN
        if ($dto['quantity_in'] > 0) {

            $newQty = $qty + $dto['quantity_in'];
            $newValue = $value + ($dto['quantity_in'] * $dto['unit_cost']);

            $avg = $newQty > 0 ? $newValue / $newQty : 0;

            return new ValuationResult(
                quantityIn: $dto['quantity_in'],
                unitCost: $avg,
                totalCost: $dto['quantity_in'] * $avg,
                balanceQty: $newQty,
                balanceValue: $newValue
            );
        }

        // STOCK OUT
        $avgCost = $balance->average_cost;

        $newQty = $qty - $dto['quantity_out'];
        $newValue = $value - ($dto['quantity_out'] * $avgCost);

        return new ValuationResult(
            quantityOut: $dto['quantity_out'],
            unitCost: $avgCost,
            totalCost: $dto['quantity_out'] * $avgCost,
            balanceQty: $newQty,
            balanceValue: $newValue
        );
    }
}
```

---

# 🔢 5. SPECIFIC STRATEGY (SERIAL BASED)

```php
final class SpecificValuationStrategy
{
    public function __construct(
        private InventoryRepository $repo
    ) {}

    public function process(array $dto): ValuationResult
    {
        $total = 0;
        $qty = count($dto['serials']);

        foreach ($dto['serials'] as $serial) {

            $record = $this->repo->getSerial($serial);

            if (!$record || $record->status !== 'AVAILABLE') {
                throw new \Exception("Invalid serial: $serial");
            }

            $total += $record->cost;

            $this->repo->updateSerial($serial, [
                'status' => 'SOLD'
            ]);
        }

        $balance = $this->repo->getBalance($dto['item_id'], $dto['warehouse_id']);

        return new ValuationResult(
            quantityOut: $qty,
            unitCost: $qty ? $total / $qty : 0,
            totalCost: $total,
            balanceQty: $balance->quantity,
            balanceValue: $balance->value
        );
    }
}
```

---

# 📦 6. ALLOCATION SERVICE (FINAL CLEAN ENGINE)

```php
final class AllocationService
{
    public function allocate(array $costPool): array
    {
        return match ($costPool['method']) {

            'USAGE' => $this->usage($costPool),

            'PERCENTAGE' => $this->percentage($costPool),

            'REVENUE' => $this->revenue($costPool),

            default => throw new \Exception("Invalid allocation method"),
        };
    }
```

---

## Usage Based

```php
private function usage(array $pool): array
{
    $total = array_sum($pool['usage']);

    return array_map(
        fn($v) => ($v / $total) * $pool['cost'],
        $pool['usage']
    );
}
```

---

## Percentage Based

```php
private function percentage(array $pool): array
{
    return array_map(
        fn($p) => ($p / 100) * $pool['cost'],
        $pool['percentages']
    );
}
```

---

## Revenue Based

```php
private function revenue(array $pool): array
{
    $total = array_sum($pool['revenue']);

    return array_map(
        fn($r) => ($r / $total) * $pool['cost'],
        $pool['revenue']
    );
}
```

}

---

# 🧠 FINAL ERP FLOW (WHAT YOU BUILT)

```text
ITEM MASTER
   ↓
TRANSACTION
   ↓
VALUATION ENGINE (FIFO / AVG / SPEC)
   ↓
INVENTORY BALANCE UPDATE
   ↓
JOURNAL GENERATION (ACCOUNT MAPPING)
   ↓
ALLOCATION ENGINE (MONTH END COST DISTRIBUTION)
```

---

# Section - 09


---

# 🧠 1. CORE IDEA (EVENT-DRIVEN ERP)

Instead of:

```text
Controller → Service → DB → Accounting (direct calls)
```

We shift to:

```text
Command → Domain Service → EVENT → Listeners → Side Effects
```

---

# 🔥 2. HIGH-LEVEL FLOW

```text
Inventory Command
      ↓
InventoryPostingService
      ↓
InventoryTransactionCreated Event
      ↓
──────────────────────────────
│ Listeners (Parallel Workers)
├─ StockBalanceUpdater
├─ FIFO Layer Manager
├─ AccountingJournalGenerator
├─ AllocationAggregator (optional)
├─ AuditLogger
──────────────────────────────
```

---

# 🧱 3. PROJECT STRUCTURE

```text
app/
 ├── Domain/
 │    ├── Inventory/
 │    │     ├── Events/
 │    │     │     ├── InventoryTransactionCreated.php
 │    │     │     ├── StockUpdated.php
 │    │     │
 │    │     ├── Listeners/
 │    │     │     ├── UpdateStockBalanceListener.php
 │    │     │     ├── UpdateFifoLayersListener.php
 │    │     │     ├── UpdateSerialTrackingListener.php
 │    │     │
 │    │     ├── Services/
 │    │     │     ├── InventoryPostingService.php
 │
 │    ├── Accounting/
 │    │     ├── Listeners/
 │    │     │     ├── GenerateJournalListener.php
 │
 │    ├── Allocation/
 │    │     ├── Listeners/
 │    │     │     ├── AllocationListener.php
```

---

# 📦 4. DOMAIN EVENT (CORE)

## InventoryTransactionCreated

```php
final class InventoryTransactionCreated
{
    public function __construct(
        public int $transactionId,
        public int $itemId,
        public int $warehouseId,
        public string $type,
        public float $quantityIn,
        public float $quantityOut,
        public float $unitCost,
        public float $totalCost,
        public array $meta = []
    ) {}
}
```

---

# ⚙️ 5. INVENTORY POSTING SERVICE (EVENT EMITTER)

```php
final class InventoryPostingService
{
    public function __construct(
        private ValuationService $valuationService
    ) {}

    public function execute(InventoryTransactionDTO $dto)
    {
        $result = $this->valuationService->process($dto);

        $transaction = InventoryTransaction::create([
            'item_id' => $dto->itemId,
            'warehouse_id' => $dto->warehouseId,
            'transaction_type' => $dto->type,

            'quantity_in' => $dto->quantityIn,
            'quantity_out' => $dto->quantityOut,

            'unit_cost' => $result->unitCost,
            'total_cost' => $result->totalCost,

            'balance_quantity' => $result->balanceQty,
            'balance_value' => $result->balanceValue,
        ]);

        // 🚀 EVENT DISPATCH (CORE SHIFT)
        event(new InventoryTransactionCreated(
            transactionId: $transaction->id,
            itemId: $dto->itemId,
            warehouseId: $dto->warehouseId,
            type: $dto->type,
            quantityIn: $dto->quantityIn,
            quantityOut: $dto->quantityOut,
            unitCost: $result->unitCost,
            totalCost: $result->totalCost,
        ));

        return $transaction;
    }
}
```

---

# 📊 6. EVENT SERVICE PROVIDER

```php
protected $listen = [

    InventoryTransactionCreated::class => [
        UpdateStockBalanceListener::class,
        UpdateFifoLayersListener::class,
        UpdateSerialTrackingListener::class,
        GenerateJournalListener::class,
        AllocationListener::class,
    ],
];
```

---

# 📦 7. STOCK BALANCE LISTENER

```php
final class UpdateStockBalanceListener
{
    public function handle(InventoryTransactionCreated $event)
    {
        $balance = InventoryBalance::firstOrCreate([
            'item_id' => $event->itemId,
            'warehouse_id' => $event->warehouseId,
        ]);

        if ($event->quantityIn > 0) {
            $balance->quantity += $event->quantityIn;
            $balance->value += $event->totalCost;
        }

        if ($event->quantityOut > 0) {
            $balance->quantity -= $event->quantityOut;
            $balance->value -= $event->totalCost;
        }

        $balance->average_cost =
            $balance->quantity > 0
                ? $balance->value / $balance->quantity
                : 0;

        $balance->save();
    }
}
```

---

# 🧊 8. FIFO LAYER LISTENER

```php
final class UpdateFifoLayersListener
{
    public function handle(InventoryTransactionCreated $event)
    {
        if ($event->type !== 'PURCHASE_RECEIPT') {
            return;
        }

        InventoryCostLayer::create([
            'item_id' => $event->itemId,
            'warehouse_id' => $event->warehouseId,
            'source_transaction_id' => $event->transactionId,
            'received_qty' => $event->quantityIn,
            'remaining_qty' => $event->quantityIn,
            'unit_cost' => $event->unitCost,
            'received_date' => now(),
        ]);
    }
}
```

---

# 🔢 9. SERIAL TRACKING LISTENER

```php
final class UpdateSerialTrackingListener
{
    public function handle(InventoryTransactionCreated $event)
    {
        if ($event->type !== 'SALES_ISSUE') {
            return;
        }

        foreach ($event->meta['serials'] ?? [] as $serial) {

            InventorySerial::where('serial_number', $serial)
                ->update([
                    'status' => 'SOLD'
                ]);
        }
    }
}
```

---

# 💰 10. ACCOUNTING LISTENER (AUTO JOURNAL)

```php
final class GenerateJournalListener
{
    public function __construct(
        private JournalGeneratorService $generator,
        private PostingService $posting
    ) {}

    public function handle(InventoryTransactionCreated $event)
    {
        $journal = $this->generator->generate([
            'type' => $event->type,
            'item_id' => $event->itemId,
            'total_cost' => $event->totalCost,
            'quantity' => $event->quantityOut ?: $event->quantityIn,
        ]);

        if ($journal) {
            $this->posting->post($journal);
        }
    }
}
```

---

# 📊 11. ALLOCATION LISTENER (MONTH-END HOOK)

```php
final class AllocationListener
{
    public function handle(InventoryTransactionCreated $event)
    {
        AllocationBuffer::create([
            'item_id' => $event->itemId,
            'warehouse_id' => $event->warehouseId,
            'cost' => $event->totalCost,
            'type' => $event->type,
        ]);
    }
}
```

👉 Later cron job runs allocation engine

---

# 🧠 12. WHY THIS DESIGN IS POWERFUL

## ✔ 1. Fully decoupled

Inventory does NOT know accounting exists

## ✔ 2. Scalable

Each listener can be queued

## ✔ 3. Parallel processing

FIFO, Accounting, Allocation run independently

## ✔ 4. Extensible

Add new feature WITHOUT touching core

Example:

```text
Add AI forecasting → just add new listener
```

---

# ⚡ 13. QUEUE ENABLEMENT (IMPORTANT)

```php
class GenerateJournalListener implements ShouldQueue {}
class UpdateStockBalanceListener implements ShouldQueue {}
```

---

# 🚀 14. FINAL ERP FLOW

```text
Transaction Posted
     ↓
Event Fired
     ↓
QUEUE SYSTEM
     ↓
Parallel Listeners:
   ├─ Stock Balance
   ├─ FIFO Layers
   ├─ Serial Tracking
   ├─ Accounting Journal
   ├─ Allocation Buffer
```

---

Section - 10


---

* Race-condition safe
* FIFO-consistent
* Multi-warehouse ready
* Queue-safe + DB-safe
* Horizontally scalable

---

# 🧠 1. PROBLEM (REAL WORLD)

When multiple requests happen at the same time:

```text
Order A → consumes FIFO layer 1
Order B → also reads same layer at same time ❌
Result: negative stock / duplicate allocation
```

---

# 🔥 2. SOLUTION ARCHITECTURE

We combine 3 layers of safety:

```text
1. DB Row Lock (FOR UPDATE)
2. Redis Distributed Lock
3. FIFO Layer Atomic Consumption Service
```

---

# 🧱 3. HIGH LEVEL FLOW

```text
Request
  ↓
Redis Lock (item+warehouse key)
  ↓
DB Transaction
  ↓
SELECT FIFO layers FOR UPDATE
  ↓
Atomic consumption
  ↓
Commit
  ↓
Release Lock
```

---

# ⚙️ 4. LOCK KEY STRATEGY

```php
inventory:lock:{tenant_id}:{item_id}:{warehouse_id}
```

Example:

```text
inventory:lock:1:1005:3
```

---

# 🧩 5. REDIS DISTRIBUTED LOCK SERVICE

```php
use Illuminate\Support\Facades\Cache;

final class InventoryLockService
{
    public function acquire(int $tenantId, int $itemId, int $warehouseId, int $ttl = 10): bool
    {
        $key = $this->key($tenantId, $itemId, $warehouseId);

        return Cache::lock($key, $ttl)->get();
    }

    public function release(int $tenantId, int $itemId, int $warehouseId): void
    {
        $key = $this->key($tenantId, $itemId, $warehouseId);

        Cache::lock($key)->release();
    }

    private function key($t, $i, $w): string
    {
        return "inventory:lock:$t:$i:$w";
    }
}
```

---

# 🧱 6. FIFO SAFE CONSUMPTION SERVICE (CORE ENGINE)

This is the **critical part**.

```php
use Illuminate\Support\Facades\DB;

final class SafeFifoConsumptionService
{
    public function __construct(
        private InventoryRepository $repo,
        private InventoryLockService $lock
    ) {}

    public function consume(array $dto): array
    {
        $lockAcquired = $this->lock->acquire(
            $dto['tenant_id'],
            $dto['item_id'],
            $dto['warehouse_id']
        );

        if (!$lockAcquired) {
            throw new \Exception("Inventory is locked, retry request");
        }

        try {
            return DB::transaction(function () use ($dto) {

                // 🔒 LOCK FIFO LAYERS (DB LEVEL SAFETY)
                $layers = $this->repo->getLayersForUpdate(
                    $dto['item_id'],
                    $dto['warehouse_id']
                );

                $remaining = $dto['quantity'];
                $totalCost = 0;

                foreach ($layers as $layer) {

                    if ($remaining <= 0) break;

                    if ($layer->remaining_qty <= 0) continue;

                    $consume = min($layer->remaining_qty, $remaining);

                    // ⚠️ ATOMIC UPDATE (NO RACE CONDITION)
                    $layer->remaining_qty -= $consume;
                    $layer->save();

                    $totalCost += $consume * $layer->unit_cost;
                    $remaining -= $consume;
                }

                if ($remaining > 0) {
                    throw new \Exception("Insufficient stock during FIFO allocation");
                }

                return [
                    'total_cost' => $totalCost,
                    'unit_cost' => $totalCost / $dto['quantity'],
                ];
            });

        } finally {
            $this->lock->release(
                $dto['tenant_id'],
                $dto['item_id'],
                $dto['warehouse_id']
            );
        }
    }
}
```

---

# 🧠 7. CRITICAL DB LAYER IMPROVEMENT

## FIFO Layer Query MUST use locking:

```php
public function getLayersForUpdate($itemId, $warehouseId)
{
    return InventoryCostLayer::where('item_id', $itemId)
        ->where('warehouse_id', $warehouseId)
        ->where('remaining_qty', '>', 0)
        ->orderBy('id')
        ->lockForUpdate()
        ->get();
}
```

---

# ⚡ 8. WHY THIS IS RACE-CONDITION SAFE

## ✔ Redis lock

Prevents parallel requests across servers

## ✔ DB transaction

Ensures atomicity

## ✔ SELECT FOR UPDATE

Prevents concurrent row modification

## ✔ Sequential FIFO loop

Guarantees correct ordering

---

# 🚀 9. OPTIONAL ENHANCEMENT (PRO LEVEL)

## Retry mechanism

```php
if (!$lockAcquired) {
    usleep(200000); // 200ms
    return $this->consume($dto);
}
```

---

## Queue-based FIFO (Ultra scalable)

```text
Order → Queue → Worker → Locked FIFO Engine
```

---

# 🧱 10. DEADLOCK PREVENTION STRATEGY

Always enforce:

```text
LOCK ORDER RULE:

tenant → warehouse → item
```

This prevents DB deadlocks in high concurrency.

---

# 📊 11. FINAL FLOW (PRODUCTION GRADE)

```text
Request
  ↓
Redis Lock
  ↓
DB Transaction
  ↓
FIFO Layers (FOR UPDATE)
  ↓
Atomic Consumption
  ↓
Balance Update
  ↓
Release Lock
```

---

# 🧠 12. DESIGN BENEFITS

✔ No duplicate stock consumption
✔ No race conditions across servers
✔ Safe for microservices / scaling
✔ FIFO integrity guaranteed
✔ Works with queues + API + batch jobs

---

# Section - 11

---

```php
// Purchase related
$table->foreignId('purchase_account_id')
    ->nullable()
    ->constrained('accounts')
    ->nullOnDelete()
    ->comment('Default procurement or supplier-side expense clearing account');

// Inventory adjustment
$table->foreignId('inventory_adjustment_account_id')
    ->nullable()
    ->constrained('accounts')
    ->nullOnDelete()
    ->comment('Inventory gain/loss adjustment postings from stock corrections');

// Inventory variance
$table->foreignId('inventory_variance_account_id')
    ->nullable()
    ->constrained('accounts')
    ->nullOnDelete()
    ->comment('Inventory costing variance difference reconciliation account');

// Sales return
$table->foreignId('sales_return_account_id')
    ->nullable()
    ->constrained('accounts')
    ->nullOnDelete()
    ->comment('Revenue reversal ledger for customer sales returns');

// Purchase return
$table->foreignId('purchase_return_account_id')
    ->nullable()
    ->constrained('accounts')
    ->nullOnDelete()
    ->comment('Supplier-side procurement reversal and return ledger');

// WIP account
$table->foreignId('wip_account_id')
    ->nullable()
    ->constrained('accounts')
    ->nullOnDelete()
    ->comment('Work-in-progress interim inventory capitalization account');

// Deferred revenue
$table->foreignId('deferred_revenue_account_id')
    ->nullable()
    ->constrained('accounts')
    ->nullOnDelete()
    ->comment('Unearned customer revenue liability holding account');

// Stock output / goods issued
$table->foreignId('stock_output_account_id')
    ->nullable()
    ->constrained('accounts')
    ->nullOnDelete()
    ->comment('Temporary stock issued clearing account before final settlement');
```

---

# ✅ Recommended Valuation Methods

professional inventory valuation methods:

| Method                  | Description                       |
| ----------------------- | --------------------------------- |
| FIFO                    | First In First Out                |
| LIFO                    | Last In First Out                 |
| WEIGHTED_AVERAGE        | Moving weighted average costing   |
| STANDARD_COST           | Fixed predefined standard costing |
| SPECIFIC_IDENTIFICATION | Serial/batch-specific costing     |
| FEFO                    | First Expired First Out           |
| MANUAL                  | User-entered manual valuation     |

---

# ✅ Recommended Allocation Methods

| Method     | Description                          |
| ---------- | ------------------------------------ |
| QUANTITY   | Allocate by quantity                 |
| VALUE      | Allocate by monetary value           |
| WEIGHT     | Allocate by item weight              |
| VOLUME     | Allocate by cubic volume             |
| EQUAL      | Equal distribution                   |
| MANUAL     | User-defined allocation              |
| TIME       | Allocate by operational/service time |
| PERCENTAGE | Percentage-based allocation          |

---

# ✅ Fully Functional Valuation Service

```php
<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use InvalidArgumentException;

class ValuationService
{
    public function calculate(
        string $method,
        float $quantity,
        array $layers,
        ?float $standardCost = null
    ): float {
        return match ($method) {

            'FIFO' => $this->fifo($quantity, $layers),

            'LIFO' => $this->lifo($quantity, $layers),

            'WEIGHTED_AVERAGE' => $this->weightedAverage($quantity, $layers),

            'STANDARD_COST' => $this->standardCost(
                $quantity,
                $standardCost
            ),

            'SPECIFIC_IDENTIFICATION' => $this->specificIdentification(
                $quantity,
                $layers
            ),

            default => throw new InvalidArgumentException(
                "Unsupported valuation method: {$method}"
            )
        };
    }

    protected function fifo(float $quantity, array $layers): float
    {
        $remaining = $quantity;
        $total = 0;

        foreach ($layers as $layer) {

            if ($remaining <= 0) {
                break;
            }

            $take = min($remaining, $layer['qty']);

            $total += $take * $layer['cost'];

            $remaining -= $take;
        }

        return round($total, 4);
    }

    protected function lifo(float $quantity, array $layers): float
    {
        return $this->fifo(
            $quantity,
            array_reverse($layers)
        );
    }

    protected function weightedAverage(
        float $quantity,
        array $layers
    ): float {

        $totalQty = array_sum(array_column($layers, 'qty'));

        if ($totalQty <= 0) {
            return 0;
        }

        $totalCost = 0;

        foreach ($layers as $layer) {
            $totalCost += $layer['qty'] * $layer['cost'];
        }

        $average = $totalCost / $totalQty;

        return round($quantity * $average, 4);
    }

    protected function standardCost(
        float $quantity,
        ?float $standardCost
    ): float {

        return round(
            $quantity * ($standardCost ?? 0),
            4
        );
    }

    protected function specificIdentification(
        float $quantity,
        array $layers
    ): float {

        $total = 0;

        foreach ($layers as $layer) {
            $total += $layer['qty'] * $layer['cost'];
        }

        return round($total, 4);
    }
}
```

---

# ✅ Fully Functional Allocation Service

```php
<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use InvalidArgumentException;

class AllocationService
{
    public function allocate(
        string $method,
        float $totalAmount,
        array $items
    ): array {

        return match ($method) {

            'QUANTITY' => $this->byField(
                $totalAmount,
                $items,
                'quantity'
            ),

            'VALUE' => $this->byField(
                $totalAmount,
                $items,
                'value'
            ),

            'WEIGHT' => $this->byField(
                $totalAmount,
                $items,
                'weight'
            ),

            'VOLUME' => $this->byField(
                $totalAmount,
                $items,
                'volume'
            ),

            'TIME' => $this->byField(
                $totalAmount,
                $items,
                'time'
            ),

            'EQUAL' => $this->equal(
                $totalAmount,
                $items
            ),

            'PERCENTAGE' => $this->percentage(
                $totalAmount,
                $items
            ),

            default => throw new InvalidArgumentException(
                "Unsupported allocation method: {$method}"
            )
        };
    }

    protected function byField(
        float $totalAmount,
        array $items,
        string $field
    ): array {

        $basisTotal = array_sum(
            array_column($items, $field)
        );

        if ($basisTotal <= 0) {
            return [];
        }

        $allocations = [];

        foreach ($items as $item) {

            $ratio = $item[$field] / $basisTotal;

            $allocations[] = [
                'item_id' => $item['id'],
                'allocated_amount' => round(
                    $totalAmount * $ratio,
                    4
                )
            ];
        }

        return $allocations;
    }

    protected function equal(
        float $totalAmount,
        array $items
    ): array {

        $count = count($items);

        if ($count === 0) {
            return [];
        }

        $share = round($totalAmount / $count, 4);

        return array_map(function ($item) use ($share) {

            return [
                'item_id' => $item['id'],
                'allocated_amount' => $share
            ];

        }, $items);
    }

    protected function percentage(
        float $totalAmount,
        array $items
    ): array {

        $allocations = [];

        foreach ($items as $item) {

            $allocations[] = [
                'item_id' => $item['id'],
                'allocated_amount' => round(
                    $totalAmount * (
                        ($item['percentage'] ?? 0) / 100
                    ),
                    4
                )
            ];
        }

        return $allocations;
    }
}
```

---

# ✅ Suggested ENUM Constants

```php
class ValuationMethod
{
    public const FIFO = 'FIFO';
    public const LIFO = 'LIFO';
    public const WEIGHTED_AVERAGE = 'WEIGHTED_AVERAGE';
    public const STANDARD_COST = 'STANDARD_COST';
    public const SPECIFIC_IDENTIFICATION = 'SPECIFIC_IDENTIFICATION';
    public const FEFO = 'FEFO';
    public const MANUAL = 'MANUAL';
}
```

```php
class AllocationMethod
{
    public const QUANTITY = 'QUANTITY';
    public const VALUE = 'VALUE';
    public const WEIGHT = 'WEIGHT';
    public const VOLUME = 'VOLUME';
    public const EQUAL = 'EQUAL';
    public const MANUAL = 'MANUAL';
    public const TIME = 'TIME';
    public const PERCENTAGE = 'PERCENTAGE';
}
```

---

# ✅ Recommended Validation Rules

```php
'valuation_method' => [
    'nullable',
    Rule::in([
        'FIFO',
        'LIFO',
        'WEIGHTED_AVERAGE',
        'STANDARD_COST',
        'SPECIFIC_IDENTIFICATION',
        'FEFO',
        'MANUAL',
    ])
],

'allocation_method' => [
    'nullable',
    Rule::in([
        'QUANTITY',
        'VALUE',
        'WEIGHT',
        'VOLUME',
        'EQUAL',
        'MANUAL',
        'TIME',
        'PERCENTAGE',
    ])
],
```

---

# ✅ Enterprise Recommendation

Production ERP:

* FIFO → most common
* Weighted Average → retail/manufacturing
* Specific Identification → serial tracked inventory
* Standard Cost → manufacturing ERP
* FEFO → pharma/food industries

Allocation:

* Quantity → freight allocation
* Value → insurance/customs
* Weight/Volume → logistics
* Time → services/projects

---

# Section - 12

---



Inventory Domain Kernel.

---

# 🧠 1. FINAL ARCHITECTURE

```text
Domain/
 ├── Inventory/
 │    ├── Services/
 │    │     ├── InventoryEngine.php
 │    │     ├── InventoryCommandService.php
 │    │     ├── StockBalanceService.php
 │    │     ├── InventoryStateService.php
 │
 │    ├── Valuation/
 │    │     ├── Contracts/ValuationStrategy.php
 │    │     ├── FIFO.php
 │    │     ├── AVG.php
 │    │     ├── SPEC.php
 │    │     ├── ValuationManager.php
 │
 │    ├── Allocation/
 │    │     ├── Contracts/AllocationStrategy.php
 │    │     ├── Usage.php
 │    │     ├── Percentage.php
 │    │     ├── Revenue.php
 │    │     ├── AllocationManager.php
 │
 │    ├── Accounting/
 │    │     ├── JournalService.php
 │    │     ├── AccountResolver.php
 │
 │    ├── Events/
 │    │     ├── InventoryTransactionCreated.php
 │
 │    ├── DTO/
 │    │     ├── InventoryCommand.php
 │    │     ├── ValuationResult.php
```

---

# ⚙️ 2. CORE DTO (UNIVERSAL INPUT)

```php
final class InventoryCommand
{
    public function __construct(
        public int $tenantId,
        public int $itemId,
        public int $warehouseId,
        public string $type,

        public float $quantityIn = 0,
        public float $quantityOut = 0,

        public float $unitCost = 0,
        public float $salesValue = 0,

        public array $serials = [],
        public array $meta = []
    ) {}
}
```

---

# 🧠 3. CORE ENGINE (ENTRY POINT)

```php
final class InventoryEngine
{
    public function __construct(
        private InventoryCommandService $commandService
    ) {}

    public function execute(InventoryCommand $cmd)
    {
        return $this->commandService->process($cmd);
    }
}
```

---

# ⚙️ 4. COMMAND SERVICE (ORCHESTRATOR)

```php
final class InventoryCommandService
{
    public function __construct(
        private ValuationManager $valuation,
        private StockBalanceService $balance,
        private JournalService $journal
    ) {}

    public function process(InventoryCommand $cmd)
    {
        // 1. VALUATION
        $result = $this->valuation->resolve(
            $cmd->itemId
        )->process($cmd);

        // 2. UPDATE BALANCE
        $this->balance->apply($cmd, $result);

        // 3. JOURNAL ENTRY
        $this->journal->generate($cmd, $result);

        // 4. EVENT (ASYNC EXTENSIONS)
        event(new InventoryTransactionCreated($cmd, $result));

        return $result;
    }
}
```

---

# 🧮 5. VALUATION SYSTEM (PLUGGABLE)

## CONTRACT

```php
interface ValuationStrategy
{
    public function process(InventoryCommand $cmd): ValuationResult;
}
```

---

## MANAGER

```php
final class ValuationManager
{
    public function resolve(int $itemId): ValuationStrategy
    {
        $method = Item::find($itemId)->valuation_method;

        return match ($method) {
            'FIFO' => app(FIFO::class),
            'AVG' => app(AVG::class),
            'SPEC' => app(SPEC::class),
            default => throw new \Exception("Invalid valuation method")
        };
    }
}
```

---

# 🔥 6. FIFO IMPLEMENTATION (FULL SAFE)

```php
final class FIFO implements ValuationStrategy
{
    public function process(InventoryCommand $cmd): ValuationResult
    {
        $layers = InventoryCostLayer::where('item_id', $cmd->itemId)
            ->where('warehouse_id', $cmd->warehouseId)
            ->where('remaining_qty', '>', 0)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $remaining = $cmd->quantityOut;
        $cost = 0;

        foreach ($layers as $layer) {

            if ($remaining <= 0) break;

            $consume = min($layer->remaining_qty, $remaining);

            $layer->remaining_qty -= $consume;
            $layer->save();

            $cost += $consume * $layer->unit_cost;
            $remaining -= $consume;
        }

        return new ValuationResult(
            quantity: $cmd->quantityOut,
            unitCost: $cmd->quantityOut ? $cost / $cmd->quantityOut : 0,
            totalCost: $cost
        );
    }
}
```

---

# 📊 7. AVERAGE COST

```php
final class AVG implements ValuationStrategy
{
    public function process(InventoryCommand $cmd): ValuationResult
    {
        $balance = InventoryBalance::firstOrCreate([
            'item_id' => $cmd->itemId,
            'warehouse_id' => $cmd->warehouseId,
        ]);

        $newQty = $balance->quantity + $cmd->quantityIn;
        $newValue = $balance->value + ($cmd->quantityIn * $cmd->unitCost);

        $avg = $newQty > 0 ? $newValue / $newQty : 0;

        return new ValuationResult(
            quantity: $cmd->quantityIn ?: $cmd->quantityOut,
            unitCost: $avg,
            totalCost: ($cmd->quantityIn ?: $cmd->quantityOut) * $avg
        );
    }
}
```

---

# 🔢 8. SPECIFIC COST

```php
final class SPEC implements ValuationStrategy
{
    public function process(InventoryCommand $cmd): ValuationResult
    {
        $cost = 0;

        foreach ($cmd->serials as $serial) {

            $item = InventorySerial::where('serial_number', $serial)->firstOrFail();

            $cost += $item->cost;
            $item->status = 'SOLD';
            $item->save();
        }

        $qty = count($cmd->serials);

        return new ValuationResult(
            quantity: $qty,
            unitCost: $qty ? $cost / $qty : 0,
            totalCost: $cost
        );
    }
}
```

---

# 📦 9. STOCK BALANCE SERVICE

```php
final class StockBalanceService
{
    public function apply(InventoryCommand $cmd, ValuationResult $res)
    {
        $balance = InventoryBalance::firstOrCreate([
            'item_id' => $cmd->itemId,
            'warehouse_id' => $cmd->warehouseId,
        ]);

        if ($cmd->quantityIn > 0) {
            $balance->quantity += $cmd->quantityIn;
            $balance->value += $res->totalCost;
        }

        if ($cmd->quantityOut > 0) {
            $balance->quantity -= $cmd->quantityOut;
            $balance->value -= $res->totalCost;
        }

        $balance->average_cost =
            $balance->quantity > 0
                ? $balance->value / $balance->quantity
                : 0;

        $balance->save();
    }
}
```

---

# 💰 10. JOURNAL SERVICE (ACCOUNTING LAYER)

```php
final class JournalService
{
    public function __construct(
        private AccountResolver $accounts
    ) {}

    public function generate(InventoryCommand $cmd, ValuationResult $res)
    {
        if ($cmd->type === 'SALES_ISSUE') {

            JournalEntry::create([
                'debit' => $this->accounts->cogs($cmd->itemId),
                'credit' => $this->accounts->inventory($cmd->itemId),
                'amount' => $res->totalCost
            ]);
        }

        if ($cmd->type === 'PURCHASE_RECEIPT') {

            JournalEntry::create([
                'debit' => $this->accounts->inventory($cmd->itemId),
                'credit' => $this->accounts->ap(),
                'amount' => $res->totalCost
            ]);
        }
    }
}
```

---

# 📊 11. ALLOCATION SYSTEM (EXTENSIBLE)

## Contract

```php
interface AllocationStrategy
{
    public function allocate(array $pool): array;
}
```

---

## Manager

```php
final class AllocationManager
{
    public function resolve(string $method)
    {
        return match ($method) {
            'USAGE' => app(UsageAllocation::class),
            'PERCENTAGE' => app(PercentageAllocation::class),
            'REVENUE' => app(RevenueAllocation::class),
        };
    }
}
```

---

# 🚀 12. EVENT SYSTEM (EXTENSION POINT)

```php
final class InventoryTransactionCreated
{
    public function __construct(
        public InventoryCommand $cmd,
        public ValuationResult $result
    ) {}
}
```

---

# 🧠 13. FULL FLOW

```text
Controller
   ↓
InventoryEngine
   ↓
InventoryCommandService
   ↓
Valuation Strategy (FIFO/AVG/SPEC)
   ↓
Stock Balance Update
   ↓
Journal Service
   ↓
Event Fired
   ↓
Allocation / Projection / Audit / Analytics
```

---

# Section - 13


---

# 🧠 1. FINAL ARCHITECTURE

```text
Domain/
 ├── Inventory/
 │    ├── DTO/
 │    │    ├── InventoryCommand.php
 │    │    ├── ValuationResult.php
 │
 │    ├── Valuation/
 │    │    ├── Contracts/ValuationStrategy.php
 │    │    ├── FIFOValuation.php
 │    │    ├── AverageValuation.php
 │    │    ├── SpecificValuation.php
 │    │    ├── ValuationManager.php
 │
 │    ├── Allocation/
 │    │    ├── Contracts/AllocationStrategy.php
 │    │    ├── UsageAllocation.php
 │    │    ├── PercentageAllocation.php
 │    │    ├── RevenueAllocation.php
 │    │    ├── AllocationManager.php
 │
 │    ├── Services/
 │    │    ├── InventoryEngine.php
 │    │    ├── InventoryService.php
 │    │    ├── StockBalanceService.php
 │    │    ├── JournalService.php
```

---

# 📦 2. CORE DTOs

## Inventory Command

```php
final class InventoryCommand
{
    public function __construct(
        public int $tenantId,
        public int $itemId,
        public int $warehouseId,
        public string $type,

        public float $quantityIn = 0,
        public float $quantityOut = 0,

        public float $unitCost = 0,
        public float $salesValue = 0,

        public array $serials = [],
        public array $meta = []
    ) {}
}
```

---

## Valuation Result

```php
final class ValuationResult
{
    public function __construct(
        public float $quantity = 0,
        public float $unitCost = 0,
        public float $totalCost = 0,
        public float $balanceQty = 0,
        public float $balanceValue = 0
    ) {}
}
```

---

# ⚙️ 3. VALUATION SYSTEM

---

## CONTRACT

```php
interface ValuationStrategy
{
    public function process(InventoryCommand $cmd): ValuationResult;
}
```

---

## MANAGER (FACTORY)

```php
final class ValuationManager
{
    public function resolve(int $itemId): ValuationStrategy
    {
        $method = Item::find($itemId)->valuation_method;

        return match ($method) {

            'FIFO' => app(FIFOValuation::class),

            'AVERAGE' => app(AverageValuation::class),

            'SPECIFIC' => app(SpecificValuation::class),

            default => throw new \Exception("Invalid valuation method"),
        };
    }
}
```

---

# 🔥 4. FIFO VALUATION (FULL IMPLEMENTATION)

```php
final class FIFOValuation implements ValuationStrategy
{
    public function process(InventoryCommand $cmd): ValuationResult
    {
        $layers = InventoryCostLayer::where('item_id', $cmd->itemId)
            ->where('warehouse_id', $cmd->warehouseId)
            ->where('remaining_qty', '>', 0)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $remaining = $cmd->quantityOut;
        $totalCost = 0;

        foreach ($layers as $layer) {

            if ($remaining <= 0) break;

            $consume = min($layer->remaining_qty, $remaining);

            $layer->remaining_qty -= $consume;
            $layer->save();

            $totalCost += $consume * $layer->unit_cost;
            $remaining -= $consume;
        }

        return new ValuationResult(
            quantity: $cmd->quantityOut,
            unitCost: $cmd->quantityOut ? $totalCost / $cmd->quantityOut : 0,
            totalCost: $totalCost
        );
    }
}
```

---

# 📊 5. AVERAGE COST VALUATION

```php
final class AverageValuation implements ValuationStrategy
{
    public function process(InventoryCommand $cmd): ValuationResult
    {
        $balance = InventoryBalance::firstOrCreate([
            'item_id' => $cmd->itemId,
            'warehouse_id' => $cmd->warehouseId,
        ]);

        $qty = $balance->quantity;
        $value = $balance->value;

        // STOCK IN
        if ($cmd->quantityIn > 0) {

            $newQty = $qty + $cmd->quantityIn;
            $newValue = $value + ($cmd->quantityIn * $cmd->unitCost);

            $avg = $newQty > 0 ? $newValue / $newQty : 0;

            return new ValuationResult(
                quantity: $cmd->quantityIn,
                unitCost: $avg,
                totalCost: $cmd->quantityIn * $avg,
                balanceQty: $newQty,
                balanceValue: $newValue
            );
        }

        // STOCK OUT
        $avgCost = $balance->average_cost;

        $newQty = $qty - $cmd->quantityOut;
        $newValue = $value - ($cmd->quantityOut * $avgCost);

        return new ValuationResult(
            quantity: $cmd->quantityOut,
            unitCost: $avgCost,
            totalCost: $cmd->quantityOut * $avgCost,
            balanceQty: $newQty,
            balanceValue: $newValue
        );
    }
}
```

---

# 🔢 6. SPECIFIC IDENTIFICATION VALUATION

```php
final class SpecificValuation implements ValuationStrategy
{
    public function process(InventoryCommand $cmd): ValuationResult
    {
        $total = 0;

        foreach ($cmd->serials as $serial) {

            $record = InventorySerial::where('serial_number', $serial)->firstOrFail();

            if ($record->status !== 'AVAILABLE') {
                throw new \Exception("Serial not available: $serial");
            }

            $total += $record->cost;

            $record->status = 'SOLD';
            $record->save();
        }

        $qty = count($cmd->serials);

        return new ValuationResult(
            quantity: $qty,
            unitCost: $qty ? $total / $qty : 0,
            totalCost: $total
        );
    }
}
```

---

# 🧠 7. ALLOCATION SYSTEM

---

## CONTRACT

```php
interface AllocationStrategy
{
    public function allocate(array $pool): array;
}
```

---

## MANAGER

```php
final class AllocationManager
{
    public function resolve(string $method): AllocationStrategy
    {
        return match ($method) {

            'USAGE' => app(UsageAllocation::class),

            'PERCENTAGE' => app(PercentageAllocation::class),

            'REVENUE' => app(RevenueAllocation::class),

            default => throw new \Exception("Invalid allocation method"),
        };
    }
}
```

---

# 📊 8. USAGE ALLOCATION

```php
final class UsageAllocation implements AllocationStrategy
{
    public function allocate(array $pool): array
    {
        $totalUsage = array_sum($pool['usage']);

        $result = [];

        foreach ($pool['usage'] as $key => $value) {

            $result[$key] = ($value / $totalUsage) * $pool['cost'];
        }

        return $result;
    }
}
```

---

# 📊 9. PERCENTAGE ALLOCATION

```php
final class PercentageAllocation implements AllocationStrategy
{
    public function allocate(array $pool): array
    {
        $result = [];

        foreach ($pool['percentages'] as $key => $percent) {

            $result[$key] = ($percent / 100) * $pool['cost'];
        }

        return $result;
    }
}
```

---

# 📊 10. REVENUE ALLOCATION

```php
final class RevenueAllocation implements AllocationStrategy
{
    public function allocate(array $pool): array
    {
        $totalRevenue = array_sum($pool['revenue']);

        $result = [];

        foreach ($pool['revenue'] as $key => $value) {

            $result[$key] = ($value / $totalRevenue) * $pool['cost'];
        }

        return $result;
    }
}
```

---

# ⚙️ 11. CORE INVENTORY ENGINE

```php
final class InventoryEngine
{
    public function __construct(
        private ValuationManager $valuationManager,
        private StockBalanceService $balanceService,
        private JournalService $journalService,
        private AllocationManager $allocationManager
    ) {}

    public function process(InventoryCommand $cmd)
    {
        // 1. VALUATION
        $valuation = $this->valuationManager
            ->resolve($cmd->itemId)
            ->process($cmd);

        // 2. BALANCE UPDATE
        $this->balanceService->apply($cmd, $valuation);

        // 3. JOURNAL ENTRY
        $this->journalService->generate($cmd, $valuation);

        // 4. OPTIONAL ALLOCATION (buffer or immediate)
        if (isset($cmd->meta['allocation'])) {

            $this->allocationManager
                ->resolve($cmd->meta['allocation']['method'])
                ->allocate($cmd->meta['allocation']);
        }

        return $valuation;
    }
}
```

---

# 📦 12. STOCK BALANCE SERVICE

```php
final class StockBalanceService
{
    public function apply(InventoryCommand $cmd, ValuationResult $res)
    {
        $balance = InventoryBalance::firstOrCreate([
            'item_id' => $cmd->itemId,
            'warehouse_id' => $cmd->warehouseId,
        ]);

        if ($cmd->quantityIn > 0) {
            $balance->quantity += $cmd->quantityIn;
            $balance->value += $res->totalCost;
        }

        if ($cmd->quantityOut > 0) {
            $balance->quantity -= $cmd->quantityOut;
            $balance->value -= $res->totalCost;
        }

        $balance->average_cost =
            $balance->quantity > 0
                ? $balance->value / $balance->quantity
                : 0;

        $balance->save();
    }
}
```

---

# 💰 13. JOURNAL SERVICE (ACCOUNTING READY)

```php
final class JournalService
{
    public function generate(InventoryCommand $cmd, ValuationResult $res)
    {
        if ($cmd->type === 'SALES_ISSUE') {

            JournalEntry::create([
                'type' => 'COGS',
                'amount' => $res->totalCost,
            ]);
        }

        if ($cmd->type === 'PURCHASE_RECEIPT') {

            JournalEntry::create([
                'type' => 'INVENTORY',
                'amount' => $res->totalCost,
            ]);
        }
    }
}
```

---

# 🚀 FINAL SYSTEM FLOW

```text
Controller
  ↓
InventoryEngine
  ↓
ValuationManager (FIFO / AVG / SPEC)
  ↓
StockBalanceService
  ↓
JournalService
  ↓
AllocationManager (optional)
```

---

# Section - 14

---



# 🧠 1. PROBLEM IN MULTI-WAREHOUSE FIFO

Without proper locking:

```text
Warehouse A + B + C
   ↓
Same FIFO layers accessed simultaneously
   ↓
Double consumption ❌
Negative stock ❌
Broken valuation ❌
```

---

# 🔥 2. ARCHITECTURE OVERVIEW

We combine 4 layers:

```text
1. Redis Distributed Lock (cross-server safety)
2. DB Row Lock (FOR UPDATE)
3. Warehouse-level isolation
4. FIFO atomic layer consumption engine
```

---

# 🧱 3. LOCKING STRATEGY (KEY DESIGN)

## GLOBAL LOCK KEY

```text
fifo:tenant:{tenantId}:item:{itemId}
```

## WAREHOUSE LOCK KEY

```text
fifo:tenant:{tenantId}:item:{itemId}:warehouse:{warehouseId}
```

👉 We use **2-level locking**

* Global prevents cross-warehouse race
* Warehouse lock ensures local FIFO correctness

---

# ⚙️ 4. REDIS DISTRIBUTED LOCK SERVICE

```php
final class FifoLockService
{
    public function lock(int $tenantId, int $itemId, int $warehouseId)
    {
        $globalKey = $this->globalKey($tenantId, $itemId);
        $whKey = $this->warehouseKey($tenantId, $itemId, $warehouseId);

        $globalLock = Cache::lock($globalKey, 10);
        $warehouseLock = Cache::lock($whKey, 10);

        if (!$globalLock->get()) {
            throw new \Exception("Global FIFO lock failed");
        }

        if (!$warehouseLock->get()) {
            $globalLock->release();
            throw new \Exception("Warehouse FIFO lock failed");
        }

        return [$globalLock, $warehouseLock];
    }

    public function release($locks): void
    {
        foreach ($locks as $lock) {
            $lock->release();
        }
    }

    private function globalKey($t, $i): string
    {
        return "fifo:tenant:$t:item:$i";
    }

    private function warehouseKey($t, $i, $w): string
    {
        return "fifo:tenant:$t:item:$i:warehouse:$w";
    }
}
```

---

# 🧊 5. CORE FIFO ENGINE (DISTRIBUTED SAFE)

```php
final class DistributedFifoEngine
{
    public function __construct(
        private FifoLockService $lockService
    ) {}

    public function consume(array $dto): array
    {
        [$globalLock, $whLock] = $this->lockService->lock(
            $dto['tenant_id'],
            $dto['item_id'],
            $dto['warehouse_id']
        );

        try {

            return DB::transaction(function () use ($dto) {

                return $this->processFifo($dto);

            }, 3); // retry deadlocks

        } finally {
            $this->lockService->release([$globalLock, $whLock]);
        }
    }
```

---

## FIFO PROCESSOR (CORE LOGIC)

```php
private function processFifo(array $dto): array
{
    $layers = InventoryCostLayer::where('tenant_id', $dto['tenant_id'])
        ->where('item_id', $dto['item_id'])
        ->where('warehouse_id', $dto['warehouse_id'])
        ->where('remaining_qty', '>', 0)
        ->orderBy('id')
        ->lockForUpdate()
        ->get();

    $remaining = $dto['quantity'];
    $totalCost = 0;

    foreach ($layers as $layer) {

        if ($remaining <= 0) break;

        $consume = min($layer->remaining_qty, $remaining);

        $layer->remaining_qty -= $consume;
        $layer->save();

        $totalCost += $consume * $layer->unit_cost;
        $remaining -= $consume;
    }

    if ($remaining > 0) {
        throw new \Exception("Insufficient stock in FIFO layers");
    }

    return [
        'total_cost' => $totalCost,
        'unit_cost' => $totalCost / $dto['quantity'],
    ];
}
```

---

# 🏢 6. MULTI-WAREHOUSE RULE ENGINE

Each warehouse has isolated FIFO pools:

```text
Warehouse A → FIFO Stack A
Warehouse B → FIFO Stack B
Warehouse C → FIFO Stack C
```

But global lock ensures:

```text
No cross-warehouse race during valuation snapshot
```

---

# ⚡ 7. DEADLOCK PREVENTION STRATEGY

We enforce strict ordering:

```text
LOCK ORDER:

1. tenant
2. item
3. warehouse
```

---

# 🧠 8. OPTIONAL OPTIMIZATION (HIGH SCALE)

## Split lock strategy (better performance)

Instead of global lock always:

```php
if ($dto['quantity'] < THRESHOLD) {
    use warehouse lock only;
} else {
    use global + warehouse lock;
}
```

---

# 🚀 9. QUEUE-BASED SAFE MODE (ENTERPRISE)

For high traffic systems:

```text
API → Queue → FIFO Worker → Locked Engine
```

Worker ensures:

* single-thread per item/warehouse
* no race condition possible

---

# 📦 10. FINAL SYSTEM FLOW

```text
Request
   ↓
Redis Global Lock (Item level)
   ↓
Redis Warehouse Lock
   ↓
DB Transaction
   ↓
SELECT FOR UPDATE FIFO layers
   ↓
Atomic consumption
   ↓
Commit
   ↓
Release locks
```

---

# Section - 15

---

**Forecasting Engine** that supports multiple forecasting strategies. 

```text
Sales History
Purchase History
Inventory History
Seasonality Data
Lead Time Data
       │
       ▼
Forecast Engine
       │
       ├── Moving Average Forecast
       ├── Weighted Average Forecast
       ├── Exponential Smoothing Forecast
       ├── Seasonal Forecast
       ├── ML Forecast (future)
       │
       ▼
Demand Forecast
       │
       ├── Reorder Point
       ├── Safety Stock
       ├── Purchase Recommendation
       ├── Transfer Recommendation
       └── Stockout Alerts
```

---

# Forecast Domain Structure

```text
Domain/
 ├── Forecasting/
 │
 ├── Contracts/
 │   ├── ForecastStrategy.php
 │
 ├── DTO/
 │   ├── ForecastRequest.php
 │   ├── ForecastResult.php
 │
 ├── Strategies/
 │   ├── MovingAverageForecast.php
 │   ├── WeightedMovingAverageForecast.php
 │   ├── ExponentialSmoothingForecast.php
 │   ├── SeasonalForecast.php
 │   ├── MachineLearningForecast.php
 │
 ├── Services/
 │   ├── ForecastEngine.php
 │   ├── ReorderPointService.php
 │   ├── SafetyStockService.php
 │   ├── PurchaseRecommendationService.php
 │
 └── Repositories/
     ├── ForecastRepository.php
```

---

# Forecast Request DTO

```php
final readonly class ForecastRequest
{
    public function __construct(
        public int $tenantId,
        public int $itemId,
        public ?int $warehouseId,
        public string $method,
        public int $forecastDays = 30,
        public int $historyDays = 365,
    ) {
    }
}
```

---

# Forecast Result DTO

```php
final readonly class ForecastResult
{
    public function __construct(
        public float $dailyDemand,
        public float $monthlyDemand,
        public float $forecastQuantity,
        public float $confidence,
        public array $details = [],
    ) {
    }
}
```

---

# Forecast Strategy Contract

```php
interface ForecastStrategy
{
    public function forecast(
        ForecastRequest $request
    ): ForecastResult;
}
```

---

# Moving Average Forecast

Simple and reliable.

```php
final class MovingAverageForecast
    implements ForecastStrategy
{
    public function __construct(
        private ForecastRepository $repository
    ) {
    }

    public function forecast(
        ForecastRequest $request
    ): ForecastResult {

        $history = $this->repository
            ->dailyConsumptionHistory(
                itemId: $request->itemId,
                warehouseId: $request->warehouseId,
                days: $request->historyDays
            );

        if ($history === []) {
            return new ForecastResult(
                0,
                0,
                0,
                0
            );
        }

        $dailyAverage =
            array_sum($history) / count($history);

        return new ForecastResult(
            dailyDemand: $dailyAverage,
            monthlyDemand: $dailyAverage * 30,
            forecastQuantity:
                $dailyAverage * $request->forecastDays,
            confidence: 0.75
        );
    }
}
```

---

# Weighted Moving Average

Recent sales have higher importance.

```php
final class WeightedMovingAverageForecast
    implements ForecastStrategy
{
    public function __construct(
        private ForecastRepository $repository
    ) {
    }

    public function forecast(
        ForecastRequest $request
    ): ForecastResult {

        $history =
            $this->repository
                ->recentConsumption(
                    $request->itemId,
                    90
                );

        $weights = [];
        $weightSum = 0;

        foreach ($history as $index => $value) {

            $weight = $index + 1;

            $weights[] = $value * $weight;

            $weightSum += $weight;
        }

        $average =
            array_sum($weights) / $weightSum;

        return new ForecastResult(
            dailyDemand: $average,
            monthlyDemand: $average * 30,
            forecastQuantity:
                $average * $request->forecastDays,
            confidence: 0.80
        );
    }
}
```

---

# Exponential Smoothing

Excellent for most ERP implementations.

```php
final class ExponentialSmoothingForecast
    implements ForecastStrategy
{
    private float $alpha = 0.3;

    public function __construct(
        private ForecastRepository $repository
    ) {
    }

    public function forecast(
        ForecastRequest $request
    ): ForecastResult {

        $history =
            $this->repository
                ->dailyConsumptionHistory(
                    $request->itemId,
                    $request->warehouseId,
                    180
                );

        $forecast = $history[0] ?? 0;

        foreach ($history as $actual) {

            $forecast =
                $this->alpha * $actual
                + (1 - $this->alpha) * $forecast;
        }

        return new ForecastResult(
            dailyDemand: $forecast,
            monthlyDemand: $forecast * 30,
            forecastQuantity:
                $forecast * $request->forecastDays,
            confidence: 0.85
        );
    }
}
```

---

# Strategy Resolver

```php
final class ForecastEngine
{
    public function __construct(
        private Container $container
    ) {
    }

    public function forecast(
        ForecastRequest $request
    ): ForecastResult {

        return $this->strategy(
            $request->method
        )->forecast($request);
    }

    private function strategy(
        string $method
    ): ForecastStrategy {

        return match ($method) {

            'MOVING_AVERAGE'
                => app(MovingAverageForecast::class),

            'WEIGHTED_AVERAGE'
                => app(WeightedMovingAverageForecast::class),

            'EXPONENTIAL'
                => app(ExponentialSmoothingForecast::class),

            'SEASONAL'
                => app(SeasonalForecast::class),

            'ML'
                => app(MachineLearningForecast::class),

            default
                => throw new InvalidArgumentException(
                    'Invalid forecast method'
                ),
        };
    }
}
```

---

# Safety Stock Service

```php
final class SafetyStockService
{
    public function calculate(
        float $dailyDemand,
        float $leadTimeDays,
        float $serviceFactor = 1.65
    ): float {

        return
            $dailyDemand *
            sqrt($leadTimeDays) *
            $serviceFactor;
    }
}
```

---

# Reorder Point Service

```php
final class ReorderPointService
{
    public function calculate(
        float $dailyDemand,
        float $leadTimeDays,
        float $safetyStock
    ): float {

        return
            ($dailyDemand * $leadTimeDays)
            + $safetyStock;
    }
}
```

---

# Purchase Recommendation Service

```php
final class PurchaseRecommendationService
{
    public function recommend(
        float $projectedDemand,
        float $availableStock,
        float $reorderPoint
    ): float {

        if ($availableStock > $reorderPoint) {
            return 0;
        }

        return max(
            0,
            $projectedDemand - $availableStock
        );
    }
}
```

---

# Example ERP Output

```json
{
  "item_id": 1001,
  "warehouse_id": 2,
  "daily_demand": 42.5,
  "forecast_30_days": 1275,
  "lead_time_days": 14,
  "safety_stock": 262,
  "reorder_point": 857,
  "available_stock": 500,
  "recommended_purchase": 775
}
```

---

## Recommended ERP Evolution Path

### Phase 1 (must-have)

* FIFO valuation
* Distributed inventory locking
* Stock projection engine
* Moving Average Forecast
* Exponential Smoothing Forecast
* Safety Stock
* Reorder Point
* Purchase Recommendations

### Phase 2

* Seasonal forecasting
* Multi-warehouse balancing recommendations
* Supplier lead-time intelligence
* Forecast accuracy tracking (MAPE, RMSE)

### Phase 3 (AI/ML)

* Gradient Boosting / XGBoost models
* Promotion-aware forecasting
* Holiday and seasonality modeling
* Automated replenishment optimization
* Dynamic safety stock adjustment

---

# Section - 16

---

**Cross-Warehouse Stock Balancing AI** is one of the most valuable advanced ERP features because it reduces:

* Overstocking
* Stockouts
* Emergency purchases
* Inter-warehouse transfer costs
* Dead inventory

Instead of recommending a new purchase immediately, the system first checks whether another warehouse already has excess stock.

---

# High-Level Architecture

```text
Sales History
Inventory Balances
Reservations
Forecast Engine
Lead Times
Transfer Costs
Warehouse Rules
        │
        ▼
Cross-Warehouse Balancing Engine
        │
        ├── Shortage Detection
        ├── Excess Detection
        ├── Transfer Optimization
        ├── Purchase Recommendation
        └── Auto Transfer Proposal
```

---

# Example Scenario

## Current State

| Warehouse | Available | Forecast 30 Days |
| --------- | --------- | ---------------- |
| Colombo   | 20        | 150              |
| Kandy     | 300       | 80               |
| Galle     | 250       | 60               |

### Result

```text
Colombo shortage = 130

Kandy excess = 220
Galle excess = 190
```

AI recommendation:

```text
Transfer 130 units
From Kandy
To Colombo

Purchase NOT required
```

---

# Domain Structure

```text
Forecasting/
│
├── DTO/
│   ├── WarehouseDemandDTO.php
│   ├── WarehouseBalanceDTO.php
│   ├── TransferRecommendationDTO.php
│
├── Services/
│   ├── StockBalancingEngine.php
│   ├── DemandForecastService.php
│   ├── WarehouseScoringService.php
│   ├── TransferOptimizationService.php
│   ├── PurchaseFallbackService.php
│
├── Rules/
│   ├── ShortageRule.php
│   ├── ExcessRule.php
│   ├── TransferRule.php
```

---

# Transfer Recommendation DTO

```php
final readonly class TransferRecommendationDTO
{
    public function __construct(
        public int $sourceWarehouseId,
        public int $targetWarehouseId,
        public int $itemId,
        public float $quantity,
        public float $score,
        public float $estimatedSaving,
    ) {
    }
}
```

---

# Step 1 — Forecast Demand

Already provided by the forecasting engine.

Example:

```php
$forecast = $forecastEngine->forecast(
    new ForecastRequest(
        itemId: $itemId,
        warehouseId: $warehouseId,
        method: 'EXPONENTIAL'
    )
);
```

Result:

```php
[
    'forecast_quantity' => 150
]
```

---

# Step 2 — Calculate Excess & Shortage

## Service

```php
final class WarehouseDemandAnalyzer
{
    public function analyze(
        float $availableStock,
        float $forecastDemand
    ): array {

        return [
            'surplus' =>
                max(
                    0,
                    $availableStock - $forecastDemand
                ),

            'shortage' =>
                max(
                    0,
                    $forecastDemand - $availableStock
                ),
        ];
    }
}
```

---

# Example

```php
available = 300
forecast = 80
```

Result

```php
surplus = 220
shortage = 0
```

---

# Step 3 — Warehouse Scoring Engine

Not all warehouses are equally good transfer candidates.

Factors:

| Factor           | Weight |
| ---------------- | ------ |
| Surplus quantity | 40%    |
| Distance         | 20%    |
| Transfer cost    | 20%    |
| Lead time        | 20%    |

---

## Score Formula

```text
Score =
SurplusScore
+ DistanceScore
+ CostScore
+ LeadTimeScore
```

---

# Service

```php
final class WarehouseScoringService
{
    public function score(
        float $surplus,
        float $distance,
        float $transferCost,
        float $leadTime
    ): float {

        return
            ($surplus * 0.40)
            - ($distance * 0.20)
            - ($transferCost * 0.20)
            - ($leadTime * 0.20);
    }
}
```

---

# Step 4 — Transfer Optimization Engine

## Core Service

```php
final class TransferOptimizationService
{
    public function optimize(
        array $surplusWarehouses,
        float $requiredQty
    ): array {

        usort(
            $surplusWarehouses,
            fn ($a, $b) =>
                $b['score'] <=> $a['score']
        );

        $recommendations = [];

        foreach ($surplusWarehouses as $warehouse) {

            if ($requiredQty <= 0) {
                break;
            }

            $transferQty = min(
                $requiredQty,
                $warehouse['surplus']
            );

            $recommendations[] = [

                'warehouse_id' =>
                    $warehouse['id'],

                'quantity' =>
                    $transferQty
            ];

            $requiredQty -= $transferQty;
        }

        return $recommendations;
    }
}
```

---

# Example Result

```php
[
    [
        'warehouse_id' => 2,
        'quantity' => 130
    ]
]
```

---

# Step 5 — Purchase Fallback

If all warehouses combined cannot satisfy demand:

```php
final class PurchaseFallbackService
{
    public function quantityToPurchase(
        float $required,
        float $transferable
    ): float {

        return max(
            0,
            $required - $transferable
        );
    }
}
```

---

# Example

```text
Required = 300
Transferable = 220
```

Result

```text
Purchase Required = 80
```

---

# Complete Balancing Engine

```php
final class StockBalancingEngine
{
    public function recommend(
        int $itemId
    ): array {

        $warehouses =
            $this->repository
                ->warehouseStates($itemId);

        $recommendations = [];

        foreach ($warehouses as $target) {

            if ($target['shortage'] <= 0) {
                continue;
            }

            $sources =
                $this->findSurplusWarehouses(
                    $target
                );

            $recommendations[] =
                $this->optimizer
                    ->optimize(
                        $sources,
                        $target['shortage']
                    );
        }

        return $recommendations;
    }
}
```

---

# AI Upgrade (Future)

Once enough data exists (typically 12–24 months), replace rule-based forecasting with ML models:

Features:

```text
Historical sales
Seasonality
Holidays
Promotions
Weather
Lead time
Transfer history
Warehouse performance
```

Models:

* XGBoost
* LightGBM
* CatBoost
* Prophet
* LSTM (only for very large datasets)

---

# Auto-Transfer Workflow

```text
Forecast Engine
      │
      ▼
Detect Shortage
      │
      ▼
Search Surplus Warehouses
      │
      ▼
Optimize Transfers
      │
      ▼
Generate Transfer Proposal
      │
      ▼
Approval Workflow
      │
      ▼
Stock Transfer Order
      │
      ▼
Inventory Update
```

---

# Enterprise Enhancements

### Multi-Echelon Inventory Optimization

```text
Head Office
    ↓
Regional Warehouse
    ↓
Branch Warehouse
```

Optimizes stock across all levels simultaneously.

### Dynamic Transfer Cost Modeling

Considers:

* Fuel prices
* Courier rates
* Vehicle availability
* Route efficiency

### Service Level Optimization

Example:

```text
Critical item:
98% availability target

Normal item:
90% availability target
```

### Inventory Health Score

```text
Fast Moving
Slow Moving
Dead Stock
Excess Stock
At Risk Stockout
```

### Reinforcement Learning Layer (future)

System learns:

```text
Which transfer decisions reduced costs
Which purchase decisions avoided stockouts
Which warehouses should hold more safety stock
```

---

# Section - 17

---

An **Auto-Replenishment Engine** should not directly create Purchase Orders every time stock drops. A professional ERP uses forecasting, lead times, safety stock, reservations, incoming orders, and supplier constraints before recommending or creating purchases.

---

# 🧠 Core Objective

Instead of:

```text
Current Stock = 20
Min Stock = 50
Create PO = 30
```

Use:

```text
Projected Available = On Hand
                    - Reserved
                    - Forecast Demand
                    + Incoming PO
                    + Incoming Transfers

Recommended Order Qty =
(Target Stock Level - Projected Available)
```

---

# ERP Architecture

```text
Inventory Events
        ↓
Stock Projection Engine
        ↓
Demand Forecast Engine
        ↓
Replenishment Calculator
        ↓
Purchase Recommendation Engine
        ↓
Approval Workflow
        ↓
Purchase Order Generator
```

---

# Required Item Fields

Add to `items`:

```php
$table->decimal('minimum_stock', 20, 4)->default(0);

$table->decimal('maximum_stock', 20, 4)->nullable();

$table->decimal('reorder_point', 20, 4)->default(0);

$table->decimal('reorder_quantity', 20, 4)->nullable();

$table->decimal('safety_stock', 20, 4)->default(0);

$table->integer('lead_time_days')->default(0);

$table->integer('review_period_days')->default(30);

$table->boolean('auto_replenishment_enabled')->default(false);

$table->boolean('allow_auto_purchase_order')->default(false);
```

---

# Supplier Purchasing Data

Create dedicated table.

```php
Schema::create('item_suppliers', function (Blueprint $table) {

    $table->id();

    $table->foreignId('tenant_id');
    $table->foreignId('item_id');
    $table->foreignId('supplier_id');

    $table->boolean('is_primary')->default(false);

    $table->decimal('supplier_cost',20,4);

    $table->decimal('minimum_order_qty',20,4)->default(1);

    $table->decimal('order_multiple_qty',20,4)->default(1);

    $table->integer('lead_time_days')->default(0);

    $table->timestamps();
});
```

---

# Inventory Projection Model

Create projection table.

```php
Schema::create('inventory_projections', function (Blueprint $table) {

    $table->id();

    $table->foreignId('tenant_id');

    $table->foreignId('item_id');

    $table->foreignId('warehouse_id');

    $table->decimal('on_hand_qty',20,4)->default(0);

    $table->decimal('reserved_qty',20,4)->default(0);

    $table->decimal('incoming_qty',20,4)->default(0);

    $table->decimal('outgoing_qty',20,4)->default(0);

    $table->decimal('projected_qty',20,4)->default(0);

    $table->timestamps();
});
```

---

# Forecast Service

## Contract

```php
interface ForecastStrategy
{
    public function forecast(
        Item $item,
        Warehouse $warehouse
    ): float;
}
```

---

## Moving Average Forecast

Simple but effective.

```php
final class MovingAverageForecastStrategy
    implements ForecastStrategy
{
    public function forecast(
        Item $item,
        Warehouse $warehouse
    ): float {

        $usage = InventoryTransaction::query()

            ->where('item_id',$item->id)

            ->where('warehouse_id',$warehouse->id)

            ->where('transaction_type','SALES_ISSUE')

            ->whereDate(
                'transaction_date',
                '>=',
                now()->subDays(30)
            )

            ->sum('quantity_out');

        return $usage / 30;
    }
}
```

---

# Replenishment Result DTO

```php
final readonly class ReplenishmentResult
{
    public function __construct(
        public int $itemId,
        public int $warehouseId,

        public float $projectedAvailable,

        public float $recommendedQty,

        public bool $requiresReplenishment
    ) {}
}
```

---

# Replenishment Calculator

## Main Logic

```php
final class ReplenishmentCalculator
{
    public function calculate(
        Item $item,
        InventoryProjection $projection,
        float $forecastDemand
    ): ReplenishmentResult {

        $leadTimeDemand =
            $forecastDemand
            * $item->lead_time_days;

        $projectedAvailable =
            $projection->on_hand_qty
            + $projection->incoming_qty
            - $projection->reserved_qty
            - $leadTimeDemand;

        $targetLevel =
            $item->maximum_stock
            ?: (
                $item->reorder_point
                + $item->safety_stock
            );

        $required =
            max(
                0,
                $targetLevel - $projectedAvailable
            );

        return new ReplenishmentResult(
            itemId: $item->id,
            warehouseId: $projection->warehouse_id,
            projectedAvailable: $projectedAvailable,
            recommendedQty: $required,
            requiresReplenishment: $required > 0
        );
    }
}
```

---

# MOQ + Order Multiples

Professional purchasing systems respect supplier constraints.

Example:

```text
Required = 73

MOQ = 50
Multiple = 25

Order = 75
```

Service:

```php
final class OrderQuantityNormalizer
{
    public function normalize(
        float $required,
        float $minimumOrderQty,
        float $multiple
    ): float {

        $required = max(
            $required,
            $minimumOrderQty
        );

        return ceil(
            $required / $multiple
        ) * $multiple;
    }
}
```

---

# Purchase Recommendation Engine

```php
final class PurchaseRecommendationService
{
    public function generate(
        Item $item,
        Warehouse $warehouse
    ): ?PurchaseRecommendation {

        $projection =
            $this->projectionRepository
                ->get(
                    $item->id,
                    $warehouse->id
                );

        $forecast =
            $this->forecastStrategy
                ->forecast(
                    $item,
                    $warehouse
                );

        $result =
            $this->calculator
                ->calculate(
                    $item,
                    $projection,
                    $forecast
                );

        if (!$result->requiresReplenishment) {
            return null;
        }

        return $this->recommendationRepository
            ->create([
                'item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
                'recommended_qty' => $result->recommendedQty,
            ]);
    }
}
```

---

# Automatic Purchase Order Creation

Only after approval or policy allows it.

```php
if (
    $item->allow_auto_purchase_order
    && $result->requiresReplenishment
) {

    $purchaseOrderService->create(
        $supplier,
        $result
    );
}
```

---

# Event-Driven Updates

Events that trigger recalculation:

```text
PurchaseReceiptPosted
PurchaseReturnPosted

SalesOrderReserved
SalesOrderReleased

SalesShipmentPosted
SalesReturnPosted

StockTransferIn
StockTransferOut

InventoryAdjusted

ProductionConsumed
ProductionProduced
```

Listener:

```php
RecalculateProjectionListener
```

updates projections immediately.

---

# Recommended Additional Tables

### purchase_recommendations

```text
Stores system suggestions
```

### replenishment_policies

```text
Warehouse specific settings
```

### demand_forecasts

```text
Persist forecast history
```

### inventory_projections

```text
Current future stock snapshot
```

---

# Final Formula

```text
Projected Available

=
On Hand
+ Incoming Purchases
+ Incoming Transfers

- Reserved Sales Orders
- Planned Production Consumption
- Lead Time Demand Forecast

Recommended Quantity

=
Target Stock Level
-
Projected Available

Adjusted for:

• Safety Stock
• MOQ
• Order Multiples
• Supplier Lead Time
• Warehouse Policy
```
---

# Setep - 18

---


A **Ledger Reconciliation Engine** ensures:

✔ Inventory = Accounting = Subledger = General Ledger
✔ No drift between stock valuation and finance books
✔ Detects mismatches automatically
✔ Self-correcting audit trail
✔ Fully event-driven + traceable

---

# 🧠 1. CORE PROBLEM

Without reconciliation:

```text
Inventory Value (System A) ≠ GL Balance (System B)
```

Causes:

* missing journal entries
* wrong FIFO valuation
* partial transactions
* failed async jobs
* manual adjustments

---

# 🔥 2. TARGET ARCHITECTURE

build a **3-layer financial consistency model**

```text
Inventory Engine
      ↓
Subledger (Inventory Ledger)
      ↓
General Ledger (Accounting System)
      ↓
Reconciliation Engine (Truth Validator)
```

---

# 🧱 3. LEDGER TYPES

## 3.1 Inventory Subledger (Operational Truth)

```text
- FIFO layers
- stock movements
- valuation results
- reservations
```

---

## 3.2 General Ledger (Financial Truth)

```text
- Inventory Asset Account
- COGS
- AP / AR
- Adjustments
```

---

## 3.3 Reconciliation Ledger (Audit Truth)

```text
- mismatches
- corrections
- variance entries
- audit trails
```

---

# 📦 4. CORE TABLE DESIGN

## GL Entries

```php
Schema::create('gl_entries', function (Blueprint $table) {

    $table->id();

    $table->foreignId('tenant_id');

    $table->string('reference_type'); // inventory, sales, purchase
    $table->unsignedBigInteger('reference_id');

    $table->foreignId('account_id');

    $table->enum('type', ['DEBIT','CREDIT']);

    $table->decimal('amount', 20, 4);

    $table->dateTime('posted_at');

    $table->timestamps();
});
```

---

## Inventory Ledger (Subledger)

```php
Schema::create('inventory_ledger', function (Blueprint $table) {

    $table->id();

    $table->foreignId('tenant_id');

    $table->foreignId('item_id');
    $table->foreignId('warehouse_id');

    $table->string('transaction_type');

    $table->decimal('quantity_in',20,4)->default(0);
    $table->decimal('quantity_out',20,4)->default(0);

    $table->decimal('unit_cost',20,4)->default(0);
    $table->decimal('total_cost',20,4)->default(0);

    $table->timestamps();
});
```

---

## Reconciliation Table

```php
Schema::create('ledger_reconciliations', function (Blueprint $table) {

    $table->id();

    $table->foreignId('tenant_id');

    $table->foreignId('item_id')->nullable();
    $table->foreignId('warehouse_id')->nullable();

    $table->enum('status', [
        'PENDING',
        'MATCHED',
        'VARIANCE',
        'ADJUSTED'
    ]);

    $table->decimal('inventory_value',20,4);

    $table->decimal('gl_value',20,4);

    $table->decimal('variance',20,4);

    $table->timestamps();
});
```

---

# ⚙️ 5. CORE ENGINE DESIGN

```text
ReconciliationEngine
 ├── Snapshot Builder
 ├── Inventory Aggregator
 ├── GL Aggregator
 ├── Comparator
 ├── Variance Resolver
 ├── Auto Adjustment Generator
```

---

# 🧠 6. MAIN RECONCILIATION SERVICE

```php
final class LedgerReconciliationEngine
{
    public function __construct(
        private InventoryRepository $inventory,
        private GlRepository $gl,
        private VarianceResolver $resolver
    ) {}

    public function reconcile(int $tenantId, int $itemId, int $warehouseId)
    {
        // 1. INVENTORY VALUE
        $inventoryValue = $this->inventory->getValue(
            $tenantId,
            $itemId,
            $warehouseId
        );

        // 2. GL VALUE
        $glValue = $this->gl->getInventoryAccountBalance(
            $tenantId,
            $itemId
        );

        // 3. VARIANCE
        $variance = $inventoryValue - $glValue;

        // 4. STORE RESULT
        $record = LedgerReconciliation::create([
            'tenant_id' => $tenantId,
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
            'inventory_value' => $inventoryValue,
            'gl_value' => $glValue,
            'variance' => $variance,
            'status' => $variance == 0 ? 'MATCHED' : 'VARIANCE'
        ]);

        // 5. AUTO RESOLVE (OPTIONAL)
        if ($variance != 0) {
            $this->resolver->resolve($record);
        }

        return $record;
    }
}
```

---

# ⚖️ 7. INVENTORY VALUE CALCULATION

```php
final class InventoryRepository
{
    public function getValue($tenantId, $itemId, $warehouseId): float
    {
        return InventoryBalance::where([
            'tenant_id' => $tenantId,
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId
        ])->sum('value');
    }
}
```

---

# 💰 8. GL VALUE CALCULATION

```php
final class GlRepository
{
    public function getInventoryAccountBalance(
        $tenantId,
        $itemId
    ): float {

        return GlEntry::where('tenant_id', $tenantId)
            ->where('account_id',
                Account::inventoryAccount($itemId)
            )
            ->selectRaw("
                SUM(CASE WHEN type='DEBIT' THEN amount ELSE -amount END)
            ")
            ->value('SUM');
    }
}
```

---

# 🧠 9. VARIANCE RESOLUTION ENGINE

```php
final class VarianceResolver
{
    public function resolve(LedgerReconciliation $record)
    {
        if ($record->variance == 0) {
            return;
        }

        // AUTO ADJUST ENTRY
        GlEntry::create([
            'tenant_id' => $record->tenant_id,
            'reference_type' => 'reconciliation',
            'reference_id' => $record->id,
            'account_id' => $this->getAdjustmentAccount(),
            'type' => $record->variance > 0 ? 'CREDIT' : 'DEBIT',
            'amount' => abs($record->variance),
        ]);

        $record->status = 'ADJUSTED';
        $record->save();
    }
}
```

---

# ⚙️ 10. EVENT-DRIVEN RECONCILIATION

Triggered by:

```text
InventoryTransactionPosted
FIFOConsumptionCompleted
PurchaseReceiptPosted
SalesIssuePosted
StockAdjustmentPosted
```

Listener:

```php
ReconciliationTriggerListener
```

calls:

```php
$engine->reconcile($tenantId, $itemId, $warehouseId);
```

---

# 📊 11. SCHEDULED FULL RECONCILIATION

```text
php artisan inventory:reconcile-all
```

Runs:

* daily
* weekly audit
* month-end closing

---

# 🔥 12. FULL SYSTEM FLOW

```text
Inventory Transaction
        ↓
FIFO / AVG Engine
        ↓
Inventory Ledger Update
        ↓
GL Journal Entry
        ↓
Event Fired
        ↓
Reconciliation Engine
        ↓
Variance Detection
        ↓
Auto Adjustment (if allowed)
        ↓
Audit Trail Stored
```

---
