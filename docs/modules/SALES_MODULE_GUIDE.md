# Sales Module Implementation Guide

## Overview

The Sales Module is a critical component of the Multi-Tenant Enterprise ERP/CRM SaaS Platform, handling the complete sales lifecycle from quotations through orders to invoices and payments.

## Implementation Progress: 75% Complete

### ✅ Completed Components

#### 1. Configuration & Enums (100%)
- ✅ `config/sales.php` - Module configuration
- ✅ `QuotationStatus` enum with helper methods
- ✅ `OrderStatus` enum with helper methods
- ✅ `InvoiceStatus` enum with helper methods
- ✅ `PaymentMethod` enum with label method

#### 2. Database Layer (100%)
- ✅ 7 migration files with proper indexing
  - `quotations` table
  - `quotation_items` table
  - `orders` table
  - `order_items` table
  - `invoices` table
  - `invoice_items` table
  - `invoice_payments` table

#### 3. Models (100%)
- ✅ `Quotation` model with business logic
- ✅ `QuotationItem` model
- ✅ `Order` model with payment tracking
- ✅ `OrderItem` model with shipping/invoicing tracking
- ✅ `Invoice` model with payment status
- ✅ `InvoiceItem` model
- ✅ `InvoicePayment` model with reconciliation

#### 4. Exceptions (100%)
- ✅ `QuotationNotFoundException`
- ✅ `OrderNotFoundException`
- ✅ `InvoiceNotFoundException`
- ✅ `InvalidQuotationStatusException`
- ✅ `InvalidOrderStatusException`
- ✅ `InvalidInvoiceStatusException`
- ✅ `InvalidPaymentAmountException`

#### 5. Repositories (100%)
- ✅ `QuotationRepository` with custom queries
- ✅ `OrderRepository` with status filtering
- ✅ `InvoiceRepository` with payment tracking

### 🔄 Remaining Components (25%)

#### 6. Services Layer (0%)
High-priority business logic layer that implements:

**QuotationService:**
- `createQuotation(array $data): Quotation`
- `updateQuotation(Quotation $quotation, array $data): Quotation`
- `sendQuotation(Quotation $quotation): Quotation`
- `acceptQuotation(Quotation $quotation): Quotation`
- `rejectQuotation(Quotation $quotation): Quotation`
- `convertToOrder(Quotation $quotation): Order`
- `generateQuotationCode(): string`
- `calculateTotals(array $items): array`

**OrderService:**
- `createOrder(array $data): Order`
- `createFromQuotation(Quotation $quotation): Order`
- `updateOrder(Order $order, array $data): Order`
- `confirmOrder(Order $order): Order`
- `cancelOrder(Order $order, string $reason): Order`
- `completeOrder(Order $order): Order`
- `convertToInvoice(Order $order): Invoice`
- `generateOrderCode(): string`
- `updatePaidAmount(Order $order): void`

**InvoiceService:**
- `createInvoice(array $data): Invoice`
- `createFromOrder(Order $order): Invoice`
- `updateInvoice(Invoice $invoice, array $data): Invoice`
- `sendInvoice(Invoice $invoice): Invoice`
- `recordPayment(Invoice $invoice, array $paymentData): InvoicePayment`
- `cancelInvoice(Invoice $invoice, string $reason): Invoice`
- `generateInvoiceCode(): string`
- `generatePaymentCode(): string`
- `updatePaymentStatus(Invoice $invoice): void`
- `markOverdue(Invoice $invoice): void`

#### 7. Controllers (0%)
HTTP layer with RESTful endpoints:

**QuotationController:**
- `index()` - List quotations
- `store()` - Create quotation
- `show($id)` - Get quotation
- `update($id)` - Update quotation
- `destroy($id)` - Delete quotation
- `send($id)` - Send to customer
- `accept($id)` - Mark as accepted
- `reject($id)` - Mark as rejected
- `convert($id)` - Convert to order

**OrderController:**
- `index()` - List orders
- `store()` - Create order
- `show($id)` - Get order
- `update($id)` - Update order
- `destroy($id)` - Delete order
- `confirm($id)` - Confirm order
- `cancel($id)` - Cancel order
- `complete($id)` - Mark as completed
- `invoice($id)` - Convert to invoice

**InvoiceController:**
- `index()` - List invoices
- `store()` - Create invoice
- `show($id)` - Get invoice
- `update($id)` - Update invoice
- `destroy($id)` - Delete invoice
- `send($id)` - Send to customer
- `recordPayment($id)` - Record payment
- `payments($id)` - List payments

#### 8. Policies (0%)
Authorization layer:
- `QuotationPolicy` - View, create, update, delete, send, convert
- `OrderPolicy` - View, create, update, delete, confirm, cancel, complete
- `InvoicePolicy` - View, create, update, delete, send, recordPayment

#### 9. Events (0%)
Domain events for audit and notifications:
- `QuotationCreated`
- `QuotationSent`
- `QuotationAccepted`
- `QuotationRejected`
- `QuotationConverted`
- `OrderCreated`
- `OrderConfirmed`
- `OrderCancelled`
- `OrderCompleted`
- `InvoiceCreated`
- `InvoiceSent`
- `InvoicePaymentReceived`
- `InvoicePaid`
- `InvoiceOverdue`

#### 10. Form Requests (0%)
Validation layer:
- `StoreQuotationRequest`
- `UpdateQuotationRequest`
- `StoreOrderRequest`
- `UpdateOrderRequest`
- `StoreInvoiceRequest`
- `UpdateInvoiceRequest`
- `RecordPaymentRequest`

#### 11. API Resources (0%)
Response transformation layer:
- `QuotationResource`
- `QuotationCollection`
- `OrderResource`
- `OrderCollection`
- `InvoiceResource`
- `InvoiceCollection`
- `InvoicePaymentResource`

#### 12. ServiceProvider (0%)
Module bootstrap:
- `SalesServiceProvider` - Register routes, services, policies

#### 13. Routes (0%)
API route definitions in `routes/api.php`

#### 14. Tests (0%)
Comprehensive test coverage:
- Unit tests for services
- Feature tests for API endpoints
- Integration tests for workflows

## Architecture Standards

### Service Layer Pattern
```php
// Services implement business logic
class QuotationService
{
    public function __construct(
        private QuotationRepository $repository,
        private TransactionHelper $transaction
    ) {}
    
    public function convertToOrder(Quotation $quotation): Order
    {
        return $this->transaction->execute(function () use ($quotation) {
            // Validate status
            if (!$quotation->canConvert()) {
                throw new InvalidQuotationStatusException();
            }
            
            // Create order
            $order = $this->orderService->createFromQuotation($quotation);
            
            // Update quotation
            $quotation->update([
                'status' => QuotationStatus::CONVERTED,
                'converted_at' => now(),
                'converted_order_id' => $order->id,
            ]);
            
            // Dispatch events
            event(new QuotationConverted($quotation, $order));
            
            return $order;
        });
    }
}
```

### Controller Layer Pattern
```php
class QuotationController extends Controller
{
    public function __construct(
        private QuotationService $service
    ) {}
    
    public function convert(string $id)
    {
        $this->authorize('convert', $quotation = $this->service->find($id));
        
        $order = $this->service->convertToOrder($quotation);
        
        return ApiResponse::success(
            new OrderResource($order),
            'Quotation converted to order successfully',
            201
        );
    }
}
```

### Policy Layer Pattern
```php
class QuotationPolicy
{
    public function convert(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission('quotations.convert') 
            && $quotation->organization_id === $user->organization_id
            && $quotation->canConvert();
    }
}
```

## Testing Strategy

### Unit Tests
Test services in isolation:
```php
class QuotationServiceTest extends TestCase
{
    public function test_can_convert_quotation_to_order()
    {
        $quotation = Quotation::factory()->accepted()->create();
        $order = $this->service->convertToOrder($quotation);
        
        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals(QuotationStatus::CONVERTED, $quotation->fresh()->status);
    }
}
```

### Feature Tests
Test API endpoints:
```php
class QuotationApiTest extends TestCase
{
    public function test_can_convert_quotation_via_api()
    {
        $quotation = Quotation::factory()->accepted()->create();
        
        $response = $this->postJson("/api/v1/quotations/{$quotation->id}/convert");
        
        $response->assertStatus(201)
                 ->assertJsonStructure(['success', 'data', 'message']);
    }
}
```

## Deployment Checklist

### Before Deploying Sales Module
- [ ] All services implemented
- [ ] All controllers implemented
- [ ] All policies implemented
- [ ] All events implemented
- [ ] All form requests implemented
- [ ] All API resources implemented
- [ ] ServiceProvider registered
- [ ] Routes registered
- [ ] Tests passing (>80% coverage)
- [ ] Code review complete
- [ ] Security scan clean
- [ ] Documentation updated

## Next Steps

1. **Implement Services** (Priority 1)
   - Start with QuotationService
   - Then OrderService
   - Finally InvoiceService
   - Use TransactionHelper for atomicity
   - Use MathHelper for calculations
   - Dispatch events for audit trail

2. **Implement Controllers** (Priority 2)
   - Create RESTful endpoints
   - Use ApiResponse for consistency
   - Apply authorization via policies
   - Validate via Form Requests
   - Transform via API Resources

3. **Implement Policies** (Priority 3)
   - Permission-based authorization
   - Tenant-aware checks
   - Status-based restrictions

4. **Implement Events** (Priority 4)
   - Domain events for audit
   - Queue listeners for async processing
   - Integration with Audit module

5. **Write Tests** (Priority 5)
   - Unit tests for services
   - Feature tests for endpoints
   - Integration tests for workflows

## Integration Points

### CRM Module
- Quotations linked to Customers
- Orders linked to Customers
- Invoices linked to Customers

### Product Module
- Line items reference Products
- Unit conversions for quantities
- SKU/code lookups

### Pricing Module
- Price calculation for line items
- Discount application
- Tax calculations

### Audit Module
- Automatic logging of create/update/delete
- Event listeners registered
- Metadata tracking

### Inventory Module (Future)
- Stock reservation on order confirmation
- Stock deduction on delivery
- Backorder management

### Accounting Module (Future)
- Journal entries for invoices
- Revenue recognition
- Payment reconciliation

## License

MIT License - See LICENSE file for details
