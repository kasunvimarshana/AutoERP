# Event-Driven Architecture - AutoERP

## Overview

AutoERP implements a comprehensive event-driven architecture that enables decoupled, asynchronous processing of business operations. All critical domain operations emit events that trigger various listeners for notifications, metric calculations, logging, and integrations.

## Architecture

### Core Components

1. **Events**: Domain events representing business operations
2. **Listeners**: Handlers that respond to events
3. **Queue Workers**: Process event listeners asynchronously
4. **Event Service Provider**: Registers event-listener mappings

### Event Flow

```
Business Operation → Dispatch Event → Queue Event Listeners → Execute Listeners in Background
                                                             ↓
                                    Results: Notifications, Logs, Metrics, Integrations
```

## Domain Events

All domain events extend `App\Core\Events\BaseEvent` which provides:
- Tenant context (`tenantId`)
- User context (`userId`)
- Timestamp (`occurredAt`)
- Metadata support
- Serialization for queuing

### Product Events

- **ProductCreated**: When a new product is created
- **ProductUpdated**: When product details are modified
- **ProductDeleted**: When a product is soft-deleted
- **ProductPriceChanged**: When buying or selling prices change

### Inventory Events

- **StockIn**: Stock received into inventory
- **StockOut**: Stock issued from inventory
- **StockTransfer**: Stock moved between locations
- **StockAdjustment**: Manual stock quantity adjustments
- **StockMovementRecorded**: General stock movement logging
- **LowStockDetected**: Stock below minimum threshold
- **StockExpiring**: Stock approaching expiry date

### Sales Events

- **OrderCreated**: New sales order created
- **OrderApproved**: Sales order approved
- **OrderFulfilled**: Order fulfilled and ready for shipping
- **OrderShipped**: Order shipped to customer
- **OrderCancelled**: Order cancelled

### Invoice Events

- **InvoiceGenerated**: Invoice created from order
- **InvoiceApproved**: Invoice approved for payment
- **InvoicePaid**: Invoice fully paid
- **InvoicePaymentReceived**: Partial or full payment received
- **InvoiceOverdue**: Invoice past due date

### Payment Events

- **PaymentReceived**: Payment successfully received
- **PaymentFailed**: Payment attempt failed

### Procurement Events

- **PurchaseOrderCreated**: New PO created
- **PurchaseOrderApproved**: PO approved by manager
- **GoodsReceived**: Goods received from vendor

### Manufacturing Events

- **WorkOrderStarted**: Manufacturing work order started
- **WorkOrderCompleted**: Work order completed

### Warehouse Events

- **TransferInitiated**: Transfer between warehouses initiated
- **PickingCompleted**: Picking operation completed
- **PutawayCompleted**: Putaway operation completed

### CRM Events

- **LeadCreated**: New lead added to system
- **LeadConverted**: Lead converted to customer
- **CustomerCreated**: New customer registered

## Event Listeners

### Generic Listeners

**LogActivityListener**
- Logs all events to activity log
- Stores event data for auditing
- Implements ShouldQueue for async processing
- Never fails entire event chain if logging fails

**SendNotificationListener**
- Sends notifications based on event type
- Routes to users with appropriate permissions
- Uses NotificationService for delivery
- Supports database, broadcast, and push channels

**RecalculateMetricsListener**
- Triggers metric recalculations based on events
- Clears relevant caches
- Updates dashboards and reports
- Handles inventory, sales, financial, and CRM metrics

### Specialized Listeners

**SendLowStockNotification**
- Notifies users with inventory alert permissions
- Includes product details and current quantity

**SendStockExpiryAlert**
- Alerts users about expiring stock
- Includes batch/lot and expiry date information

**GenerateInvoiceFromOrder**
- Automatically creates invoice when order is approved
- Copies order items and pricing

**UpdateInventoryOnSale**
- Reduces stock when order is fulfilled
- Updates FIFO/FEFO ledgers

**UpdateCustomerStatistics**
- Recalculates customer metrics
- Updates lifetime value, order count, etc.

## Dispatching Events

### Basic Event Dispatch

```php
use App\Events\Product\ProductCreated;

// In your service or controller
ProductCreated::dispatch(
    product: $product,
    tenantId: $tenantId,
    userId: $userId
);
```

### With Metadata

```php
use App\Events\Inventory\StockIn;

StockIn::dispatch(
    productId: $product->id,
    productName: $product->name,
    productSku: $product->sku,
    quantity: 100.0,
    locationId: $location->id,
    batchNumber: 'BATCH-001',
    serialNumber: null,
    expiryDate: now()->addMonths(6),
    reference: 'PO-12345',
    tenantId: $tenantId,
    userId: $userId,
    metadata: [
        'source' => 'purchase_order',
        'po_id' => 12345,
    ]
);
```

### Transaction Safety

Always dispatch events **inside** database transactions:

```php
use Illuminate\Support\Facades\DB;

DB::transaction(function () use ($data) {
    $order = $this->orderRepository->create($data);
    
    // Dispatch event inside transaction
    OrderCreated::dispatch(
        orderId: $order->id,
        orderNumber: $order->order_number,
        customerId: $order->customer_id,
        customerName: $order->customer->name,
        totalAmount: $order->total_amount,
        tenantId: $order->tenant_id,
        userId: auth()->id()
    );
});
```

Events are queued **after** the transaction commits, ensuring data consistency.

## Listener Implementation

### Creating a New Listener

```php
<?php

declare(strict_types=1);

namespace App\Listeners\Sales;

use App\Events\Sales\OrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendOrderConfirmation implements ShouldQueue
{
    use InteractsWithQueue;

    // Number of retry attempts
    public int $tries = 3;
    
    // Timeout in seconds
    public int $timeout = 120;

    public function handle(OrderCreated $event): void
    {
        try {
            // Your listener logic here
            Log::info('Order created', [
                'order_id' => $event->orderId,
                'tenant_id' => $event->tenantId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process order', [
                'error' => $e->getMessage(),
            ]);
            
            // Re-throw to trigger retry
            throw $e;
        }
    }

    public function failed(OrderCreated $event, \Throwable $exception): void
    {
        // Handle final failure after all retries
        Log::error('Listener permanently failed', [
            'event' => $event->getEventName(),
            'error' => $exception->getMessage(),
        ]);
    }
}
```

### Register Listener

Add to `EventServiceProvider`:

```php
protected $listen = [
    \App\Events\Sales\OrderCreated::class => [
        \App\Listeners\Sales\SendOrderConfirmation::class,
    ],
];
```

## Queue Configuration

### Required Migrations

The following migrations must be run:

```bash
php artisan migrate
```

This creates:
- `jobs` table - Stores queued jobs
- `failed_jobs` table - Stores failed jobs

### Queue Workers

Start queue workers to process events:

```bash
# Process all queues
php artisan queue:work

# Process specific queue
php artisan queue:work --queue=default,notifications

# With options
php artisan queue:work --tries=3 --timeout=120 --sleep=3
```

### Supervisor Configuration

For production, use Supervisor to keep queue workers running:

```ini
[program:autoerp-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/autoerp/backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasflimit=10
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/autoerp/backend/storage/logs/queue-worker.log
stopwaitsecs=3600
```

### Horizon (Optional but Recommended)

Laravel Horizon provides a dashboard for monitoring queues:

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan horizon
```

Access dashboard at `/horizon`

## Event Discovery

By default, automatic event discovery is **disabled** for better performance. All events must be explicitly registered in `EventServiceProvider`.

To enable automatic discovery:

```php
public function shouldDiscoverEvents(): bool
{
    return true;
}
```

## Best Practices

### 1. Keep Events Pure

Events should only contain data, no business logic:

```php
// ✓ Good
class OrderCreated extends BaseEvent
{
    public function __construct(
        public readonly int $orderId,
        public readonly string $orderNumber,
        int $tenantId,
        ?int $userId = null
    ) {
        parent::__construct($tenantId, $userId);
    }
}

// ✗ Bad
class OrderCreated extends BaseEvent
{
    public function sendEmail() { /* ... */ } // NO!
}
```

### 2. Always Use Listeners for Async Work

Never perform heavy operations in event constructors or synchronously:

```php
// ✓ Good
OrderCreated::dispatch($order);
// Listener handles email sending asynchronously

// ✗ Bad
OrderCreated::dispatch($order);
Mail::send(...); // Synchronous email
```

### 3. Ensure Tenant Context

All events must include tenant context for multi-tenancy:

```php
MyEvent::dispatch(
    // ... event data
    tenantId: $tenant->id, // Always required
    userId: auth()->id()
);
```

### 4. Handle Failures Gracefully

Implement `failed()` method in listeners:

```php
public function failed(MyEvent $event, \Throwable $exception): void
{
    // Log, alert, or handle permanent failure
    Log::error('Listener failed permanently', [
        'event' => $event->getEventName(),
        'error' => $exception->getMessage(),
    ]);
}
```

### 5. Use Idempotent Operations

Listeners may be retried, so ensure operations are idempotent:

```php
// ✓ Good - Check if already processed
public function handle(OrderCreated $event): void
{
    if ($this->isAlreadyProcessed($event->orderId)) {
        return;
    }
    
    $this->processOrder($event->orderId);
    $this->markAsProcessed($event->orderId);
}
```

## Monitoring and Debugging

### View Failed Jobs

```bash
php artisan queue:failed
```

### Retry Failed Jobs

```bash
# Retry specific job
php artisan queue:retry <job-id>

# Retry all failed jobs
php artisan queue:retry all
```

### Clear Failed Jobs

```bash
php artisan queue:flush
```

### Monitor Queue Performance

Use Laravel Telescope or Horizon for real-time monitoring.

## Testing Events

```php
use Illuminate\Support\Facades\Event;

public function test_order_created_event_is_dispatched()
{
    Event::fake([OrderCreated::class]);
    
    $order = $this->createOrder();
    
    Event::assertDispatched(OrderCreated::class, function ($event) use ($order) {
        return $event->orderId === $order->id;
    });
}
```

## Troubleshooting

### Events Not Processing

1. Check queue workers are running: `ps aux | grep queue:work`
2. Check failed jobs: `php artisan queue:failed`
3. Check logs: `storage/logs/laravel.log`

### Memory Issues

Restart queue workers regularly or use `--max-time` option:

```bash
php artisan queue:work --max-time=3600
```

### Timeout Issues

Increase timeout for long-running listeners:

```php
public int $timeout = 300; // 5 minutes
```

## Summary

AutoERP's event-driven architecture provides:
- ✅ Decoupled, maintainable code
- ✅ Asynchronous processing
- ✅ Scalable performance
- ✅ Comprehensive audit trails
- ✅ Real-time notifications
- ✅ Automatic metric updates
- ✅ Tenant-aware operations

For questions or issues, consult the development team or create a support ticket.
