# 🚀 COMPLETE ENTERPRISE CRUD EXAMPLE

## Laravel DDD + Aggregate + Repository + Action

# Example Domain:

# 🛒 Order Management System

Includes:

✅ Controller
✅ Request
✅ DTO
✅ Action
✅ Aggregate Root
✅ Domain Service
✅ Repository Interface
✅ Eloquent Repository
✅ Eloquent Models
✅ Full CRUD
✅ Relations
✅ Transaction
✅ Enterprise structure

---

# 🔥 FINAL FLOW

```text
Client
 ↓
Controller
 ↓
Request
 ↓
DTO
 ↓
Action / UseCase
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

# 📂 FULL STRUCTURE

```bash
Domains/
└── Order/
    │
    ├── Domain/
    │   ├── Aggregates/
    │   │   └── OrderAggregate.php
    │   │
    │   ├── Entities/
    │   │   ├── Order.php
    │   │   ├── OrderItem.php
    │   │   └── Address.php
    │   │
    │   ├── Services/
    │   │   └── OrderDomainService.php
    │   │
    │   └── Repositories/
    │       └── OrderRepository.php
    │
    ├── Application/
    │   ├── DTOs/
    │   │   └── OrderData.php
    │   │
    │   └── Actions/
    │       ├── CreateOrderAction.php
    │       ├── UpdateOrderAction.php
    │       ├── DeleteOrderAction.php
    │       ├── GetOrderAction.php
    │       └── ListOrdersAction.php
    │
    ├── Infrastructure/
    │   ├── Persistence/
    │   │   ├── Models/
    │   │   │   ├── OrderModel.php
    │   │   │   ├── OrderItemModel.php
    │   │   │   └── AddressModel.php
    │   │   │
    │   │   └── Migrations/
    │   │
    │   └── Repositories/
    │       └── EloquentOrderRepository.php
    │
    └── Presentation/
        └── API/
            ├── Controllers/
            ├── Requests/
            ├── Resources/
            └── Routes/
```

---

# 🚀 DATABASE

---

# orders

```sql
id
user_id
total
status
timestamps
```

---

# order_items

```sql
id
order_id
product_name
price
qty
subtotal
```

---

# addresses

```sql
id
order_id
line1
city
country
```

---

# 🚀 DOMAIN ENTITIES

---

# Order Entity

```php
class Order
{
    public function __construct(
        public ?int $id,
        public int $userId,
        public float $total,
        public string $status,
    ) {}
}
```

---

# OrderItem Entity

```php
class OrderItem
{
    public function __construct(
        public string $productName,
        public float $price,
        public int $qty,
        public float $subtotal,
    ) {}
}
```

---

# Address Entity

```php
class Address
{
    public function __construct(
        public string $line1,
        public string $city,
        public string $country,
    ) {}
}
```

---

# 🚀 AGGREGATE ROOT

# ⭐ HEART OF DDD

```php
class OrderAggregate
{
    public function __construct(
        public Order $order,

        /** @var OrderItem[] */
        public array $items,

        public Address $address,
    ) {}

    public function calculateTotal(): float
    {
        return collect($this->items)
            ->sum(fn($item) => $item->subtotal);
    }

    public function validate(): void
    {
        if (count($this->items) === 0) {
            throw new Exception(
                'Order requires items'
            );
        }
    }
}
```

---

# 🚀 DOMAIN SERVICE

```php
class OrderDomainService
{
    public function validateStock(
        array $items
    ): void {

        foreach ($items as $item) {

            if ($item->qty <= 0) {
                throw new Exception(
                    'Invalid quantity'
                );
            }
        }
    }
}
```

---

# 🚀 DTO

```php
class OrderData
{
    public function __construct(
        public int $userId,
        public array $items,
        public array $address,
    ) {}
}
```

---

# 🚀 REPOSITORY INTERFACE

```php
interface OrderRepository
{
    public function create(
        OrderAggregate $aggregate
    ): OrderAggregate;

    public function update(
        int $id,
        OrderAggregate $aggregate
    ): OrderAggregate;

    public function delete(int $id): bool;

    public function find(int $id): ?array;

    public function all();
}
```

---

# 🚀 CREATE ACTION

```php
class CreateOrderAction
{
    public function __construct(
        private OrderRepository $repository,
        private OrderDomainService $service
    ) {}

    public function execute(
        OrderData $data
    ): OrderAggregate {

        $items = [];

        foreach ($data->items as $item) {

            $subtotal =
                $item['price'] * $item['qty'];

            $items[] = new OrderItem(
                productName: $item['product_name'],
                price: $item['price'],
                qty: $item['qty'],
                subtotal: $subtotal
            );
        }

        $address = new Address(
            line1: $data->address['line1'],
            city: $data->address['city'],
            country: $data->address['country']
        );

        $order = new Order(
            id: null,
            userId: $data->userId,
            total: 0,
            status: 'pending'
        );

        $aggregate = new OrderAggregate(
            order: $order,
            items: $items,
            address: $address
        );

        $aggregate->validate();

        $this->service
            ->validateStock($items);

        $aggregate->order->total =
            $aggregate->calculateTotal();

        return $this->repository
            ->create($aggregate);
    }
}
```

---

# 🚀 UPDATE ACTION

```php
class UpdateOrderAction
{
    public function __construct(
        private OrderRepository $repository
    ) {}

    public function execute(
        int $id,
        OrderData $data
    ) {
        // same aggregate rebuilding logic

        return $this->repository
            ->update($id, $aggregate);
    }
}
```

---

# 🚀 DELETE ACTION

```php
class DeleteOrderAction
{
    public function __construct(
        private OrderRepository $repository
    ) {}

    public function execute(int $id)
    {
        return $this->repository
            ->delete($id);
    }
}
```

---

# 🚀 ELOQUENT MODELS

---

# OrderModel

```php
class OrderModel extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'total',
        'status'
    ];

    public function items()
    {
        return $this->hasMany(
            OrderItemModel::class
        );
    }

    public function address()
    {
        return $this->hasOne(
            AddressModel::class
        );
    }
}
```

---

# OrderItemModel

```php
class OrderItemModel extends Model
{
    protected $fillable = [
        'order_id',
        'product_name',
        'price',
        'qty',
        'subtotal'
    ];
}
```

---

# AddressModel

```php
class AddressModel extends Model
{
    protected $fillable = [
        'order_id',
        'line1',
        'city',
        'country'
    ];
}
```

---

# 🚀 ELOQUENT REPOSITORY

# ⭐ DATABASE LOGIC HERE ONLY

```php
class EloquentOrderRepository
implements OrderRepository
{
    public function create(
        OrderAggregate $aggregate
    ): OrderAggregate {

        DB::transaction(function ()
        use ($aggregate) {

            $order = OrderModel::create([
                'user_id' =>
                    $aggregate->order->userId,

                'total' =>
                    $aggregate->order->total,

                'status' =>
                    $aggregate->order->status,
            ]);

            foreach ($aggregate->items as $item) {

                $order->items()->create([
                    'product_name' =>
                        $item->productName,

                    'price' => $item->price,

                    'qty' => $item->qty,

                    'subtotal' =>
                        $item->subtotal,
                ]);
            }

            $order->address()->create([
                'line1' =>
                    $aggregate->address->line1,

                'city' =>
                    $aggregate->address->city,

                'country' =>
                    $aggregate->address->country,
            ]);

            $aggregate->order->id =
                $order->id;
        });

        return $aggregate;
    }
}
```

---

# 🚀 REQUEST VALIDATION

```php
class StoreOrderRequest
extends FormRequest
{
    public function rules(): array
    {
        return [

            'items' => [
                'required',
                'array'
            ],

            'address.line1' => [
                'required'
            ],

            'address.city' => [
                'required'
            ],
        ];
    }
}
```

---

# 🚀 CONTROLLER

# ⭐ THIN CONTROLLER

```php
class OrderController extends Controller
{
    public function store(
        StoreOrderRequest $request,
        CreateOrderAction $action
    ) {
        $dto = new OrderData(
            userId: auth()->id(),

            items: $request->items,

            address: $request->address
        );

        $aggregate = $action->execute($dto);

        return response()->json([
            'id' =>
                $aggregate->order->id,

            'total' =>
                $aggregate->order->total,

            'status' =>
                $aggregate->order->status,
        ]);
    }
}
```

---

# 🚀 ROUTES

```php
Route::apiResource(
    'orders',
    OrderController::class
);
```

---

# 🚀 FULL CREATE FLOW

```text
Client Request
    ↓
Route
    ↓
Controller
    ↓
Request Validation
    ↓
DTO
    ↓
CreateOrderAction
    ↓
OrderAggregate
    ↓
OrderDomainService
    ↓
OrderRepository Interface
    ↓
EloquentOrderRepository
    ↓
Eloquent Models
    ↓
Database
```

---

# 🚀 WHY THIS IS ENTERPRISE LEVEL

---

# ✅ Controllers stay tiny

---

# ✅ Business rules centralized

Aggregate + Domain Service.

---

# ✅ DB logic isolated

inside repository only.

---

# ✅ Relations handled cleanly

Order → Items → Address.

---

# ✅ Easily testable

Mock repository.

---

# ✅ Scalable

Can evolve into:

* CQRS
* Event Sourcing
* Microservices

---

# 🚀 FINAL SENIOR RECOMMENDATION

For REAL Laravel enterprise systems:

## BEST PRACTICAL COMBO

```text
DTO
 + Action
 + Aggregate Root
 + Repository
 + Thin Controller
```

🔥 clean
🔥 scalable
🔥 maintainable
🔥 production-ready
🔥 senior-level architecture

---

# Step 2

---

# 🚀 PROBLEM (Cross Module Case)

Example:

* User places Order
* Payment module handles payment
* Inventory module checks stock
* Notification module sends email

---

# ❌ BAD WAY (DON'T DO THIS)

```php
class OrderController
{
    public function store(Request $request)
    {
        // inventory logic
        // payment logic
        // notification logic
        // order logic

        // 💀 fat controller
    }
}
```

---

# 🚀 BEST PRACTICE ARCHITECTURE

## 🔥 CORE IDEA

👉 Controller = ONLY entry point
👉 Cross-module logic = Orchestrator (Application Layer)

---

# 🚀 FINAL CLEAN SOLUTION

## ✅ Add ONE ORCHESTRATION LAYER

```text
Controller
   ↓
Application Facade / Orchestrator
   ↓
Module Actions (Order / Payment / Inventory)
   ↓
Repositories
```

---

# 📦 REAL STRUCTURE

```bash
App/
 ├── Orchestration/
 │     └── PlaceOrderService.php   👈 KEY LAYER
 │
 ├── Modules/
 │     ├── Order/
 │     ├── Payment/
 │     ├── Inventory/
 │     └── Notification/
```

---

# 🚀 CONTROLLER (ULTRA THIN)

```php
class OrderController extends Controller
{
    public function store(
        PlaceOrderRequest $request,
        PlaceOrderService $service
    ) {
        return $service->execute(
            $request->validated()
        );
    }
}
```

🔥 ONLY 1 LINE LOGIC

---

# 🚀 CROSS MODULE ORCHESTRATOR (IMPORTANT PART)

```php
class PlaceOrderService
{
    public function __construct(
        private CreateOrderAction $createOrder,
        private CheckStockAction $checkStock,
        private ProcessPaymentAction $payment,
        private SendOrderNotificationAction $notify,
    ) {}

    public function execute(array $data)
    {
        DB::transaction(function () use ($data) {

            // 1️⃣ Inventory Module
            $this->checkStock->execute($data['items']);

            // 2️⃣ Order Module
            $order = $this->createOrder->execute($data);

            // 3️⃣ Payment Module
            $this->payment->execute($order);

            // 4️⃣ Notification Module
            $this->notify->execute($order);
        });

        return response()->json([
            'message' => 'Order placed'
        ]);
    }
}
```

---

# 🚀 EACH MODULE IS INDEPENDENT

## Order Module

```php
CreateOrderAction
```

ONLY:

* order creation
* order aggregate
* order repository

---

## Payment Module

```php
ProcessPaymentAction
```

ONLY:

* payment logic
* gateway calls
* transaction handling

---

## Inventory Module

```php
CheckStockAction
```

ONLY:

* stock validation
* reservation logic

---

# 🚀 WHY THIS IS BEST

---

# ✅ 1. CONTROLLER IS CLEAN

No business logic.

---

# ✅ 2. CROSS MODULE LOGIC IS CENTRALIZED

All flow in ONE place:

```text
PlaceOrderService
```

---

# ✅ 3. MODULES STAY INDEPENDENT

Payment doesn't know Order.

Inventory doesn't know Notification.

---

# ✅ 4. EASY TO TEST

You can test:

```php
PlaceOrderService
```

as ONE unit.

---

# ✅ 5. EASY TO SCALE

Later:

👉 move Payment to microservice
👉 Order stays same
👉 Orchestrator just calls API

---

# 🚀 ALTERNATIVE (EVEN MORE ADVANCED)

Instead of Service → use EVENTS

```text
OrderCreatedEvent
   ↓
PaymentListener
InventoryListener
NotificationListener
```

🔥 This is Event-Driven Architecture

---

# ❌ BUT WARNING

Events = async complexity

So for most apps:

👉 Orchestrator service is BEST balance

---

# 🚀 FINAL SENIOR RULE

## Cross-module logic should NEVER be in:

❌ Controller
❌ Domain
❌ Repository

---

## IT SHOULD LIVE IN:

👉 Application Orchestrator Layer

---

# 🧠 SIMPLE MINDSET

Think like this:

> Controller = receptionist
> Orchestrator = manager
> Modules = departments

Receptionist doesn't run company 😎

---

# 🚀 FINAL RECOMMENDED FLOW

```text
Controller
   ↓
Orchestrator Service (Cross Module)
   ↓
Module Actions
   ↓
Repositories
   ↓
Database
```

---

# Step 3

---

# 🚀 FINAL ARCHITECTURE FLOW

```text
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

# 📦 DOMAIN: ORDER MODULE

---

# 🚀 1️⃣ REQUEST

## StoreOrderRequest

```php
class StoreOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.price' => 'required|numeric',
            'items.*.qty' => 'required|integer',
        ];
    }
}
```

---

# 🚀 2️⃣ DTO

```php
class OrderDTO
{
    public function __construct(
        public int $userId,
        public array $items
    ) {}
}
```

---

# 🚀 3️⃣ CONTROLLER (THIN)

```php
class OrderController extends Controller
{
    public function store(
        StoreOrderRequest $request,
        OrderOrchestratorService $service
    ) {
        $dto = new OrderDTO(
            userId: $request->user_id,
            items: $request->items
        );

        return $service->create($dto);
    }
}
```

---

# 🚀 4️⃣ ORCHESTRATOR SERVICE (CORE FLOW)

🔥 THIS is where cross-module coordination + transaction happens

```php
class OrderOrchestratorService
{
    public function __construct(
        private CreateOrderAction $createOrder,
        private ValidateStockAction $validateStock,
        private ProcessPaymentAction $payment,
        private OrderRepository $repository
    ) {}

    public function create(OrderDTO $dto)
    {
        return DB::transaction(function () use ($dto) {

            try {

                // 1️⃣ Stock validation (Domain Action)
                $this->validateStock->execute($dto->items);

                // 2️⃣ Create Order Aggregate
                $order = $this->createOrder->execute($dto);

                // 3️⃣ Payment
                $this->payment->execute($order);

                return $order;

            } catch (\Throwable $e) {

                // 🔥 ROLLBACK happens automatically
                throw $e;
            }
        });
    }
}
```

---

# 🚀 5️⃣ DOMAIN ACTION (USE CASE)

## CreateOrderAction

```php
class CreateOrderAction
{
    public function execute(OrderDTO $dto): OrderAggregate
    {
        $items = [];

        foreach ($dto->items as $item) {

            $subtotal = $item['price'] * $item['qty'];

            $items[] = new OrderItem(
                productId: $item['product_id'],
                price: $item['price'],
                qty: $item['qty'],
                subtotal: $subtotal
            );
        }

        $order = new Order(
            id: null,
            userId: $dto->userId,
            total: 0,
            status: 'pending'
        );

        $aggregate = new OrderAggregate(
            order: $order,
            items: $items
        );

        // business rule
        $aggregate->calculateTotal();

        return $aggregate;
    }
}
```

---

# 🚀 6️⃣ AGGREGATE ROOT

```php
class OrderAggregate
{
    public function __construct(
        public Order $order,

        /** @var OrderItem[] */
        public array $items
    ) {}

    public function calculateTotal(): float
    {
        $total = collect($this->items)
            ->sum(fn($i) => $i->subtotal);

        $this->order->total = $total;

        return $total;
    }

    public function validate(): void
    {
        if (count($this->items) === 0) {
            throw new Exception("Order cannot be empty");
        }
    }
}
```

---

# 🚀 7️⃣ DOMAIN SERVICE

```php
class OrderDomainService
{
    public function validateStock(array $items): void
    {
        foreach ($items as $item) {

            if ($item['qty'] <= 0) {
                throw new Exception("Invalid stock");
            }
        }
    }
}
```

---

# 🚀 8️⃣ REPOSITORY INTERFACE

```php
interface OrderRepository
{
    public function create(OrderAggregate $aggregate);
    public function find(int $id);
    public function update(int $id, OrderAggregate $aggregate);
    public function delete(int $id);
}
```

---

# 🚀 9️⃣ ELOQUENT REPOSITORY (DB + TRANSACTION SAFE)

```php
class EloquentOrderRepository implements OrderRepository
{
    public function create(OrderAggregate $aggregate)
    {
        $orderModel = OrderModel::create([
            'user_id' => $aggregate->order->userId,
            'total' => $aggregate->order->total,
            'status' => $aggregate->order->status,
        ]);

        foreach ($aggregate->items as $item) {

            OrderItemModel::create([
                'order_id' => $orderModel->id,
                'product_id' => $item->productId,
                'price' => $item->price,
                'qty' => $item->qty,
                'subtotal' => $item->subtotal,
            ]);
        }

        $aggregate->order->id = $orderModel->id;

        return $aggregate;
    }

    public function delete(int $id)
    {
        return DB::transaction(function () use ($id) {

            OrderItemModel::where('order_id', $id)->delete();

            return OrderModel::destroy($id);
        });
    }
}
```

---

# 🚀 🔟 MODEL

```php
class OrderModel extends Model
{
    protected $fillable = [
        'user_id',
        'total',
        'status'
    ];

    public function items()
    {
        return $this->hasMany(OrderItemModel::class);
    }
}
```

---

# 🚀 1️⃣1️⃣ UPDATE FLOW (SHORT VERSION)

```php
class UpdateOrderAction
{
    public function execute(int $id, OrderDTO $dto)
    {
        $order = OrderModel::findOrFail($id);

        $order->update([
            'status' => 'updated'
        ]);

        return $order;
    }
}
```

---

# 🚀 1️⃣2️⃣ DELETE FLOW

Handled in repository (safe rollback)

```php
public function delete(int $id)
{
    return DB::transaction(function () use ($id) {

        OrderItemModel::where('order_id', $id)->delete();

        return OrderModel::destroy($id);
    });
}
```

---

# 🚀 FULL CREATE FLOW (REAL EXECUTION)

```text
Client Request
 ↓
Controller
 ↓
Request Validation
 ↓
DTO
 ↓
Orchestrator Service
 ↓
DB Transaction START
 ↓
Stock Validation (Domain Service)
 ↓
Create Order (Action)
 ↓
Aggregate Build
 ↓
Payment Process
 ↓
Repository Save
 ↓
Eloquent Models
 ↓
Database
 ↓
COMMIT / ROLLBACK
```

---

# 🚨 WHERE ROLLBACK HAPPENS

Laravel automatically:

```php
DB::transaction(function () {
   // if ANY exception happens
   // EVERYTHING is rolled back
});
```

---

# 🔥 FINAL RESULT

This architecture gives:

✅ Clean controller
✅ Safe transactions
✅ No partial data
✅ Scalable modules
✅ Reusable actions
✅ Strong domain boundaries
✅ Enterprise-ready structure

---

# 🧠 SENIOR TAKEAWAY

👉 Controller = entry point
👉 Orchestrator = flow manager
👉 Action = business unit
👉 Aggregate = consistency guard
👉 Repository = persistence
👉 DB = final state

---

# Step 4

---

# 🚀 PROBLEM (Cross Module)

Example:

👉 Order module
👉 Payment module
👉 Inventory module
👉 Notification module

Order place:

```text
Order → Stock check → Payment → Notification
```

---

# ❌ BAD WAY (DON’T DO THIS)

## Cross module call inside domain:

```php
Order::create();

Payment::charge();

Inventory::reduce();
```

💀 Problems:

* modules tightly coupled
* impossible to scale
* hard to test
* no boundaries

---

# 🚀 BEST PRACTICE (SENIOR APPROACH)

## 👉 CROSS MODULE = ORCHESTRATION LAYER

```text
Controller
 ↓
Orchestrator (Cross Module)
 ↓
Domain Actions (per module)
 ↓
Repositories
```

---

# 📦 REAL STRUCTURE

```bash
App/
 ├── Orchestration/
 │     └── PlaceOrderService.php
 │
 ├── Modules/
 │     ├── Order/
 │     ├── Payment/
 │     ├── Inventory/
 │     └── Notification/
```

---

# 🚀 CORE IDEA

👉 Each module is independent
👉 Orchestrator connects them
👉 Modules NEVER talk to each other directly

---

# 🚀 EXAMPLE: PLACE ORDER FLOW

---

# 1️⃣ CONTROLLER (THIN)

```php
class OrderController extends Controller
{
    public function store(
        StoreOrderRequest $request,
        PlaceOrderService $service
    ) {
        return $service->execute(
            $request->validated()
        );
    }
}
```

---

# 2️⃣ ORCHESTRATOR (CROSS MODULE BRAIN)

🔥 THIS is the key layer

```php
class PlaceOrderService
{
    public function __construct(
        private CheckStockAction $stock,
        private CreateOrderAction $order,
        private ProcessPaymentAction $payment,
        private SendNotificationAction $notify
    ) {}

    public function execute(array $data)
    {
        return DB::transaction(function () use ($data) {

            // 🔥 1. INVENTORY MODULE
            $this->stock->execute($data['items']);

            // 🔥 2. ORDER MODULE
            $order = $this->order->execute($data);

            // 🔥 3. PAYMENT MODULE
            $this->payment->execute($order);

            // 🔥 4. NOTIFICATION MODULE
            $this->notify->execute($order);

            return $order;
        });
    }
}
```

---

# 🚀 3️⃣ MODULE ACTIONS (ISOLATED)

---

## Inventory Module

```php
class CheckStockAction
{
    public function execute(array $items)
    {
        foreach ($items as $item) {

            if ($item['qty'] > 10) {
                throw new Exception(
                    'Stock not enough'
                );
            }
        }
    }
}
```

---

## Order Module

```php
class CreateOrderAction
{
    public function execute(array $data)
    {
        return OrderModel::create([
            'user_id' => $data['user_id'],
            'total' => 1000,
            'status' => 'pending'
        ]);
    }
}
```

---

## Payment Module

```php
class ProcessPaymentAction
{
    public function execute($order)
    {
        if ($order->total <= 0) {
            throw new Exception("Invalid payment");
        }

        return true;
    }
}
```

---

## Notification Module

```php
class SendNotificationAction
{
    public function execute($order)
    {
        // send email / SMS
    }
}
```

---

# 🚀 WHY THIS IS BEST

## ✅ 1. MODULES ARE INDEPENDENT

```text
Order module knows NOTHING about Payment
Payment knows NOTHING about Inventory
```

---

## ✅ 2. CROSS MODULE LOGIC IS CENTRALIZED

👉 Only Orchestrator knows full flow

---

## ✅ 3. EASY TO SCALE

Later you can do:

```text
Payment → Microservice
Inventory → Microservice
```

Orchestrator stays same 😎

---

## ✅ 4. EASY TESTING

You can test:

```php
PlaceOrderService
```

without DB calls

---

# 🚀 ADVANCED OPTION (EVEN BETTER)

## 👉 EVENT-DRIVEN CROSS MODULE (PRO LEVEL)

Instead of orchestrator:

```text
OrderCreatedEvent
   ↓
InventoryListener
PaymentListener
NotificationListener
```

🔥 Loose coupling
🔥 Highly scalable

---

# ❌ BUT WARNING

Events = harder debugging + async complexity

👉 So for most apps:

✔ Orchestrator = BEST balance
✔ Events = enterprise scale systems

---

# 🚀 FINAL RULE (VERY IMPORTANT)

## ❌ NEVER DO

* Module A calling Module B directly
* Business logic inside controller
* Cross-module DB access directly

---

## ✅ ALWAYS DO

```text
Controller → Orchestrator → Module Actions
```

---

# 🧠 SIMPLE MINDSET

Think like this:

* Controller = receptionist
* Orchestrator = manager
* Modules = departments

👉 Departments never talk directly
👉 Manager coordinates everything

---

# 🚀 FINAL ARCHITECTURE (CLEAN VERSION)

```text
Client
 ↓
Controller
 ↓
Request
 ↓
DTO
 ↓
Orchestrator (Cross Module)
 ↓
Domain Actions (Modules)
 ↓
Aggregates
 ↓
Repositories
 ↓
Database
```

---

# 🔥 BOTTOM LINE

Cross-module handling =

👉 NOT inside domain
👉 NOT inside controller
👉 ONLY in orchestration layer (or events)

---

# Step 5

---

# 🚀 1️⃣ SINGLE APP (MONOLITH) — SELECT / LIST + RELATIONSHIP

```text
Controller → Request → DTO → Query Service → Repository → Model → DB
```

👉 READ side **NO Aggregate, NO Orchestrator**

---

# 📦 EXAMPLE: ORDER LIST WITH RELATIONS

## Goal:

👉 Orders + Items + Address

---

## 🚀 CONTROLLER (THIN)

```php
class OrderQueryController extends Controller
{
    public function index(
        GetOrderListQuery $query
    ) {
        return $query->execute();
    }
}
```

---

## 🚀 QUERY SERVICE (READ LAYER)

```php
class GetOrderListQuery
{
    public function __construct(
        private OrderReadRepository $repo
    ) {}

    public function execute()
    {
        return $this->repo->list();
    }
}
```

---

## 🚀 READ REPOSITORY (RELATION HANDLING HERE)

🔥 This is where relationships handled

```php
class OrderReadRepository
{
    public function list()
    {
        return OrderModel::with([
            'items',
            'address'
        ])
        ->orderBy('id', 'desc')
        ->paginate(10);
    }
}
```

---

## 🚀 RESULT

```json
{
  "data": [
    {
      "id": 1,
      "total": 500,
      "items": [
        { "product_id": 1, "qty": 2 }
      ],
      "address": {
        "city": "Colombo"
      }
    }
  ]
}
```

---

# 🧠 KEY IDEA (MONOLITH READ SIDE)

👉 Relationships = ONLY in Repository
👉 NOT in Controller
👉 NOT in Domain
👉 NOT in Aggregate

---

# 🚀 2️⃣ CROSS MODULE READ (SAME APP BUT MULTI MODULE)

Example:

👉 Order
👉 User
👉 Payment

---

## ❌ BAD WAY

```php
Order::with('user','payment')
```

👉 tight coupling across modules

---

## ✅ BETTER WAY (MODULE READ SERVICES)

```text
OrderReadService
UserReadService
PaymentReadService
```

---

## EXAMPLE

```php
class OrderListQuery
{
    public function __construct(
        private OrderReadRepository $orders,
        private UserReadRepository $users,
        private PaymentReadRepository $payments
    ) {}

    public function execute()
    {
        $orders = $this->orders->list();

        return $orders->map(function ($order) {

            $order->user =
                $this->users->find($order->user_id);

            $order->payment =
                $this->payments->findByOrder($order->id);

            return $order;
        });
    }
}
```

---

# ⚠️ NOTE

👉 This is OK for SMALL apps
👉 But heavy loops = performance risk

---

# 🚀 3️⃣ MICROSERVICE ARCHITECTURE (REAL ENTERPRISE)

Now real complexity 😎

---

## SYSTEM:

```text
Order Service
User Service
Payment Service
Inventory Service
```

---

# 🚀 HOW RELATIONSHIPS WORK HERE

👉 NO direct DB join ❌
👉 NO Eloquent relations ❌

Everything is via:

✔ HTTP API
✔ Events
✔ Cached snapshots

---

# 📦 EXAMPLE: ORDER LIST (MICROSERVICE)

---

## STEP 1 — ORDER SERVICE

```php
class OrderService
{
    public function list()
    {
        return Order::paginate(10);
    }
}
```

---

## STEP 2 — CALL USER SERVICE

```php
class UserClient
{
    public function find($id)
    {
        return Http::get(
            "http://user-service/api/users/$id"
        )->json();
    }
}
```

---

## STEP 3 — ORCHESTRATION (READ AGGREGATION LAYER)

```php
class OrderReadAggregator
{
    public function __construct(
        private OrderClient $orders,
        private UserClient $users
    ) {}

    public function list()
    {
        $orders = $this->orders->list();

        return collect($orders)->map(function ($order) {

            $order->user =
                $this->users->find($order['user_id']);

            return $order;
        });
    }
}
```

---

# 🚀 PERFORMANCE PROBLEM (IMPORTANT)

Microservices = risk:

❌ N+1 API calls
❌ slow response
❌ network latency

---

# 🚀 SOLUTION 1 — BATCH API (BEST PRACTICE)

```php
POST /users/batch
{
  "ids": [1,2,3,4]
}
```

👉 1 call instead of 100 calls

---

# 🚀 SOLUTION 2 — DENORMALIZED DATA (FAST READ)

Order service stores:

```json
{
  "user_name": "Kasun"
}
```

👉 no need to call user service

---

# 🚀 SOLUTION 3 — EVENT SYNC (MOST ENTERPRISE)

```text
UserUpdatedEvent
   ↓
Order Service updates local cache
```

👉 fast read + no API dependency

---

# 🚀 FINAL SUMMARY (VERY IMPORTANT)

## 🟢 MONOLITH READ

```text
Repository → with() relationships
```

✔ simple
✔ fast
✔ best for small-medium apps

---

## 🟡 MODULAR MONOLITH

```text
Query Service → multiple module repositories
```

✔ medium complexity
✔ flexible

---

## 🔴 MICROSERVICES

```text
API calls / Events / Cache
```

✔ scalable
❌ complex
❌ network overhead

---

# 🧠 GOLDEN RULE

## WRITE SIDE

👉 strict DDD (Aggregate, Orchestrator)

## READ SIDE

👉 simple + fast + optimized

---

# 🚀 FINAL ARCHITECTURE MINDSET

```text
WRITE = correctness
READ = performance
```

---

# 🔥 BOTTOM LINE

👉 Relationships in READ side:

* Monolith → Eloquent `with()`
* Modular → Query Services
* Microservice → API + events + cache

---

# Step 6

---

app/
│
├── Orchestration/                          # 🔥 CROSS MODULE WORKFLOWS (SAGA / FLOW LAYER)
│   ├── Orders/
│   │   ├── PlaceOrderService.php
│   │   ├── CancelOrderService.php
│   │   └── RefundOrderService.php
│   │
│   ├── Checkout/
│   │   ├── CheckoutFlowService.php
│   │   └── PaymentFlowService.php
│   │
│   └── Workflows/
│       ├── UserRegistrationFlow.php
│       ├── OrderFulfillmentFlow.php
│       └── InventoryReservationFlow.php
│
│
├── Modules/                                 # 🧩 BUSINESS BOUNDED CONTEXTS
│   │
│   ├── Core/                                # 🔐 USERS / AUTH / RBAC
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   ├── ValueObjects/
│   │   │   ├── Enums/
│   │   │   ├── Events/
│   │   │   ├── Services/
│   │   │   ├── Policies/
│   │   │   ├── Repositories/
│   │   │   └── Exceptions/
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   ├── DTOs/
│   │   │   ├── Queries/
│   │   │   ├── Commands/
│   │   │   └── Services/
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   ├── Repositories/
│   │   │   ├── Migrations/
│   │   │   └── Providers/
│   │   └── Presentation/
│   │       ├── Controllers/
│   │       ├── Requests/
│   │       ├── Resources/
│   │       └── Routes/
│   │
│   │
│   ├── Business/                            # 💼 ORDERS / PRODUCTS / BUSINESS LOGIC
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   ├── Aggregates/
│   │   │   ├── ValueObjects/
│   │   │   ├── Enums/
│   │   │   ├── Services/
│   │   │   ├── Rules/
│   │   │   ├── Repositories/
│   │   │   └── Exceptions/
│   │   ├── Application/
│   │   │   ├── Actions/
│   │   │   ├── DTOs/
│   │   │   ├── Queries/
│   │   │   ├── Commands/
│   │   │   ├── Workflows/
│   │   │   └── Validators/
│   │   ├── Infrastructure/
│   │   │   ├── Models/
│   │   │   ├── Repositories/
│   │   │   ├── ReadModels/
│   │   │   ├── Migrations/
│   │   │   └── Search/
│   │   ├── Presentation/
│   │   │   ├── Controllers/
│   │   │   ├── Requests/
│   │   │   ├── Resources/
│   │   │   └── Routes/
│   │   └── Tests/
│   │
│   │
│   ├── Commerce/                            # 🛒 CART / CHECKOUT / PAYMENTS
│   │   ├── Domain/
│   │   ├── Application/
│   │   ├── Infrastructure/
│   │   ├── Presentation/
│   │   └── Tests/
│   │
│   │
│   ├── Inventory/                           # 📦 STOCK / RESERVATION
│   │   ├── Domain/
│   │   ├── Application/
│   │   ├── Infrastructure/
│   │   ├── Presentation/
│   │   └── Tests/
│   │
│   │
│   ├── Notification/                        # 📩 EMAIL / SMS / PUSH
│   │   ├── Domain/
│   │   ├── Application/
│   │   ├── Infrastructure/
│   │   ├── Presentation/
│   │   └── Tests/
│   │
│   │
│   ├── Analytics/                           # 📊 REPORTING / METRICS
│   │   ├── Domain/
│   │   ├── Application/
│   │   ├── Infrastructure/
│   │   ├── Presentation/
│   │   └── Tests/
│   │
│   │
│   └── Audit/                               # 🕵️ LOGS / HISTORY
│       ├── Domain/
│       ├── Application/
│       ├── Infrastructure/
│       ├── Presentation/
│       └── Tests/
│
│
├── Shared/                                  # 🔁 SHARED KERNEL (STRICT RULES)
│   ├── Domain/
│   │   ├── ValueObjects/
│   │   ├── Enums/
│   │   ├── Exceptions/
│   │   ├── Contracts/
│   │   └── Traits/
│   ├── Application/
│   │   ├── DTOs/
│   │   ├── Services/
│   │   └── Bus/
│   ├── Infrastructure/
│   │   ├── Cache/
│   │   ├── Queue/
│   │   ├── Logging/
│   │   └── HttpClient/
│   └── Presentation/
│       └── Responses/
│
│
├── Support/                                # ⚙️ TECHNICAL HELPERS ONLY
│   ├── Helpers/
│   ├── Utils/
│   ├── Traits/
│   ├── Enums/
│   └── Constants/
│
│
├── Http/                                   # 🌐 BASE LARAVEL LAYER
│   ├── Controllers/
│   ├── Middleware/
│   └── Kernel.php
│
├── Console/                                # 🖥 ARTISAN COMMANDS
├── Exceptions/                             # 🚨 GLOBAL EXCEPTIONS
├── Providers/                              # 🔌 SERVICE PROVIDERS
│
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
│
├── routes/
│   ├── api.php
│   ├── web.php
│   └── console.php
│
├── storage/
└── tests/
    ├── Unit/
    ├── Feature/
    └── Integration/
