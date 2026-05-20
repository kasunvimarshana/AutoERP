# Complete File Structure – Interface & Implementation

# Section - 01

---

## 📁 Complete Module Structure – `app/Modules/Purchase`

```
app/Modules/Purchase/
│
├── Domain/                                      # 🧠 Pure business rules (no framework dependencies)
│   ├── Aggregates/
│   │   └── PurchaseOrderAggregate.php           # Aggregate root – enforces invariants, calculates totals
│   │
│   ├── Entities/
│   │   ├── PurchaseOrder.php                    # Entity – PO header data
│   │   └── PurchaseOrderLine.php                # Entity – PO line item
│   │
│   ├── Services/
│   │   └── PurchaseDomainService.php            # Cross‑entity business rules (e.g., supplier credit check)
│   │
│   ├── Repositories/
│   │   ├── PurchaseOrderRepositoryInterface.php # Contract – domain layer depends on this
│   │   └── GoodsReceiptRepositoryInterface.php
│   │
│   ├── ValueObjects/
│   │   └── PurchaseOrderStatus.php              # Enum/Value Object for status lifecycle
│   │
│   └── Exceptions/
│       ├── PurchaseOrderNotApprovedException.php
│       └── InsufficientStockException.php
│
├── Application/                                 # ⚙️ Use‑cases & orchestration (no database access directly)
│   ├── Actions/                                 # Single‑responsibility use‑cases
│   │   ├── CreatePurchaseOrderAction.php        # Builds aggregate from DTO
│   │   ├── ApprovePurchaseOrderAction.php
│   │   ├── ReceiveGoodsAction.php
│   │   └── PostPurchaseInvoiceAction.php
│   │
│   ├── DTOs/
│   │   ├── CreatePurchaseOrderDTO.php           # Immutable data transfer object
│   │   └── ReceiveGoodsDTO.php
│   │
│   └── Services/
│       └── PurchaseOrchestrator.php             # Coordinates cross‑module actions, wraps DB transactions
│
├── Infrastructure/                              # 🔌 Framework‑specific implementations
│   ├── Persistence/
│   │   ├── Models/
│   │   │   ├── PurchaseOrderModel.php           # Eloquent model for purchase_orders table
│   │   │   └── PurchaseOrderLineModel.php
│   │   └── Migrations/
│   │       ├── 2025_01_01_000001_create_purchase_orders_table.php
│   │       └── 2025_01_01_000002_create_purchase_order_lines_table.php
│   │
│   ├── Repositories/
│   │   ├── EloquentPurchaseOrderRepository.php  # Implements domain interface
│   │   └── EloquentGoodsReceiptRepository.php
│   │
│   └── Providers/
│       └── PurchaseServiceProvider.php          # Binds interfaces → implementations
│
└── Presentation/                                # 🌐 HTTP layer
    ├── Controllers/
    │   └── PurchaseOrderController.php          # Ultra‑thin – only validation + orchestrator call
    │
    ├── Requests/
    │   ├── CreatePurchaseOrderRequest.php        # Form request validation
    │   └── ApprovePurchaseOrderRequest.php
    │
    ├── Resources/
    │   ├── PurchaseOrderResource.php            # API response transformation
    │   └── PurchaseOrderCollection.php
    │
    └── Routes/
        └── api.php                              # Route definitions
```

---

## 🔗 How the Flow Connects (Create Purchase Order Example)

```
1. Client (HTTP POST /api/v1/purchase-orders)
        ↓
2. Controller → PurchaseOrderController@store(CreatePurchaseOrderRequest $request)
        ↓
3. Request → CreatePurchaseOrderRequest validates input
        ↓
4. DTO → CreatePurchaseOrderDTO is built from validated data
        ↓
5. Orchestrator → PurchaseOrchestrator::create($dto)
        ↓
6. Action → CreatePurchaseOrderAction::execute($dto)  [builds aggregate]
        ↓
7. Aggregate → PurchaseOrderAggregate::validate() + calculateTotals()
        ↓
8. Domain Service → PurchaseDomainService::checkSupplierCredit($supplierId)
        ↓
9. Repository Interface → PurchaseOrderRepositoryInterface::save($aggregate)
        ↓
10. Eloquent Repository → EloquentPurchaseOrderRepository::save($aggregate)
        ↓
11. Model → PurchaseOrderModel / PurchaseOrderLineModel → INSERT into DB
```

---

## 📝 Key Class Skeleton

### Controller (thin)

```php
namespace Modules\Purchase\Presentation\Controllers;

class PurchaseOrderController extends Controller
{
    public function __construct(private PurchaseOrchestrator $orchestrator) {}

    public function store(CreatePurchaseOrderRequest $request): JsonResponse
    {
        $dto = CreatePurchaseOrderDTO::fromRequest($request);
        $aggregate = $this->orchestrator->create($dto);
        return new PurchaseOrderResource($aggregate);
    }
}
```

### Orchestrator (transaction boundary)

```php
namespace Modules\Purchase\Application\Services;

class PurchaseOrchestrator
{
    public function create(CreatePurchaseOrderDTO $dto): PurchaseOrderAggregate
    {
        return DB::transaction(function () use ($dto) {
            $aggregate = app(CreatePurchaseOrderAction::class)->execute($dto);
            return app(PurchaseOrderRepositoryInterface::class)->save($aggregate);
        });
    }
}
```

### Action (use case)

```php
namespace Modules\Purchase\Application\Actions;

class CreatePurchaseOrderAction
{
    public function execute(CreatePurchaseOrderDTO $dto): PurchaseOrderAggregate
    {
        // 1. Generate PO number
        // 2. Build entities
        // 3. Create aggregate
        // 4. Call domain service for validations
        // 5. Return aggregate
    }
}
```

### Aggregate Root

```php
namespace Modules\Purchase\Domain\Aggregates;

class PurchaseOrderAggregate
{
    public function __construct(
        public PurchaseOrder $order,
        /** @var PurchaseOrderLine[] */
        public array $lines
    ) {}

    public function calculateTotals(): void { /* ... */ }
    public function validate(): void { /* ... */ }
}
```

### Repository Interface

```php
namespace Modules\Purchase\Domain\Repositories;

interface PurchaseOrderRepositoryInterface
{
    public function save(PurchaseOrderAggregate $aggregate): PurchaseOrderAggregate;
    public function findById(int $id): ?PurchaseOrderAggregate;
    public function updateStatus(int $id, string $status): void;
}
```

### Eloquent Repository

```php
namespace Modules\Purchase\Infrastructure\Repositories;

class EloquentPurchaseOrderRepository implements PurchaseOrderRepositoryInterface
{
    public function save(PurchaseOrderAggregate $aggregate): PurchaseOrderAggregate
    {
        DB::transaction(function () use ($aggregate) {
            $model = PurchaseOrderModel::create($aggregate->order->toArray());
            foreach ($aggregate->lines as $line) {
                $model->lines()->create($line->toArray());
            }
            $aggregate->order->id = $model->id;
        });
        return $aggregate;
    }
}
```

---

- **Controllers** stay tiny (1-3 lines of logic)
- **Business rules** live in the Domain layer, completely framework-agnostic
- **Database logic** is isolated in Eloquent Repositories
- **Cross-module coordination** happens in the Orchestrator
- **Every layer is testable** independently via mocking interfaces

---

# Section - 02

---


## 1. Core Architectural Decisions

| Decision | Why |
|----------|-----|
| **Separate dedicated tables per business domain** (no generic “Document” table) | Each module owns its data completely → no leaky abstractions, easy to understand, full referential integrity |
| **Domain layer is completely framework‑agnostic** | Entities, Aggregates, Repositories (interfaces), and Domain Services have zero Laravel imports → testable in isolation, portable |
| **Infrastructure layer implements interfaces** | Eloquent repositories, models, and service providers reside here → business rules never touch Eloquent directly |
| **Application layer orchestrates flows, never contains business rules** | Orchestrators, Actions, and DTOs are thin; they delegate to Domain layer |
| **Presentation layer is ultra‑thin** | Controllers only validate input, build DTOs, call an orchestrator, and return a resource → no business logic |
| **All monetary calculations use immutable Money value object** | Eliminates floating‑point errors, centralises rounding rules |
| **Account resolution by `AccountCode` enum, not hardcoded IDs** | Survives database seed changes, self‑documenting |
| **Expression evaluation uses Symfony ExpressionLanguage sandbox** | Safe arithmetic, no `eval()`, no remote code execution risk |
| **Inventory writes use pessimistic locking (`lockForUpdate`)** | Prevents overselling under concurrent load |
| **Rate limiting & authorization enforced at middleware/gate level** | Security baked in, not an afterthought |
| **Standardised `ApiResponse` value object** | Consistent JSON structure across all APIs |

---

## 2. Final Folder Structure (One Module = One Bounded Context)

```
app/Modules/Purchase/
├── Domain/
│   ├── Aggregates/
│   │   └── PurchaseOrderAggregate.php           # Invariants & total calculations
│   ├── Entities/
│   │   ├── PurchaseOrder.php
│   │   └── PurchaseOrderLine.php
│   ├── Services/
│   │   └── PurchaseDomainService.php            # Cross‑entity rules (supplier credit, etc.)
│   ├── Repositories/                             # INTERFACES only
│   │   └── PurchaseOrderRepositoryInterface.php
│   ├── ValueObjects/
│   │   ├── Money.php                            # Immutable amount + currency
│   │   └── PurchaseOrderStatus.php
│   └── Exceptions/
│       └── PurchaseOrderNotApprovableException.php
│
├── Application/
│   ├── Actions/                                  # Single‑purpose Use Cases
│   │   ├── CreatePurchaseOrderAction.php
│   │   └── ApprovePurchaseOrderAction.php
│   ├── DTOs/
│   │   └── CreatePurchaseOrderDTO.php
│   └── Services/
│       └── PurchaseOrchestrator.php              # Transaction boundary + cross‑module calls
│
├── Infrastructure/
│   ├── Models/                                   # Eloquent models
│   │   ├── PurchaseOrderModel.php
│   │   └── PurchaseOrderLineModel.php
│   ├── Repositories/
│   │   └── EloquentPurchaseOrderRepository.php   # Implements Domain interface
│   ├── Migrations/
│   │   └── 2025_01_01_000001_create_purchase_orders_table.php
│   └── Providers/
│       └── PurchaseServiceProvider.php
│
└── Presentation/
    ├── Controllers/
    │   └── PurchaseOrderController.php           # 1‑3 lines of logic
    ├── Requests/
    │   └── CreatePurchaseOrderRequest.php
    ├── Resources/
    │   └── PurchaseOrderResource.php
    └── Routes/
        └── api.php
```

---

## 3. How the Flow Works (Concrete Example)

### 3.1 Controller – Ultra‑Thin

```php
class PurchaseOrderController extends Controller
{
    public function __construct(private PurchaseOrchestrator $orchestrator) {}

    public function store(CreatePurchaseOrderRequest $request): JsonResponse
    {
        $dto = CreatePurchaseOrderDTO::fromRequest($request);
        $aggregate = $this->orchestrator->create($dto);
        return ApiResponse::created(new PurchaseOrderResource($aggregate));
    }
}
```

### 3.2 DTO – Immutable Data Transfer Object

```php
class CreatePurchaseOrderDTO
{
    public function __construct(
        public readonly int    $supplierId,
        public readonly string $orderDate,
        public readonly array  $items,            // Each item is an array: [product_id, quantity, unit_price, ...]
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(CreatePurchaseOrderRequest $request): self
    {
        return new self(
            supplierId: $request->supplier_id,
            orderDate: $request->order_date,
            items: $request->items,
            notes: $request->notes,
        );
    }
}
```

### 3.3 Orchestrator – Transaction Boundary

```php
class PurchaseOrchestrator
{
    public function create(CreatePurchaseOrderDTO $dto): PurchaseOrderAggregate
    {
        return DB::transaction(function () use ($dto) {
            $aggregate = app(CreatePurchaseOrderAction::class)->execute($dto);
            return app(PurchaseOrderRepositoryInterface::class)->save($aggregate);
        });
    }
}
```

### 3.4 Action – Builds Aggregate, Uses Domain Service

```php
class CreatePurchaseOrderAction
{
    public function execute(CreatePurchaseOrderDTO $dto): PurchaseOrderAggregate
    {
        $lines = array_map(
            fn($item) => new PurchaseOrderLine(
                productId: $item['product_id'],
                quantity: $item['quantity'],
                unitPrice: Money::fromDecimal($item['unit_price']),
                discount: Money::fromDecimal($item['discount_amount'] ?? 0),
                tax: Money::fromDecimal($item['tax_amount'] ?? 0),
            ),
            $dto->items
        );

        $order = new PurchaseOrder(
            supplierId: $dto->supplierId,
            orderDate: $dto->orderDate,
            status: PurchaseOrderStatus::DRAFT,
            notes: $dto->notes,
        );

        $aggregate = new PurchaseOrderAggregate($order, $lines);
        $aggregate->calculateTotals();
        $aggregate->validate();                              // throws if invalid

        app(PurchaseDomainService::class)->checkSupplierCredit($dto->supplierId, $aggregate->order->grandTotal);

        return $aggregate;
    }
}
```

### 3.5 Aggregate Root – Enforces Invariants

```php
class PurchaseOrderAggregate
{
    public function __construct(
        public PurchaseOrder $order,
        /** @var PurchaseOrderLine[] */ public array $lines
    ) {}

    public function calculateTotals(): void
    {
        $this->order->subtotal = Money::sum(...array_map(fn($line) => $line->grossAmount(), $this->lines));
        $this->order->discountTotal = Money::sum(...array_map(fn($line) => $line->discount, $this->lines));
        $this->order->taxTotal = Money::sum(...array_map(fn($line) => $line->tax, $this->lines));
        $this->order->grandTotal = $this->order->subtotal
            ->subtract($this->order->discountTotal)
            ->add($this->order->taxTotal);
    }

    public function validate(): void
    {
        if (count($this->lines) === 0) {
            throw new EmptyOrderException();
        }
    }
}
```

### 3.6 Domain Service – Cross‑Entity Business Rules

```php
class PurchaseDomainService
{
    public function checkSupplierCredit(int $supplierId, Money $orderTotal): void
    {
        $profile = app(SupplierProfileRepositoryInterface::class)->findByPartyId($supplierId);
        if ($profile && $profile->creditLimit && $orderTotal->greaterThan($profile->creditLimit)) {
            throw new SupplierCreditExceededException();
        }
    }
}
```

### 3.7 Repository Interface (Domain Layer)

```php
interface PurchaseOrderRepositoryInterface
{
    public function save(PurchaseOrderAggregate $aggregate): PurchaseOrderAggregate;
    public function findById(int $id): ?PurchaseOrderAggregate;
}
```

### 3.8 Eloquent Repository (Infrastructure)

```php
class EloquentPurchaseOrderRepository implements PurchaseOrderRepositoryInterface
{
    public function save(PurchaseOrderAggregate $aggregate): PurchaseOrderAggregate
    {
        DB::transaction(function () use ($aggregate) {
            $model = PurchaseOrderModel::create([
                'tenant_id'       => current_tenant_id(),
                'supplier_id'     => $aggregate->order->supplierId,
                'po_number'       => app(SequenceService::class)->nextNumber('purchase_order'),
                'order_date'      => $aggregate->order->orderDate,
                'status'          => $aggregate->order->status->value,
                'subtotal'        => $aggregate->order->subtotal->toDecimal(),
                'discount_total'  => $aggregate->order->discountTotal->toDecimal(),
                'tax_total'       => $aggregate->order->taxTotal->toDecimal(),
                'grand_total'     => $aggregate->order->grandTotal->toDecimal(),
                'notes'           => $aggregate->order->notes,
            ]);

            foreach ($aggregate->lines as $line) {
                $model->lines()->create([
                    'product_id'      => $line->productId,
                    'quantity'        => $line->quantity,
                    'unit_price'      => $line->unitPrice->toDecimal(),
                    'discount_amount' => $line->discount->toDecimal(),
                    'tax_amount'      => $line->tax->toDecimal(),
                    'line_total'      => $line->total()->toDecimal(),
                ]);
            }

            $aggregate->order->id = $model->id;
        });

        return $aggregate;
    }
}
```

---

## 4. Key Optimizations & Their Impact

### 4.1 `Money` Value Object

```php
final readonly class Money
{
    private function __construct(public int $cents) {}

    public static function fromDecimal(float $amount): self
    {
        return new self((int) round($amount * 100));
    }

    public function toDecimal(): float
    {
        return $this->cents / 100;
    }

    public function add(Money $other): self
    {
        return new self($this->cents + $other->cents);
    }
    // subtract, multiply, greaterThan, etc.
}
```

**Why:** Eliminates floating‑point errors in all monetary calculations. Centralises rounding logic.

### 4.2 `AccountCode` Enum + Resolver

```php
enum AccountCode: string
{
    case INVENTORY_ASSET = '1300';
    case ACCOUNTS_PAYABLE = '2000';
    case SALES_REVENUE = '3000';
    // …
}

class AccountResolver
{
    public function resolve(int $tenantId, AccountCode $code): int
    {
        // cache the ID, invalidate on COA change
    }
}
```

**Why:** No hardcoded database IDs survive seed changes. Self‑documenting code.

### 4.3 Symfony ExpressionLanguage for Calculations

**Why:** Replaces dangerous `eval()`. Only safe arithmetic and registered functions are allowed. Definition‑driven calculations remain configurable without code changes.

### 4.4 Pessimistic Locking on Inventory

```php
$stock = StockLevelModel::where(...)->lockForUpdate()->first();
```

**Why:** Guarantees correct stock under concurrent sales. Alternative (optimistic locking) would require retry loops.

### 4.5 Standardised `ApiResponse`

```php
ApiResponse::created($data, 'Purchase order created');
// returns { "success": true, "message": "Purchase order created", "data": {...} }
```

**Why:** Frontend never parses inconsistent response shapes.

### 4.6 Rate Limiting & Gates

```php
Route::middleware(['auth:api', 'throttle:60,1'])->group(function () { … });
```

```php
Gate::authorize('create', PurchaseOrder::class);
```

**Why:** Prevents abuse and enforces RBAC at the perimeter.

---

## 5. How Modules Stay Truly Independent

- **Every module’s Domain layer defines interfaces** (`PurchaseOrderRepositoryInterface`) that the Infrastructure layer implements.
- **No module ever imports a Model from another module.** Instead, a shared `Core` module provides interfaces (e.g., `SupplierProfileRepositoryInterface`) that all modules can depend on.
- **Cross‑module workflows** (e.g., placing an order that reserves stock and posts a journal entry) are handled by **Orchestrators** in the `App\Orchestration` folder. These orchestrators consume Actions from each involved module, but never their Models.

---

## 6. Final Architecture Flow (Single Request Lifecycle)

```
Client → Controller → Request → DTO → Orchestrator → Action → Aggregate → Domain Service
                                                                         ↓
                                                                   Repository (interface)
                                                                         ↓
                                                                  Eloquent Repository
                                                                         ↓
                                                                  Eloquent Model → Database
```

Every layer has exactly one responsibility:

| Layer | Responsibility |
|-------|---------------|
| **Client** | Sends HTTP request |
| **Controller** | Validates input, builds DTO, calls orchestrator, returns resource |
| **Request** | Validation rules |
| **DTO** | Carries data across boundaries, no logic |
| **Orchestrator** | Transaction boundary, sequence of cross‑module actions |
| **Action** | Single use case, builds aggregate, calls domain service |
| **Aggregate** | Business invariants, total calculations |
| **Domain Service** | Cross‑entity rules that don’t belong to a single entity |
| **Repository Interface** | Contract for persistence |
| **Eloquent Repository** | Persists aggregate to database |
| **Model** | Eloquent ORM mapping |
| **Database** | PostgreSQL / MySQL / SQLite |

---

# Section - 03

---


```
app/
├── Modules/
│   ├── Core/                              # Shared kernel
│   │   ├── Domain/
│   │   │   ├── Contracts/
│   │   │   │   ├── SequenceServiceInterface.php
│   │   │   │   ├── AccountResolverInterface.php
│   │   │   │   └── TenantConfigurationInterface.php
│   │   │   ├── ValueObjects/
│   │   │   │   ├── Money.php
│   │   │   │   └── AccountCode.php
│   │   │   └── Exceptions/
│   │   │       └── ...
│   │   ├── Application/
│   │   │   └── Services/
│   │   │       ├── SequenceService.php          # implements SequenceServiceInterface
│   │   │       └── TenantConfigurationService.php
│   │   ├── Infrastructure/
│   │   │   └── Providers/
│   │   │       └── CoreServiceProvider.php
│   │   └── Presentation/
│   │       └── ApiResponse.php
│   │
│   ├── Party/                              # Customers, suppliers, dealers
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   └── Party.php
│   │   │   ├── Repositories/
│   │   │   │   ├── PartyRepositoryInterface.php
│   │   │   │   ├── CustomerProfileRepositoryInterface.php
│   │   │   │   └── SupplierProfileRepositoryInterface.php
│   │   │   └── Services/
│   │   │       └── PartyDomainService.php
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   └── CreateCustomerAction.php
│   │   │   └── Services/
│   │   │       └── PartyOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── PartyModel.php
│   │   │   │   ├── CustomerProfileModel.php
│   │   │   │   └── SupplierProfileModel.php
│   │   │   ├── Repositories/
│   │   │   │   ├── EloquentPartyRepository.php
│   │   │   │   └── ...
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   └── Presentation/
│   │       ├── Controllers/
│   │       ├── Requests/
│   │       ├── Resources/
│   │       └── Routes/
│   │
│   ├── Product/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   ├── Repositories/
│   │   │   │   └── ProductRepositoryInterface.php
│   │   │   └── ...
│   │   ├── Application/
│   │   ├── Infrastructure/
│   │   └── Presentation/
│   │
│   ├── Inventory/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   ├── Repositories/
│   │   │   │   ├── StockLevelRepositoryInterface.php
│   │   │   │   └── StockMovementRepositoryInterface.php
│   │   │   └── Services/
│   │   │       └── InventoryDomainService.php
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── ReceiveStockAction.php
│   │   │   │   └── DispatchStockAction.php
│   │   │   └── Services/
│   │   │       └── InventoryOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   ├── Repositories/
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   └── Presentation/
│   │
│   ├── Finance/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   ├── Repositories/
│   │   │   │   ├── JournalEntryRepositoryInterface.php
│   │   │   │   └── TaxRateRepositoryInterface.php
│   │   │   └── ...
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   └── PostJournalEntryAction.php
│   │   │   └── Services/
│   │   │       └── FinanceOrchestrator.php
│   │   ├── Infrastructure/
│   │   └── Presentation/
│   │
│   ├── Purchase/
│   │   ├── Domain/
│   │   │   ├── Aggregates/
│   │   │   │   └── PurchaseOrderAggregate.php
│   │   │   ├── Entities/
│   │   │   │   ├── PurchaseOrder.php
│   │   │   │   └── PurchaseOrderLine.php
│   │   │   ├── Repositories/
│   │   │   │   ├── PurchaseOrderRepositoryInterface.php
│   │   │   │   └── GoodsReceiptRepositoryInterface.php
│   │   │   └── Services/
│   │   │       └── PurchaseDomainServiceInterface.php   # + impl
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── CreatePurchaseOrderAction.php
│   │   │   │   └── ApprovePurchaseOrderAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── CreatePurchaseOrderDTO.php
│   │   │   └── Services/
│   │   │       └── PurchaseOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── PurchaseOrderModel.php
│   │   │   │   └── PurchaseOrderLineModel.php
│   │   │   ├── Repositories/
│   │   │   │   └── EloquentPurchaseOrderRepository.php
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── PurchaseServiceProvider.php
│   │   └── Presentation/
│   │       ├── Controllers/
│   │       │   └── PurchaseOrderController.php
│   │       ├── Requests/
│   │       │   └── CreatePurchaseOrderRequest.php
│   │       ├── Resources/
│   │       │   └── PurchaseOrderResource.php
│   │       └── Routes/
│   │           └── api.php
│   │
│   ├── Sales/                    # mirrors Purchase structure
│   ├── CarRent/                  # mirrors Purchase structure
│   ├── CarService/               # mirrors Purchase structure
│   ├── Payments/
│   ├── HR/
│   └── Reports/
│
├── Orchestration/                # Cross‑module workflows
│   ├── PlaceOrderService.php
│   ├── ReceiveGoodsService.php
│   └── InvoiceAndPaymentService.php
│
├── Shared/                       # Truly shared value objects & helpers
│   └── Money.php (if not in Core)
│
└── Support/                      # Laravel helpers, macros, constants
```

---

# Section - 04

---

```
app/
├── Modules/
│   ├── Core/
│   │   ├── Domain/
│   │   │   ├── Contracts/
│   │   │   │   ├── SequenceServiceInterface.php
│   │   │   │   ├── AccountResolverInterface.php
│   │   │   │   └── TenantConfigurationInterface.php
│   │   │   ├── ValueObjects/
│   │   │   │   ├── Money.php
│   │   │   │   └── AccountCode.php
│   │   │   └── Exceptions/
│   │   │       └── ...
│   │   ├── Application/
│   │   │   └── Services/
│   │   │       ├── SequenceService.php              # implements SequenceServiceInterface
│   │   │       ├── TenantConfigurationService.php
│   │   │       └── AccountResolverService.php       # implements AccountResolverInterface
│   │   ├── Infrastructure/
│   │   │   └── Providers/
│   │   │       └── CoreServiceProvider.php
│   │   └── Presentation/
│   │       └── ApiResponse.php
│   │
│   ├── Party/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   ├── Party.php
│   │   │   │   ├── CustomerProfile.php
│   │   │   │   └── SupplierProfile.php
│   │   │   ├── Repositories/
│   │   │   │   ├── PartyRepositoryInterface.php
│   │   │   │   ├── CustomerProfileRepositoryInterface.php
│   │   │   │   └── SupplierProfileRepositoryInterface.php
│   │   │   └── Services/
│   │   │       └── PartyDomainServiceInterface.php   # + impl
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   └── CreateCustomerAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── CreatePartyDTO.php
│   │   │   └── Services/
│   │   │       └── PartyOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── PartyModel.php
│   │   │   │   ├── CustomerProfileModel.php
│   │   │   │   └── SupplierProfileModel.php
│   │   │   ├── Repositories/
│   │   │   │   ├── EloquentPartyRepository.php
│   │   │   │   ├── EloquentCustomerProfileRepository.php
│   │   │   │   └── EloquentSupplierProfileRepository.php
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   └── Presentation/ ...
│   │
│   ├── Product/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   ├── Product.php
│   │   │   │   └── ProductVariant.php
│   │   │   ├── Repositories/
│   │   │   │   └── ProductRepositoryInterface.php
│   │   │   └── Services/
│   │   │       └── ProductDomainServiceInterface.php
│   │   ├── Application/ ...
│   │   ├── Infrastructure/ ...
│   │   └── Presentation/ ...
│   │
│   ├── Inventory/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   ├── StockLevel.php
│   │   │   │   └── StockMovement.php
│   │   │   ├── Repositories/
│   │   │   │   ├── StockLevelRepositoryInterface.php
│   │   │   │   └── StockMovementRepositoryInterface.php
│   │   │   └── Services/
│   │   │       └── InventoryDomainServiceInterface.php
│   │   ├── Application/ ...
│   │   ├── Infrastructure/ ...
│   │   └── Presentation/ ...
│   │
│   ├── Finance/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   ├── JournalEntry.php
│   │   │   │   └── JournalEntryLine.php
│   │   │   ├── Repositories/
│   │   │   │   ├── JournalEntryRepositoryInterface.php
│   │   │   │   └── TaxRateRepositoryInterface.php
│   │   │   └── Services/
│   │   │       └── FinanceDomainServiceInterface.php
│   │   ├── Application/ ...
│   │   ├── Infrastructure/ ...
│   │   └── Presentation/ ...
│   │
│   ├── Purchase/
│   │   ├── Domain/
│   │   │   ├── Aggregates/
│   │   │   │   └── PurchaseOrderAggregate.php
│   │   │   ├── Entities/
│   │   │   │   ├── PurchaseOrder.php
│   │   │   │   └── PurchaseOrderLine.php
│   │   │   ├── Repositories/
│   │   │   │   ├── PurchaseOrderRepositoryInterface.php
│   │   │   │   └── GoodsReceiptRepositoryInterface.php
│   │   │   └── Services/
│   │   │       └── PurchaseDomainServiceInterface.php   # + impl
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── CreatePurchaseOrderAction.php
│   │   │   │   └── ApprovePurchaseOrderAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── CreatePurchaseOrderDTO.php
│   │   │   └── Services/
│   │   │       └── PurchaseOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── PurchaseOrderModel.php
│   │   │   │   └── PurchaseOrderLineModel.php
│   │   │   ├── Repositories/
│   │   │   │   ├── EloquentPurchaseOrderRepository.php
│   │   │   │   └── EloquentGoodsReceiptRepository.php
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── PurchaseServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── Sales/               # mirrors Purchase – interfaces:
│   │   ├── Domain/Repositories/
│   │   │   ├── SalesOrderRepositoryInterface.php
│   │   │   ├── SalesInvoiceRepositoryInterface.php
│   │   │   └── ShipmentRepositoryInterface.php
│   │   └── ...
│   │
│   ├── CarRent/             # interfaces:
│   │   ├── Domain/Repositories/
│   │   │   └── RentalAgreementRepositoryInterface.php
│   │   └── ...
│   │
│   ├── CarService/          # interfaces:
│   │   ├── Domain/Repositories/
│   │   │   └── ServiceJobCardRepositoryInterface.php
│   │   └── ...
│   │
│   ├── Payments/
│   │   ├── Domain/Repositories/
│   │   │   └── PaymentRepositoryInterface.php
│   │   └── ...
│   │
│   ├── HR/                  # interfaces:
│   │   ├── Domain/Repositories/
│   │   │   └── EmployeeRepositoryInterface.php
│   │   └── ...
│   │
│   └── Reports/             # no repositories – read‑only queries
│
├── Orchestration/
│   ├── PlaceOrderService.php
│   ├── ReceiveGoodsService.php
│   └── InvoiceAndPaymentService.php
│
├── Shared/
│   └── Money.php            (if not in Core)
│
└── Support/ ...
```

---

# Section - 05

---

```
app/
├── Modules/
│   ├── Core/
│   │   ├── Domain/
│   │   │   ├── Contracts/
│   │   │   │   ├── SequenceServiceInterface.php          # interface
│   │   │   │   ├── AccountResolverInterface.php          # interface
│   │   │   │   └── TenantConfigurationInterface.php      # interface
│   │   │   ├── ValueObjects/
│   │   │   │   ├── Money.php
│   │   │   │   └── AccountCode.php                       # enum
│   │   │   └── Exceptions/
│   │   │       ├── AccountNotConfiguredException.php
│   │   │       └── InvalidMoneyOperationException.php
│   │   ├── Application/
│   │   │   └── Services/
│   │   │       ├── SequenceService.php                   # implements SequenceServiceInterface
│   │   │       ├── AccountResolverService.php            # implements AccountResolverInterface
│   │   │       └── TenantConfigurationService.php        # implements TenantConfigurationInterface
│   │   ├── Infrastructure/
│   │   │   └── Providers/
│   │   │       └── CoreServiceProvider.php
│   │   └── Presentation/
│   │       └── ApiResponse.php
│   │
│   ├── Party/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   ├── Party.php
│   │   │   │   ├── CustomerProfile.php
│   │   │   │   ├── SupplierProfile.php
│   │   │   │   └── DealerProfile.php
│   │   │   ├── Repositories/
│   │   │   │   ├── PartyRepositoryInterface.php          # interface
│   │   │   │   ├── CustomerProfileRepositoryInterface.php # interface
│   │   │   │   ├── SupplierProfileRepositoryInterface.php # interface
│   │   │   │   └── DealerProfileRepositoryInterface.php  # interface
│   │   │   └── Services/
│   │   │       └── PartyDomainServiceInterface.php       # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── CreateCustomerAction.php
│   │   │   │   ├── CreateSupplierAction.php
│   │   │   │   └── UpdatePartyAction.php
│   │   │   ├── DTOs/
│   │   │   │   ├── CreateCustomerDTO.php
│   │   │   │   └── CreateSupplierDTO.php
│   │   │   └── Services/
│   │   │       └── PartyOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── PartyModel.php
│   │   │   │   ├── CustomerProfileModel.php
│   │   │   │   ├── SupplierProfileModel.php
│   │   │   │   └── DealerProfileModel.php
│   │   │   ├── Repositories/
│   │   │   │   ├── EloquentPartyRepository.php           # implements PartyRepositoryInterface
│   │   │   │   ├── EloquentCustomerProfileRepository.php # implements CustomerProfileRepositoryInterface
│   │   │   │   ├── EloquentSupplierProfileRepository.php # implements SupplierProfileRepositoryInterface
│   │   │   │   └── EloquentDealerProfileRepository.php   # implements DealerProfileRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── PartyDomainService.php                # implements PartyDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── PartyServiceProvider.php
│   │   └── Presentation/
│   │       ├── Controllers/
│   │       │   └── PartyController.php
│   │       ├── Requests/
│   │       │   ├── CreatePartyRequest.php
│   │       │   └── UpdatePartyRequest.php
│   │       ├── Resources/
│   │       │   ├── PartyResource.php
│   │       │   └── PartyCollection.php
│   │       └── Routes/
│   │           └── api.php
│   │
│   ├── Product/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   ├── Product.php
│   │   │   │   └── ProductVariant.php
│   │   │   ├── Repositories/
│   │   │   │   └── ProductRepositoryInterface.php        # interface
│   │   │   └── Services/
│   │   │       └── ProductDomainServiceInterface.php     # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── CreateProductAction.php
│   │   │   │   └── UpdateProductAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── CreateProductDTO.php
│   │   │   └── Services/
│   │   │       └── ProductOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── ProductModel.php
│   │   │   │   └── ProductVariantModel.php
│   │   │   ├── Repositories/
│   │   │   │   └── EloquentProductRepository.php         # implements ProductRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── ProductDomainService.php              # implements ProductDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── ProductServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── Inventory/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   ├── StockLevel.php
│   │   │   │   └── StockMovement.php
│   │   │   ├── Repositories/
│   │   │   │   ├── StockLevelRepositoryInterface.php     # interface
│   │   │   │   └── StockMovementRepositoryInterface.php  # interface
│   │   │   └── Services/
│   │   │       └── InventoryDomainServiceInterface.php   # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── ReceiveStockAction.php
│   │   │   │   ├── DispatchStockAction.php
│   │   │   │   └── TransferStockAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── StockMovementDTO.php
│   │   │   └── Services/
│   │   │       └── InventoryOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── StockLevelModel.php
│   │   │   │   └── StockMovementModel.php
│   │   │   ├── Repositories/
│   │   │   │   ├── EloquentStockLevelRepository.php      # implements StockLevelRepositoryInterface
│   │   │   │   └── EloquentStockMovementRepository.php   # implements StockMovementRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── InventoryDomainService.php            # implements InventoryDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── InventoryServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── Finance/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   ├── JournalEntry.php
│   │   │   │   └── JournalEntryLine.php
│   │   │   ├── Repositories/
│   │   │   │   ├── JournalEntryRepositoryInterface.php   # interface
│   │   │   │   └── TaxRateRepositoryInterface.php        # interface
│   │   │   └── Services/
│   │   │       └── FinanceDomainServiceInterface.php     # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── PostJournalEntryAction.php
│   │   │   │   └── ReverseJournalEntryAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── JournalEntryDTO.php
│   │   │   └── Services/
│   │   │       └── FinanceOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── JournalEntryModel.php
│   │   │   │   └── JournalEntryLineModel.php
│   │   │   ├── Repositories/
│   │   │   │   ├── EloquentJournalEntryRepository.php    # implements JournalEntryRepositoryInterface
│   │   │   │   └── EloquentTaxRateRepository.php         # implements TaxRateRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── FinanceDomainService.php              # implements FinanceDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── FinanceServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── Purchase/
│   │   ├── Domain/
│   │   │   ├── Aggregates/
│   │   │   │   └── PurchaseOrderAggregate.php
│   │   │   ├── Entities/
│   │   │   │   ├── PurchaseOrder.php
│   │   │   │   └── PurchaseOrderLine.php
│   │   │   ├── Repositories/
│   │   │   │   ├── PurchaseOrderRepositoryInterface.php  # interface
│   │   │   │   └── GoodsReceiptRepositoryInterface.php   # interface
│   │   │   └── Services/
│   │   │       └── PurchaseDomainServiceInterface.php    # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── CreatePurchaseOrderAction.php
│   │   │   │   ├── ApprovePurchaseOrderAction.php
│   │   │   │   └── ReceiveGoodsAction.php
│   │   │   ├── DTOs/
│   │   │   │   ├── CreatePurchaseOrderDTO.php
│   │   │   │   └── ReceiveGoodsDTO.php
│   │   │   └── Services/
│   │   │       └── PurchaseOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── PurchaseOrderModel.php
│   │   │   │   └── PurchaseOrderLineModel.php
│   │   │   ├── Repositories/
│   │   │   │   ├── EloquentPurchaseOrderRepository.php   # implements PurchaseOrderRepositoryInterface
│   │   │   │   └── EloquentGoodsReceiptRepository.php    # implements GoodsReceiptRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── PurchaseDomainService.php             # implements PurchaseDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── PurchaseServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── Sales/          # mirrors Purchase exactly
│   │   ├── Domain/
│   │   │   ├── Aggregates/
│   │   │   │   └── SalesOrderAggregate.php
│   │   │   ├── Entities/
│   │   │   │   ├── SalesOrder.php
│   │   │   │   └── SalesOrderLine.php
│   │   │   ├── Repositories/
│   │   │   │   ├── SalesOrderRepositoryInterface.php     # interface
│   │   │   │   ├── SalesInvoiceRepositoryInterface.php   # interface
│   │   │   │   └── ShipmentRepositoryInterface.php       # interface
│   │   │   └── Services/
│   │   │       └── SalesDomainServiceInterface.php       # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── CreateSalesOrderAction.php
│   │   │   │   ├── ConfirmShipmentAction.php
│   │   │   │   └── PostSalesInvoiceAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── CreateSalesOrderDTO.php
│   │   │   └── Services/
│   │   │       └── SalesOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── SalesOrderModel.php
│   │   │   │   └── SalesOrderLineModel.php
│   │   │   ├── Repositories/
│   │   │   │   ├── EloquentSalesOrderRepository.php      # implements SalesOrderRepositoryInterface
│   │   │   │   ├── EloquentSalesInvoiceRepository.php    # implements SalesInvoiceRepositoryInterface
│   │   │   │   └── EloquentShipmentRepository.php        # implements ShipmentRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── SalesDomainService.php                # implements SalesDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── SalesServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── CarRent/
│   │   ├── Domain/
│   │   │   ├── Aggregates/
│   │   │   │   └── RentalAgreementAggregate.php
│   │   │   ├── Entities/
│   │   │   │   ├── RentalAgreement.php
│   │   │   │   └── RunningChart.php
│   │   │   ├── Repositories/
│   │   │   │   └── RentalAgreementRepositoryInterface.php # interface
│   │   │   └── Services/
│   │   │       └── RentalDomainServiceInterface.php       # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── CreateRentalAgreementAction.php
│   │   │   │   ├── ActivateAgreementAction.php
│   │   │   │   └── GenerateInvoiceAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── CreateRentalAgreementDTO.php
│   │   │   └── Services/
│   │   │       └── RentalOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── RentalAgreementModel.php
│   │   │   │   └── RunningChartModel.php
│   │   │   ├── Repositories/
│   │   │   │   └── EloquentRentalAgreementRepository.php  # implements RentalAgreementRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── RentalDomainService.php                # implements RentalDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── CarRentServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── CarService/
│   │   ├── Domain/
│   │   │   ├── Aggregates/
│   │   │   │   └── JobCardAggregate.php
│   │   │   ├── Entities/
│   │   │   │   ├── ServiceJobCard.php
│   │   │   │   ├── LabourLine.php
│   │   │   │   └── PartLine.php
│   │   │   ├── Repositories/
│   │   │   │   └── ServiceJobCardRepositoryInterface.php  # interface
│   │   │   └── Services/
│   │   │       └── ServiceDomainServiceInterface.php      # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── CreateJobCardAction.php
│   │   │   │   ├── CompleteJobCardAction.php
│   │   │   │   └── InvoiceJobCardAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── CreateJobCardDTO.php
│   │   │   └── Services/
│   │   │       └── ServiceOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── ServiceJobCardModel.php
│   │   │   │   └── LabourLineModel.php
│   │   │   ├── Repositories/
│   │   │   │   └── EloquentServiceJobCardRepository.php   # implements ServiceJobCardRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── ServiceDomainService.php               # implements ServiceDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── CarServiceServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── Payments/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   ├── Payment.php
│   │   │   │   └── PaymentAllocation.php
│   │   │   ├── Repositories/
│   │   │   │   └── PaymentRepositoryInterface.php         # interface
│   │   │   └── Services/
│   │   │       └── PaymentDomainServiceInterface.php      # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── CreatePaymentAction.php
│   │   │   │   ├── AllocatePaymentAction.php
│   │   │   │   └── RefundPaymentAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── CreatePaymentDTO.php
│   │   │   └── Services/
│   │   │       └── PaymentOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── PaymentModel.php
│   │   │   │   └── PaymentAllocationModel.php
│   │   │   ├── Repositories/
│   │   │   │   └── EloquentPaymentRepository.php          # implements PaymentRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── PaymentDomainService.php               # implements PaymentDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── PaymentServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── HR/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   └── Employee.php
│   │   │   ├── Repositories/
│   │   │   │   └── EmployeeRepositoryInterface.php        # interface
│   │   │   └── Services/
│   │   │       └── HRDomainServiceInterface.php           # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── OnboardEmployeeAction.php
│   │   │   │   └── TerminateEmployeeAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── OnboardEmployeeDTO.php
│   │   │   └── Services/
│   │   │       └── HROrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   └── EmployeeModel.php
│   │   │   ├── Repositories/
│   │   │   │   └── EloquentEmployeeRepository.php         # implements EmployeeRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── HRDomainService.php                    # implements HRDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── HRServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   └── Reports/
│       ├── Application/
│       │   └── Services/
│       │       ├── FinancialReportService.php
│       │       ├── InventoryReportService.php
│       │       └── DashboardService.php
│       └── Presentation/
│           ├── Controllers/
│           └── Routes/
│
├── Orchestration/
│   ├── PlaceOrderOrchestrator.php
│   ├── ReceiveGoodsOrchestrator.php
│   └── InvoiceAndPaymentOrchestrator.php
│
├── Shared/
│   ├── ValueObjects/
│   │   └── Money.php
│   ├── Exceptions/
│   │   └── DomainException.php
│   └── Traits/
│       └── Auditable.php
│
└── Support/
    └── Helpers.php
```

---

# Section - 06

---

```
app/
├── Modules/
│   ├── Core/
│   │   ├── Domain/
│   │   │   ├── Contracts/
│   │   │   │   ├── SequenceServiceInterface.php              # interface
│   │   │   │   ├── AccountResolverInterface.php              # interface
│   │   │   │   └── TenantConfigurationInterface.php          # interface
│   │   │   ├── ValueObjects/
│   │   │   │   ├── Money.php
│   │   │   │   └── AccountCode.php                           # enum
│   │   │   └── Exceptions/
│   │   │       ├── AccountNotConfiguredException.php
│   │   │       └── InvalidMoneyOperationException.php
│   │   ├── Application/
│   │   │   └── Services/
│   │   │       ├── SequenceService.php                       # implements SequenceServiceInterface
│   │   │       ├── AccountResolverService.php                # implements AccountResolverInterface
│   │   │       └── TenantConfigurationService.php            # implements TenantConfigurationInterface
│   │   ├── Infrastructure/
│   │   │   └── Providers/
│   │   │       └── CoreServiceProvider.php
│   │   └── Presentation/
│   │       └── ApiResponse.php
│   │
│   ├── Party/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   ├── Party.php
│   │   │   │   ├── CustomerProfile.php
│   │   │   │   ├── SupplierProfile.php
│   │   │   │   └── DealerProfile.php
│   │   │   ├── Repositories/
│   │   │   │   ├── PartyRepositoryInterface.php              # interface
│   │   │   │   ├── CustomerProfileRepositoryInterface.php    # interface
│   │   │   │   ├── SupplierProfileRepositoryInterface.php    # interface
│   │   │   │   └── DealerProfileRepositoryInterface.php      # interface
│   │   │   └── Services/
│   │   │       └── PartyDomainServiceInterface.php           # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── CreateCustomerAction.php
│   │   │   │   ├── CreateSupplierAction.php
│   │   │   │   └── UpdatePartyAction.php
│   │   │   ├── DTOs/
│   │   │   │   ├── CreateCustomerDTO.php
│   │   │   │   └── CreateSupplierDTO.php
│   │   │   └── Services/
│   │   │       └── PartyOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── PartyModel.php
│   │   │   │   ├── CustomerProfileModel.php
│   │   │   │   ├── SupplierProfileModel.php
│   │   │   │   └── DealerProfileModel.php
│   │   │   ├── Repositories/
│   │   │   │   ├── EloquentPartyRepository.php               # implements PartyRepositoryInterface
│   │   │   │   ├── EloquentCustomerProfileRepository.php     # implements CustomerProfileRepositoryInterface
│   │   │   │   ├── EloquentSupplierProfileRepository.php     # implements SupplierProfileRepositoryInterface
│   │   │   │   └── EloquentDealerProfileRepository.php       # implements DealerProfileRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── PartyDomainService.php                    # implements PartyDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── PartyServiceProvider.php
│   │   └── Presentation/
│   │       ├── Controllers/
│   │       │   └── PartyController.php
│   │       ├── Requests/
│   │       │   ├── CreatePartyRequest.php
│   │       │   └── UpdatePartyRequest.php
│   │       ├── Resources/
│   │       │   ├── PartyResource.php
│   │       │   └── PartyCollection.php
│   │       └── Routes/
│   │           └── api.php
│   │
│   ├── Product/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   ├── Product.php
│   │   │   │   └── ProductVariant.php
│   │   │   ├── Repositories/
│   │   │   │   └── ProductRepositoryInterface.php            # interface
│   │   │   └── Services/
│   │   │       └── ProductDomainServiceInterface.php         # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── CreateProductAction.php
│   │   │   │   └── UpdateProductAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── CreateProductDTO.php
│   │   │   └── Services/
│   │   │       └── ProductOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── ProductModel.php
│   │   │   │   └── ProductVariantModel.php
│   │   │   ├── Repositories/
│   │   │   │   └── EloquentProductRepository.php             # implements ProductRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── ProductDomainService.php                  # implements ProductDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── ProductServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── Inventory/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   ├── StockLevel.php
│   │   │   │   └── StockMovement.php
│   │   │   ├── Repositories/
│   │   │   │   ├── StockLevelRepositoryInterface.php         # interface
│   │   │   │   └── StockMovementRepositoryInterface.php      # interface
│   │   │   └── Services/
│   │   │       └── InventoryDomainServiceInterface.php       # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── ReceiveStockAction.php
│   │   │   │   ├── DispatchStockAction.php
│   │   │   │   └── TransferStockAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── StockMovementDTO.php
│   │   │   └── Services/
│   │   │       └── InventoryOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── StockLevelModel.php
│   │   │   │   └── StockMovementModel.php
│   │   │   ├── Repositories/
│   │   │   │   ├── EloquentStockLevelRepository.php          # implements StockLevelRepositoryInterface
│   │   │   │   └── EloquentStockMovementRepository.php       # implements StockMovementRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── InventoryDomainService.php                # implements InventoryDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── InventoryServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── Finance/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   ├── JournalEntry.php
│   │   │   │   └── JournalEntryLine.php
│   │   │   ├── Repositories/
│   │   │   │   ├── JournalEntryRepositoryInterface.php       # interface
│   │   │   │   └── TaxRateRepositoryInterface.php            # interface
│   │   │   └── Services/
│   │   │       └── FinanceDomainServiceInterface.php         # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── PostJournalEntryAction.php
│   │   │   │   └── ReverseJournalEntryAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── JournalEntryDTO.php
│   │   │   └── Services/
│   │   │       └── FinanceOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── JournalEntryModel.php
│   │   │   │   └── JournalEntryLineModel.php
│   │   │   ├── Repositories/
│   │   │   │   ├── EloquentJournalEntryRepository.php        # implements JournalEntryRepositoryInterface
│   │   │   │   └── EloquentTaxRateRepository.php             # implements TaxRateRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── FinanceDomainService.php                  # implements FinanceDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── FinanceServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── Purchase/
│   │   ├── Domain/
│   │   │   ├── Aggregates/
│   │   │   │   └── PurchaseOrderAggregate.php
│   │   │   ├── Entities/
│   │   │   │   ├── PurchaseOrder.php
│   │   │   │   └── PurchaseOrderLine.php
│   │   │   ├── Repositories/
│   │   │   │   ├── PurchaseOrderRepositoryInterface.php      # interface
│   │   │   │   └── GoodsReceiptRepositoryInterface.php       # interface
│   │   │   └── Services/
│   │   │       └── PurchaseDomainServiceInterface.php        # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── CreatePurchaseOrderAction.php
│   │   │   │   ├── ApprovePurchaseOrderAction.php
│   │   │   │   └── ReceiveGoodsAction.php
│   │   │   ├── DTOs/
│   │   │   │   ├── CreatePurchaseOrderDTO.php
│   │   │   │   └── ReceiveGoodsDTO.php
│   │   │   └── Services/
│   │   │       └── PurchaseOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── PurchaseOrderModel.php
│   │   │   │   └── PurchaseOrderLineModel.php
│   │   │   ├── Repositories/
│   │   │   │   ├── EloquentPurchaseOrderRepository.php       # implements PurchaseOrderRepositoryInterface
│   │   │   │   └── EloquentGoodsReceiptRepository.php        # implements GoodsReceiptRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── PurchaseDomainService.php                 # implements PurchaseDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── PurchaseServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── Sales/          # mirrors Purchase exactly
│   │   ├── Domain/
│   │   │   ├── Aggregates/
│   │   │   │   └── SalesOrderAggregate.php
│   │   │   ├── Entities/
│   │   │   │   ├── SalesOrder.php
│   │   │   │   └── SalesOrderLine.php
│   │   │   ├── Repositories/
│   │   │   │   ├── SalesOrderRepositoryInterface.php         # interface
│   │   │   │   ├── SalesInvoiceRepositoryInterface.php       # interface
│   │   │   │   └── ShipmentRepositoryInterface.php           # interface
│   │   │   └── Services/
│   │   │       └── SalesDomainServiceInterface.php           # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── CreateSalesOrderAction.php
│   │   │   │   ├── ConfirmShipmentAction.php
│   │   │   │   └── PostSalesInvoiceAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── CreateSalesOrderDTO.php
│   │   │   └── Services/
│   │   │       └── SalesOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── SalesOrderModel.php
│   │   │   │   └── SalesOrderLineModel.php
│   │   │   ├── Repositories/
│   │   │   │   ├── EloquentSalesOrderRepository.php          # implements SalesOrderRepositoryInterface
│   │   │   │   ├── EloquentSalesInvoiceRepository.php        # implements SalesInvoiceRepositoryInterface
│   │   │   │   └── EloquentShipmentRepository.php            # implements ShipmentRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── SalesDomainService.php                    # implements SalesDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── SalesServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── CarRent/
│   │   ├── Domain/
│   │   │   ├── Aggregates/
│   │   │   │   └── RentalAgreementAggregate.php
│   │   │   ├── Entities/
│   │   │   │   ├── RentalAgreement.php
│   │   │   │   └── RunningChart.php
│   │   │   ├── Repositories/
│   │   │   │   └── RentalAgreementRepositoryInterface.php    # interface
│   │   │   └── Services/
│   │   │       └── RentalDomainServiceInterface.php          # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── CreateRentalAgreementAction.php
│   │   │   │   ├── ActivateAgreementAction.php
│   │   │   │   └── GenerateInvoiceAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── CreateRentalAgreementDTO.php
│   │   │   └── Services/
│   │   │       └── RentalOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── RentalAgreementModel.php
│   │   │   │   └── RunningChartModel.php
│   │   │   ├── Repositories/
│   │   │   │   └── EloquentRentalAgreementRepository.php     # implements RentalAgreementRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── RentalDomainService.php                   # implements RentalDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── CarRentServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── CarService/
│   │   ├── Domain/
│   │   │   ├── Aggregates/
│   │   │   │   └── JobCardAggregate.php
│   │   │   ├── Entities/
│   │   │   │   ├── ServiceJobCard.php
│   │   │   │   ├── LabourLine.php
│   │   │   │   └── PartLine.php
│   │   │   ├── Repositories/
│   │   │   │   └── ServiceJobCardRepositoryInterface.php     # interface
│   │   │   └── Services/
│   │   │       └── ServiceDomainServiceInterface.php         # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── CreateJobCardAction.php
│   │   │   │   ├── CompleteJobCardAction.php
│   │   │   │   └── InvoiceJobCardAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── CreateJobCardDTO.php
│   │   │   └── Services/
│   │   │       └── ServiceOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── ServiceJobCardModel.php
│   │   │   │   └── LabourLineModel.php
│   │   │   ├── Repositories/
│   │   │   │   └── EloquentServiceJobCardRepository.php      # implements ServiceJobCardRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── ServiceDomainService.php                  # implements ServiceDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── CarServiceServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── Payments/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   ├── Payment.php
│   │   │   │   └── PaymentAllocation.php
│   │   │   ├── Repositories/
│   │   │   │   └── PaymentRepositoryInterface.php            # interface
│   │   │   └── Services/
│   │   │       └── PaymentDomainServiceInterface.php         # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── CreatePaymentAction.php
│   │   │   │   ├── AllocatePaymentAction.php
│   │   │   │   └── RefundPaymentAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── CreatePaymentDTO.php
│   │   │   └── Services/
│   │   │       └── PaymentOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── PaymentModel.php
│   │   │   │   └── PaymentAllocationModel.php
│   │   │   ├── Repositories/
│   │   │   │   └── EloquentPaymentRepository.php             # implements PaymentRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── PaymentDomainService.php                  # implements PaymentDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── PaymentServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── HR/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   └── Employee.php
│   │   │   ├── Repositories/
│   │   │   │   └── EmployeeRepositoryInterface.php           # interface
│   │   │   └── Services/
│   │   │       └── HRDomainServiceInterface.php              # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── OnboardEmployeeAction.php
│   │   │   │   └── TerminateEmployeeAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── OnboardEmployeeDTO.php
│   │   │   └── Services/
│   │   │       └── HROrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   └── EmployeeModel.php
│   │   │   ├── Repositories/
│   │   │   │   └── EloquentEmployeeRepository.php            # implements EmployeeRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── HRDomainService.php                       # implements HRDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── HRServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   └── Reports/
│       ├── Application/
│       │   └── Services/
│       │       ├── FinancialReportService.php
│       │       ├── InventoryReportService.php
│       │       └── DashboardService.php
│       └── Presentation/
│           ├── Controllers/
│           └── Routes/
│
├── Orchestration/
│   ├── PlaceOrderOrchestrator.php
│   ├── ReceiveGoodsOrchestrator.php
│   └── InvoiceAndPaymentOrchestrator.php
│
├── Shared/
│   ├── ValueObjects/
│   │   └── Money.php
│   ├── Exceptions/
│   │   └── DomainException.php
│   └── Traits/
│       └── Auditable.php
│
└── Support/
    └── Helpers.php
```

---

# Section - 07

---

```
Client
 ↓
Controller
 ↓
Request
 ↓
DTO
 ↓
Orchestrator Service
 ↓
Domain Actions (UseCases)
 ↓
Aggregate Root
 ↓
Domain Service
 ↓
Repository Interface
 ↓
Eloquent Repository
 ↓
Model
 ↓
Database
```

---

# Section - 08

---

```
app/
├── Modules/
│   ├── Core/
│   │   ├── Domain/
│   │   │   ├── Contracts/
│   │   │   │   ├── SequenceServiceInterface.php              # interface
│   │   │   │   ├── AccountResolverInterface.php              # interface
│   │   │   │   └── TenantConfigurationInterface.php          # interface
│   │   │   ├── ValueObjects/
│   │   │   │   ├── Money.php
│   │   │   │   └── AccountCode.php                           # enum
│   │   │   └── Exceptions/
│   │   │       ├── AccountNotConfiguredException.php
│   │   │       └── InvalidMoneyOperationException.php
│   │   ├── Application/
│   │   │   └── Services/
│   │   │       ├── SequenceService.php                       # implements SequenceServiceInterface
│   │   │       ├── AccountResolverService.php                # implements AccountResolverInterface
│   │   │       └── TenantConfigurationService.php            # implements TenantConfigurationInterface
│   │   ├── Infrastructure/
│   │   │   └── Providers/
│   │   │       └── CoreServiceProvider.php
│   │   └── Presentation/
│   │       └── ApiResponse.php
│   │
│   ├── Party/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   ├── Party.php
│   │   │   │   ├── CustomerProfile.php
│   │   │   │   ├── SupplierProfile.php
│   │   │   │   └── DealerProfile.php
│   │   │   ├── Repositories/
│   │   │   │   ├── PartyRepositoryInterface.php              # interface
│   │   │   │   ├── CustomerProfileRepositoryInterface.php    # interface
│   │   │   │   ├── SupplierProfileRepositoryInterface.php    # interface
│   │   │   │   └── DealerProfileRepositoryInterface.php      # interface
│   │   │   └── Services/
│   │   │       └── PartyDomainServiceInterface.php           # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── CreateCustomerAction.php
│   │   │   │   ├── CreateSupplierAction.php
│   │   │   │   └── UpdatePartyAction.php
│   │   │   ├── DTOs/
│   │   │   │   ├── CreateCustomerDTO.php
│   │   │   │   └── CreateSupplierDTO.php
│   │   │   └── Services/
│   │   │       └── PartyOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── PartyModel.php
│   │   │   │   ├── CustomerProfileModel.php
│   │   │   │   ├── SupplierProfileModel.php
│   │   │   │   └── DealerProfileModel.php
│   │   │   ├── Repositories/
│   │   │   │   ├── EloquentPartyRepository.php               # implements PartyRepositoryInterface
│   │   │   │   ├── EloquentCustomerProfileRepository.php     # implements CustomerProfileRepositoryInterface
│   │   │   │   ├── EloquentSupplierProfileRepository.php     # implements SupplierProfileRepositoryInterface
│   │   │   │   └── EloquentDealerProfileRepository.php       # implements DealerProfileRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── PartyDomainService.php                    # implements PartyDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── PartyServiceProvider.php
│   │   └── Presentation/
│   │       ├── Controllers/
│   │       │   └── PartyController.php
│   │       ├── Requests/
│   │       │   ├── CreatePartyRequest.php
│   │       │   └── UpdatePartyRequest.php
│   │       ├── Resources/
│   │       │   ├── PartyResource.php
│   │       │   └── PartyCollection.php
│   │       └── Routes/
│   │           └── api.php
│   │
│   ├── Product/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   ├── Product.php
│   │   │   │   └── ProductVariant.php
│   │   │   ├── Repositories/
│   │   │   │   └── ProductRepositoryInterface.php            # interface
│   │   │   └── Services/
│   │   │       └── ProductDomainServiceInterface.php         # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── CreateProductAction.php
│   │   │   │   └── UpdateProductAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── CreateProductDTO.php
│   │   │   └── Services/
│   │   │       └── ProductOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── ProductModel.php
│   │   │   │   └── ProductVariantModel.php
│   │   │   ├── Repositories/
│   │   │   │   └── EloquentProductRepository.php             # implements ProductRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── ProductDomainService.php                  # implements ProductDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── ProductServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── Inventory/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   ├── StockLevel.php
│   │   │   │   └── StockMovement.php
│   │   │   ├── Repositories/
│   │   │   │   ├── StockLevelRepositoryInterface.php         # interface
│   │   │   │   └── StockMovementRepositoryInterface.php      # interface
│   │   │   └── Services/
│   │   │       └── InventoryDomainServiceInterface.php       # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── ReceiveStockAction.php
│   │   │   │   ├── DispatchStockAction.php
│   │   │   │   └── TransferStockAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── StockMovementDTO.php
│   │   │   └── Services/
│   │   │       └── InventoryOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── StockLevelModel.php
│   │   │   │   └── StockMovementModel.php
│   │   │   ├── Repositories/
│   │   │   │   ├── EloquentStockLevelRepository.php          # implements StockLevelRepositoryInterface
│   │   │   │   └── EloquentStockMovementRepository.php       # implements StockMovementRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── InventoryDomainService.php                # implements InventoryDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── InventoryServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── Finance/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   ├── JournalEntry.php
│   │   │   │   └── JournalEntryLine.php
│   │   │   ├── Repositories/
│   │   │   │   ├── JournalEntryRepositoryInterface.php       # interface
│   │   │   │   └── TaxRateRepositoryInterface.php            # interface
│   │   │   └── Services/
│   │   │       └── FinanceDomainServiceInterface.php         # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── PostJournalEntryAction.php
│   │   │   │   └── ReverseJournalEntryAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── JournalEntryDTO.php
│   │   │   └── Services/
│   │   │       └── FinanceOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── JournalEntryModel.php
│   │   │   │   └── JournalEntryLineModel.php
│   │   │   ├── Repositories/
│   │   │   │   ├── EloquentJournalEntryRepository.php        # implements JournalEntryRepositoryInterface
│   │   │   │   └── EloquentTaxRateRepository.php             # implements TaxRateRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── FinanceDomainService.php                  # implements FinanceDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── FinanceServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── Purchase/
│   │   ├── Domain/
│   │   │   ├── Aggregates/
│   │   │   │   └── PurchaseOrderAggregate.php
│   │   │   ├── Entities/
│   │   │   │   ├── PurchaseOrder.php
│   │   │   │   └── PurchaseOrderLine.php
│   │   │   ├── Repositories/
│   │   │   │   ├── PurchaseOrderRepositoryInterface.php      # interface
│   │   │   │   └── GoodsReceiptRepositoryInterface.php       # interface
│   │   │   └── Services/
│   │   │       └── PurchaseDomainServiceInterface.php        # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── CreatePurchaseOrderAction.php
│   │   │   │   ├── ApprovePurchaseOrderAction.php
│   │   │   │   └── ReceiveGoodsAction.php
│   │   │   ├── DTOs/
│   │   │   │   ├── CreatePurchaseOrderDTO.php
│   │   │   │   └── ReceiveGoodsDTO.php
│   │   │   └── Services/
│   │   │       └── PurchaseOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── PurchaseOrderModel.php
│   │   │   │   └── PurchaseOrderLineModel.php
│   │   │   ├── Repositories/
│   │   │   │   ├── EloquentPurchaseOrderRepository.php       # implements PurchaseOrderRepositoryInterface
│   │   │   │   └── EloquentGoodsReceiptRepository.php        # implements GoodsReceiptRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── PurchaseDomainService.php                 # implements PurchaseDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── PurchaseServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── Sales/          # mirrors Purchase
│   │   ├── Domain/
│   │   │   ├── Aggregates/
│   │   │   │   └── SalesOrderAggregate.php
│   │   │   ├── Entities/
│   │   │   │   ├── SalesOrder.php
│   │   │   │   └── SalesOrderLine.php
│   │   │   ├── Repositories/
│   │   │   │   ├── SalesOrderRepositoryInterface.php         # interface
│   │   │   │   ├── SalesInvoiceRepositoryInterface.php       # interface
│   │   │   │   └── ShipmentRepositoryInterface.php           # interface
│   │   │   └── Services/
│   │   │       └── SalesDomainServiceInterface.php           # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── CreateSalesOrderAction.php
│   │   │   │   ├── ConfirmShipmentAction.php
│   │   │   │   └── PostSalesInvoiceAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── CreateSalesOrderDTO.php
│   │   │   └── Services/
│   │   │       └── SalesOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── SalesOrderModel.php
│   │   │   │   └── SalesOrderLineModel.php
│   │   │   ├── Repositories/
│   │   │   │   ├── EloquentSalesOrderRepository.php          # implements SalesOrderRepositoryInterface
│   │   │   │   ├── EloquentSalesInvoiceRepository.php        # implements SalesInvoiceRepositoryInterface
│   │   │   │   └── EloquentShipmentRepository.php            # implements ShipmentRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── SalesDomainService.php                    # implements SalesDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── SalesServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── CarRent/
│   │   ├── Domain/
│   │   │   ├── Aggregates/
│   │   │   │   └── RentalAgreementAggregate.php
│   │   │   ├── Entities/
│   │   │   │   ├── RentalAgreement.php
│   │   │   │   └── RunningChart.php
│   │   │   ├── Repositories/
│   │   │   │   └── RentalAgreementRepositoryInterface.php    # interface
│   │   │   └── Services/
│   │   │       └── RentalDomainServiceInterface.php          # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── CreateRentalAgreementAction.php
│   │   │   │   ├── ActivateAgreementAction.php
│   │   │   │   └── GenerateInvoiceAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── CreateRentalAgreementDTO.php
│   │   │   └── Services/
│   │   │       └── RentalOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── RentalAgreementModel.php
│   │   │   │   └── RunningChartModel.php
│   │   │   ├── Repositories/
│   │   │   │   └── EloquentRentalAgreementRepository.php     # implements RentalAgreementRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── RentalDomainService.php                   # implements RentalDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── CarRentServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── CarService/
│   │   ├── Domain/
│   │   │   ├── Aggregates/
│   │   │   │   └── JobCardAggregate.php
│   │   │   ├── Entities/
│   │   │   │   ├── ServiceJobCard.php
│   │   │   │   ├── LabourLine.php
│   │   │   │   └── PartLine.php
│   │   │   ├── Repositories/
│   │   │   │   └── ServiceJobCardRepositoryInterface.php     # interface
│   │   │   └── Services/
│   │   │       └── ServiceDomainServiceInterface.php         # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── CreateJobCardAction.php
│   │   │   │   ├── CompleteJobCardAction.php
│   │   │   │   └── InvoiceJobCardAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── CreateJobCardDTO.php
│   │   │   └── Services/
│   │   │       └── ServiceOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── ServiceJobCardModel.php
│   │   │   │   └── LabourLineModel.php
│   │   │   ├── Repositories/
│   │   │   │   └── EloquentServiceJobCardRepository.php      # implements ServiceJobCardRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── ServiceDomainService.php                  # implements ServiceDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── CarServiceServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── Payments/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   ├── Payment.php
│   │   │   │   └── PaymentAllocation.php
│   │   │   ├── Repositories/
│   │   │   │   └── PaymentRepositoryInterface.php            # interface
│   │   │   └── Services/
│   │   │       └── PaymentDomainServiceInterface.php         # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── CreatePaymentAction.php
│   │   │   │   ├── AllocatePaymentAction.php
│   │   │   │   └── RefundPaymentAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── CreatePaymentDTO.php
│   │   │   └── Services/
│   │   │       └── PaymentOrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   ├── PaymentModel.php
│   │   │   │   └── PaymentAllocationModel.php
│   │   │   ├── Repositories/
│   │   │   │   └── EloquentPaymentRepository.php             # implements PaymentRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── PaymentDomainService.php                  # implements PaymentDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── PaymentServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   ├── HR/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   └── Employee.php
│   │   │   ├── Repositories/
│   │   │   │   └── EmployeeRepositoryInterface.php           # interface
│   │   │   └── Services/
│   │   │       └── HRDomainServiceInterface.php              # interface
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   │   ├── OnboardEmployeeAction.php
│   │   │   │   └── TerminateEmployeeAction.php
│   │   │   ├── DTOs/
│   │   │   │   └── OnboardEmployeeDTO.php
│   │   │   └── Services/
│   │   │       └── HROrchestrator.php
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   │   └── EmployeeModel.php
│   │   │   ├── Repositories/
│   │   │   │   └── EloquentEmployeeRepository.php            # implements EmployeeRepositoryInterface
│   │   │   ├── Services/
│   │   │   │   └── HRDomainService.php                       # implements HRDomainServiceInterface
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   │       └── HRServiceProvider.php
│   │   └── Presentation/ ...
│   │
│   └── Reports/
│       ├── Application/
│       │   └── Services/
│       │       ├── FinancialReportService.php
│       │       ├── InventoryReportService.php
│       │       └── DashboardService.php
│       └── Presentation/
│           ├── Controllers/
│           └── Routes/
│
├── Orchestration/
│   ├── PlaceOrderOrchestrator.php
│   ├── ReceiveGoodsOrchestrator.php
│   └── InvoiceAndPaymentOrchestrator.php
│
├── Shared/
│   ├── ValueObjects/
│   │   └── Money.php
│   ├── Exceptions/
│   │   └── DomainException.php
│   └── Traits/
│       └── Auditable.php
│
└── Support/
    └── Helpers.php
```
