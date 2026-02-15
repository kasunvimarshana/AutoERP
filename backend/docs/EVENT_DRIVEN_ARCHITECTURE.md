# Event-Driven Architecture & Queue System

## Overview

This document describes the event-driven architecture and queue system implemented for AutoERP. The system provides a scalable, tenant-aware asynchronous workflow mechanism.

## Architecture

### Events (app/Events/)

Domain events represent significant state changes in the system. All events are:
- **Tenant-aware**: Include tenant context for proper isolation
- **Immutable**: Carry readonly data about what happened
- **Serializable**: Can be queued and processed asynchronously

#### Product Events
- **ProductCreated**: Fired when a new product is created
- **ProductUpdated**: Fired when a product is updated (includes change tracking)
- **ProductDeleted**: Fired when a product is deleted

#### Inventory Events
- **StockMovementRecorded**: Fired when stock movement is recorded in ledger
- **LowStockDetected**: Fired when product stock falls below reorder level
- **StockExpiring**: Fired when stock batch is approaching expiry date

#### Sales Events
- **OrderCreated**: Fired when a new sales order is created
- **OrderApproved**: Fired when a sales order is approved
- **OrderFulfilled**: Fired when a sales order is fulfilled

#### Invoice Events
- **InvoiceGenerated**: Fired when an invoice is generated
- **InvoicePaymentReceived**: Fired when payment is received for an invoice
- **InvoiceOverdue**: Fired when an invoice becomes overdue

#### CRM Events
- **CustomerCreated**: Fired when a new customer is created
- **LeadConverted**: Fired when a lead is converted to a customer

#### Procurement Events
- **PurchaseOrderApproved**: Fired when a purchase order is approved
- **GoodsReceived**: Fired when goods are received against a purchase order

### Listeners (app/Listeners/)

Event listeners handle asynchronous processing triggered by events. All listeners:
- **Implement ShouldQueue**: Run asynchronously via queue workers
- **Are tenant-aware**: Respect tenant isolation
- **Handle failures gracefully**: Use InteractsWithQueue for retry logic

#### Inventory Listeners
- **SendLowStockNotification**: Notifies users when stock is low
- **SendStockExpiryAlert**: Alerts users about expiring stock

#### Sales Listeners
- **UpdateInventoryOnSale**: Updates inventory statistics after sale fulfillment
- **GenerateInvoiceFromOrder**: Automatically generates invoice from approved order

#### Invoice Listeners
- **SendInvoiceToCustomer**: Sends invoice notification to customer contacts
- **SendPaymentConfirmation**: Sends payment confirmation to customer and staff

#### CRM Listeners
- **UpdateCustomerStatistics**: Recalculates customer statistics (orders, spending, etc.)

#### Procurement Listeners
- **NotifyPurchaseOrderApproval**: Notifies stakeholders of purchase order approval

### Jobs (app/Jobs/)

Background jobs handle long-running operations. All jobs:
- **Implement ShouldQueue**: Execute asynchronously
- **Define retry logic**: Include $tries and $timeout properties
- **Are tenant-aware**: Operate within tenant context
- **Log extensively**: Provide audit trail of operations

#### ProcessBulkImport
Handles bulk CSV/file imports for:
- Products
- Customers
- Vendors
- Inventory

**Features**:
- Row-by-row error handling
- Comprehensive logging
- Import status tracking
- Field mapping support

#### GenerateReports
Generates various report types:
- Sales reports
- Inventory reports
- Financial reports
- Customer reports
- Procurement reports

**Features**:
- Flexible date ranges
- Multiple output formats
- File storage
- Notification on completion

#### SendBulkNotifications
Sends notifications to multiple users:
- Announcements
- Reminders
- Alerts

**Features**:
- Batch processing
- Individual failure handling
- Comprehensive logging

#### RecalculateInventoryValuation
Recalculates inventory valuation using FIFO method:
- Product-level or location-level
- Batch tracking
- Average cost calculation

**Features**:
- FIFO cost flow
- Tenant-aware
- Transaction-safe

#### SyncExternalData
Synchronizes data with external systems:
- Product sync
- Customer sync
- Inventory sync
- Order sync

**Features**:
- API integration
- Error handling
- Sync logging

### Notifications (app/Notifications/)

Database-backed notifications for user alerts. All notifications:
- **Use database channel**: Stored in notifications table
- **Implement ShouldQueue**: Queued for performance
- **Include metadata**: Rich data for frontend display

#### Business Notifications
- **LowStockNotification**: Product stock below minimum
- **StockExpiryNotification**: Stock approaching expiry (severity based on days)
- **InvoiceGeneratedNotification**: New invoice created
- **PaymentReceivedNotification**: Payment received confirmation
- **OrderStatusNotification**: Order status updates
- **PurchaseOrderApprovedNotification**: PO approval notice

#### System Notifications
- **ReportGeneratedNotification**: Report ready for download
- **AnnouncementNotification**: System-wide announcements
- **ReminderNotification**: Task/event reminders
- **AlertNotification**: System alerts

## Database Schema

### Notifications Table
```sql
- id (uuid, primary)
- type (string)
- notifiable_type (string)
- notifiable_id (bigint)
- data (json)
- read_at (timestamp, nullable)
- created_at, updated_at
```

### Import Logs Table
```sql
- id
- tenant_id (foreign key)
- user_id (foreign key)
- import_type
- file_path
- total_rows, imported_rows, failed_rows
- errors (json)
- status (enum)
- completed_at, failed_at
- created_at, updated_at
```

### Reports Table
```sql
- id
- tenant_id (foreign key)
- user_id (foreign key)
- report_type
- file_name
- parameters (json)
- status (enum)
- error_message
- generated_at, failed_at
- created_at, updated_at
```

### Sync Logs Table
```sql
- id
- tenant_id (foreign key)
- sync_type
- status (enum)
- error_message
- synced_at, failed_at
- created_at, updated_at
```

## Usage Examples

### Dispatching Events

```php
use App\Events\Product\ProductCreated;

// In service layer
DB::transaction(function () use ($data) {
    $product = Product::create($data);
    
    // Dispatch event
    event(new ProductCreated(
        product: $product,
        tenantId: $product->tenant_id,
        userId: auth()->id()
    ));
    
    return $product;
});
```

### Dispatching Jobs

```php
use App\Jobs\ProcessBulkImport;

// Dispatch import job
ProcessBulkImport::dispatch(
    tenantId: auth()->user()->tenant_id,
    userId: auth()->id(),
    importType: 'products',
    filePath: $uploadedFile->path(),
    mapping: ['name' => 'Product Name', 'sku' => 'SKU']
);
```

### Sending Notifications

```php
use App\Notifications\LowStockNotification;

// Send to specific user
$user->notify(new LowStockNotification(
    productId: $product->id,
    productName: $product->name,
    productSku: $product->sku,
    currentQuantity: $product->current_stock,
    minimumQuantity: $product->reorder_level,
    locationId: $location->id
));

// Or dispatch bulk notifications
SendBulkNotifications::dispatch(
    tenantId: $tenantId,
    userIds: $userIds,
    notificationType: 'announcement',
    notificationData: ['title' => 'System Maintenance', 'message' => '...']
);
```

## Queue Workers

### Starting Queue Workers

```bash
# Start default queue worker
php artisan queue:work

# Start with specific queue
php artisan queue:work --queue=high,default,low

# Start with timeout and memory limits
php artisan queue:work --timeout=300 --memory=512

# Start with retry configuration
php artisan queue:work --tries=3 --backoff=10
```

### Production Configuration

Use Supervisor to keep queue workers running:

```ini
[program:autoerp-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/path/to/worker.log
stopwaitsecs=3600
```

## Monitoring

### Queue Statistics

```bash
# View failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {id}

# Retry all failed jobs
php artisan queue:retry all

# Flush failed jobs
php artisan queue:flush
```

### Database Monitoring

```sql
-- Check pending jobs
SELECT COUNT(*) FROM jobs WHERE queue = 'default';

-- Check failed jobs
SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 10;

-- Check notifications
SELECT notifiable_id, COUNT(*) as unread_count 
FROM notifications 
WHERE read_at IS NULL 
GROUP BY notifiable_id;

-- Check import status
SELECT import_type, status, COUNT(*) 
FROM import_logs 
GROUP BY import_type, status;
```

## Configuration

### Queue Connection (.env)
```env
QUEUE_CONNECTION=database
DB_QUEUE_TABLE=jobs
DB_QUEUE=default
DB_QUEUE_RETRY_AFTER=90
```

### Queue Priorities
- **high**: Critical operations (payments, stock movements)
- **default**: Normal operations (notifications, updates)
- **low**: Background tasks (reports, syncs, cleanup)

## Best Practices

### Event Design
1. ✅ Events should represent past actions (past tense: "Created", "Updated")
2. ✅ Include all necessary context (tenant_id, user_id)
3. ✅ Keep events focused and single-purpose
4. ✅ Use readonly properties for immutability

### Listener Design
1. ✅ Listeners should be idempotent (safe to retry)
2. ✅ Handle failures gracefully
3. ✅ Log important operations
4. ✅ Keep listeners focused on single responsibility

### Job Design
1. ✅ Jobs should be serializable
2. ✅ Define appropriate timeout and retry limits
3. ✅ Implement failed() method for cleanup
4. ✅ Use DB transactions for data consistency

### Notification Design
1. ✅ Include actionable information
2. ✅ Provide appropriate severity levels
3. ✅ Include action URLs for user navigation
4. ✅ Keep message content clear and concise

## Tenant Isolation

All events, listeners, jobs, and notifications respect tenant boundaries:

1. **Event Context**: Events include tenant_id
2. **Global Scopes**: Models automatically filter by tenant
3. **Queue Isolation**: Jobs operate within tenant context
4. **Notification Filtering**: Users only see their tenant's notifications

## Testing

### Testing Events

```php
use Illuminate\Support\Facades\Event;

Event::fake([ProductCreated::class]);

// Perform action
$product = $this->productService->create($data);

// Assert event dispatched
Event::assertDispatched(ProductCreated::class, function ($event) use ($product) {
    return $event->product->id === $product->id;
});
```

### Testing Jobs

```php
use Illuminate\Support\Facades\Queue;

Queue::fake();

// Dispatch job
ProcessBulkImport::dispatch($tenantId, $userId, 'products', $file, $mapping);

// Assert job dispatched
Queue::assertPushed(ProcessBulkImport::class);
```

### Testing Notifications

```php
use Illuminate\Support\Facades\Notification;

Notification::fake();

// Trigger notification
$this->invoiceService->generate($order);

// Assert notification sent
Notification::assertSentTo($user, InvoiceGeneratedNotification::class);
```

## Security Considerations

1. **Tenant Isolation**: Always validate tenant context in jobs/listeners
2. **Permission Checks**: Verify user permissions before sending notifications
3. **Data Sanitization**: Sanitize user input in bulk operations
4. **Rate Limiting**: Implement rate limits on job dispatching
5. **Audit Logging**: Log all job executions and failures

## Performance Optimization

1. **Queue Prioritization**: Use multiple queues for different priorities
2. **Batch Processing**: Group similar operations when possible
3. **Chunking**: Process large datasets in chunks
4. **Caching**: Cache frequently accessed data in jobs
5. **Database Indexing**: Index queue and notification tables properly

## Troubleshooting

### Jobs Not Processing
- Check queue worker is running: `ps aux | grep queue:work`
- Verify QUEUE_CONNECTION in .env
- Check failed_jobs table for errors

### Events Not Firing
- Verify EventServiceProvider registration
- Check event namespace and class name
- Ensure transaction commit if using after_commit

### Notifications Not Appearing
- Verify notifications table exists
- Check notifiable_type and notifiable_id
- Ensure user model uses Notifiable trait

## Future Enhancements

1. **Real-time Notifications**: WebSocket integration for instant updates
2. **Event Sourcing**: Full event sourcing pattern for audit trail
3. **CQRS**: Command Query Responsibility Segregation
4. **Dead Letter Queue**: Advanced failure handling
5. **Circuit Breaker**: Automatic failure detection and recovery
6. **Distributed Tracing**: Cross-service event tracking

## Conclusion

This event-driven architecture provides a scalable, maintainable foundation for asynchronous workflows in AutoERP. All components are tenant-aware, permission-controlled, and production-ready.
