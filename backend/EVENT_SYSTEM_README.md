# Event-Driven Architecture Implementation

## Overview

This implementation provides a complete event-driven architecture and queue system for AutoERP, enabling asynchronous workflows, background processing, and real-time notifications.

## What's Implemented

### 1. Events (16 Events)

#### Product Events (3)
- `ProductCreated` - Fired when a product is created
- `ProductUpdated` - Fired when a product is updated
- `ProductDeleted` - Fired when a product is deleted

#### Inventory Events (3)
- `StockMovementRecorded` - Fired when stock movement is recorded
- `LowStockDetected` - Fired when stock falls below reorder level
- `StockExpiring` - Fired when stock is approaching expiry date

#### Sales Events (3)
- `OrderCreated` - Fired when a sales order is created
- `OrderApproved` - Fired when a sales order is approved
- `OrderFulfilled` - Fired when a sales order is fulfilled

#### Invoice Events (3)
- `InvoiceGenerated` - Fired when an invoice is generated
- `InvoicePaymentReceived` - Fired when payment is received
- `InvoiceOverdue` - Fired when an invoice becomes overdue

#### CRM Events (2)
- `CustomerCreated` - Fired when a customer is created
- `LeadConverted` - Fired when a lead is converted to customer

#### Procurement Events (2)
- `PurchaseOrderApproved` - Fired when a PO is approved
- `GoodsReceived` - Fired when goods are received

### 2. Listeners (8 Listeners)

All listeners implement `ShouldQueue` for asynchronous processing:

- `SendLowStockNotification` - Notifies users of low stock
- `SendStockExpiryAlert` - Alerts users of expiring stock
- `UpdateInventoryOnSale` - Updates inventory after sale fulfillment
- `GenerateInvoiceFromOrder` - Auto-generates invoice from approved order
- `SendInvoiceToCustomer` - Sends invoice to customer contacts
- `SendPaymentConfirmation` - Sends payment confirmation
- `UpdateCustomerStatistics` - Recalculates customer statistics
- `NotifyPurchaseOrderApproval` - Notifies stakeholders of PO approval

### 3. Jobs (5 Background Jobs)

All jobs are queued and tenant-aware:

- `ProcessBulkImport` - Handles bulk CSV/file imports
- `GenerateReports` - Generates various report types
- `SendBulkNotifications` - Sends bulk notifications to users
- `RecalculateInventoryValuation` - Recalculates inventory using FIFO
- `SyncExternalData` - Syncs data with external systems

### 4. Notifications (10 Notifications)

All notifications use the database channel:

**Business Notifications:**
- `LowStockNotification`
- `StockExpiryNotification`
- `InvoiceGeneratedNotification`
- `PaymentReceivedNotification`
- `OrderStatusNotification`
- `PurchaseOrderApprovedNotification`

**System Notifications:**
- `ReportGeneratedNotification`
- `AnnouncementNotification`
- `ReminderNotification`
- `AlertNotification`

### 5. Database Migrations (4 Migrations)

- `create_notifications_table` - Stores all notifications
- `create_import_logs_table` - Tracks bulk import operations
- `create_reports_table` - Tracks report generation
- `create_sync_logs_table` - Tracks external data synchronization

### 6. Service Provider

- `EventServiceProvider` - Registers all event-listener mappings

### 7. Documentation

- `EVENT_DRIVEN_ARCHITECTURE.md` - Comprehensive documentation
- Test suite for event system

## Architecture Principles

### Tenant Awareness
✅ All events include tenant_id  
✅ All jobs operate within tenant context  
✅ All notifications respect tenant boundaries  
✅ Global scopes ensure tenant isolation

### Async Processing
✅ All listeners implement ShouldQueue  
✅ All jobs implement ShouldQueue  
✅ All notifications implement ShouldQueue  
✅ Configurable retry logic and timeouts

### Data Integrity
✅ DB transactions in listeners  
✅ Idempotent operations  
✅ Rollback safety  
✅ Comprehensive error handling

### Observability
✅ Comprehensive logging  
✅ Job failure tracking  
✅ Import/export audit trails  
✅ Sync operation logging

## Usage Examples

### Dispatching Events

```php
use App\Events\Product\ProductCreated;

event(new ProductCreated(
    product: $product,
    tenantId: $product->tenant_id,
    userId: auth()->id()
));
```

### Dispatching Jobs

```php
use App\Jobs\ProcessBulkImport;

ProcessBulkImport::dispatch(
    tenantId: auth()->user()->tenant_id,
    userId: auth()->id(),
    importType: 'products',
    filePath: $file->path(),
    mapping: ['name' => 'Product Name']
);
```

### Sending Notifications

```php
use App\Notifications\LowStockNotification;

$user->notify(new LowStockNotification(
    productId: $product->id,
    productName: $product->name,
    productSku: $product->sku,
    currentQuantity: $product->current_stock,
    minimumQuantity: $product->reorder_level,
    locationId: $location->id
));
```

## Queue Configuration

### Starting Queue Workers

```bash
# Default queue
php artisan queue:work

# With configuration
php artisan queue:work --queue=high,default,low --tries=3 --timeout=300
```

### Production Setup (Supervisor)

```ini
[program:autoerp-worker]
command=php /path/to/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=8
user=www-data
```

## Monitoring

### View Failed Jobs
```bash
php artisan queue:failed
```

### Retry Failed Jobs
```bash
php artisan queue:retry all
```

### Database Queries
```sql
-- Pending jobs
SELECT COUNT(*) FROM jobs;

-- Failed jobs
SELECT * FROM failed_jobs ORDER BY failed_at DESC;

-- Unread notifications
SELECT COUNT(*) FROM notifications WHERE read_at IS NULL;
```

## Testing

Run the event system tests:

```bash
php artisan test tests/Unit/Events/EventSystemTest.php
```

## Integration Points

### Service Layer Integration

Events should be dispatched from service layer after successful operations:

```php
class ProductService
{
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create($data);
            
            // Dispatch event after successful creation
            event(new ProductCreated(
                product: $product,
                tenantId: $product->tenant_id,
                userId: auth()->id()
            ));
            
            return $product;
        });
    }
}
```

### Automatic Invoice Generation

When a sales order is approved, an invoice is automatically generated:

```php
// In SalesOrderService
public function approve(SalesOrder $order): SalesOrder
{
    $order->update(['status' => 'approved']);
    
    event(new OrderApproved(
        order: $order,
        tenantId: $order->tenant_id,
        userId: auth()->id(),
        approvedBy: auth()->id()
    ));
    
    return $order;
}

// The GenerateInvoiceFromOrder listener handles the rest
```

### Inventory Alerts

When stock movements are recorded, automatic checks trigger alerts:

```php
// In InventoryService
public function recordMovement(array $data): StockLedger
{
    return DB::transaction(function () use ($data) {
        $ledger = StockLedger::create($data);
        
        event(new StockMovementRecorded(
            stockLedger: $ledger,
            tenantId: $ledger->tenant_id,
            userId: auth()->id()
        ));
        
        // Check stock levels
        $product = $ledger->product;
        if ($product->current_stock <= $product->reorder_level) {
            event(new LowStockDetected(...));
        }
        
        return $ledger;
    });
}
```

## Security Considerations

1. **Tenant Isolation**: All operations validate tenant context
2. **Permission Checks**: Notifications only sent to authorized users
3. **Data Sanitization**: All input validated before processing
4. **Audit Trails**: All job executions logged
5. **Rate Limiting**: Implement rate limits on job dispatching

## Performance Optimizations

1. **Queue Priorities**: Use `high`, `default`, `low` queues
2. **Batch Processing**: Group similar operations
3. **Chunking**: Process large datasets in chunks
4. **Caching**: Cache frequently accessed data
5. **Database Indexing**: Proper indexes on queue tables

## Next Steps

### Immediate
1. ✅ Run migrations to create required tables
2. ✅ Register EventServiceProvider
3. ✅ Test event dispatching
4. ✅ Test notifications

### Future Enhancements
1. WebSocket integration for real-time notifications
2. Event sourcing for complete audit trail
3. CQRS pattern implementation
4. Circuit breaker for failure handling
5. Distributed tracing across services

## Files Created

### Events (16 files)
- `app/Events/Product/` (3 events)
- `app/Events/Inventory/` (3 events)
- `app/Events/Sales/` (3 events)
- `app/Events/Invoice/` (3 events)
- `app/Events/CRM/` (2 events)
- `app/Events/Procurement/` (2 events)

### Listeners (8 files)
- `app/Listeners/Inventory/` (2 listeners)
- `app/Listeners/Sales/` (2 listeners)
- `app/Listeners/Invoice/` (2 listeners)
- `app/Listeners/CRM/` (1 listener)
- `app/Listeners/Procurement/` (1 listener)

### Jobs (5 files)
- `app/Jobs/ProcessBulkImport.php`
- `app/Jobs/GenerateReports.php`
- `app/Jobs/SendBulkNotifications.php`
- `app/Jobs/RecalculateInventoryValuation.php`
- `app/Jobs/SyncExternalData.php`

### Notifications (10 files)
- `app/Notifications/` (10 notification classes)

### Migrations (4 files)
- `database/migrations/2024_01_05_000001_create_notifications_table.php`
- `database/migrations/2024_01_05_000002_create_import_logs_table.php`
- `database/migrations/2024_01_05_000003_create_reports_table.php`
- `database/migrations/2024_01_05_000004_create_sync_logs_table.php`

### Configuration (1 file)
- `app/Providers/EventServiceProvider.php`

### Documentation (2 files)
- `docs/EVENT_DRIVEN_ARCHITECTURE.md`
- `EVENT_SYSTEM_README.md` (this file)

### Tests (1 file)
- `tests/Unit/Events/EventSystemTest.php`

## Total Implementation

- **43 files created**
- **16 Events**
- **8 Listeners**
- **5 Jobs**
- **10 Notifications**
- **4 Migrations**
- **1 Service Provider**
- **Comprehensive Documentation**
- **Test Suite**

All components are:
✅ Tenant-aware  
✅ Queue-enabled  
✅ Production-ready  
✅ Fully documented  
✅ Following clean architecture  
✅ Following SOLID principles

## Conclusion

This implementation provides a complete, production-ready event-driven architecture for AutoERP. All components are tenant-aware, permission-controlled, and designed for scalability and maintainability.
