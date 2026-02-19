# Sales Module

## Overview

The Sales Module handles the complete sales lifecycle from quotations through orders to invoices. It integrates with CRM, Product, Pricing, and Inventory modules following a strictly modular, plugin-style architecture.

## Features

### Core Entities

1. **Quotation** (Sales Quote)
   - Create quotations for customers
   - Multiple line items with products/services
   - Pricing integration (automatic price calculation)
   - Expiration dates
   - Status tracking (Draft, Sent, Accepted, Rejected, Expired)
   - Convert to Order

2. **Order** (Sales Order)
   - Customer orders with multiple items
   - Product/service line items
   - Quantity, pricing, discounts
   - Status tracking (Draft, Confirmed, Processing, Completed, Cancelled)
   - Payment tracking
   - Integration with Inventory (stock reservation)
   - Convert to Invoice

3. **Invoice** (Sales Invoice)
   - Bill customers for goods/services
   - Multiple line items
   - Tax calculations
   - Payment tracking (Unpaid, Partially Paid, Paid, Overdue)
   - Due dates
   - Integration with Accounting (journal entries)

## Architecture

### Clean Architecture Layers

```
Controllers → Services → Repositories → Models
```

- **Controllers**: Handle HTTP requests, authorization, validation
- **Services**: Business logic, workflows, calculations
- **Repositories**: Data access patterns
- **Models**: Eloquent models with relationships

### Module Integration

- **CRM Module**: Customer and contact relationships
- **Product Module**: Product catalog, units
- **Pricing Module**: Price calculations, discounts, rules
- **Inventory Module**: Stock tracking, reservations
- **Accounting Module**: Journal entries for invoices
- **Audit Module**: Automatic audit logging

## Database Schema

### Tables

1. `quotations` - Sales quotations
2. `quotation_items` - Quotation line items
3. `orders` - Sales orders
4. `order_items` - Order line items
5. `invoices` - Sales invoices
6. `invoice_items` - Invoice line items

## API Endpoints

### Quotations
- `GET    /api/v1/quotations` - List quotations
- `POST   /api/v1/quotations` - Create quotation
- `GET    /api/v1/quotations/{id}` - Get quotation
- `PUT    /api/v1/quotations/{id}` - Update quotation
- `DELETE /api/v1/quotations/{id}` - Delete quotation
- `POST   /api/v1/quotations/{id}/send` - Send to customer
- `POST   /api/v1/quotations/{id}/accept` - Mark as accepted
- `POST   /api/v1/quotations/{id}/reject` - Mark as rejected
- `POST   /api/v1/quotations/{id}/convert` - Convert to order

### Orders
- `GET    /api/v1/orders` - List orders
- `POST   /api/v1/orders` - Create order
- `GET    /api/v1/orders/{id}` - Get order
- `PUT    /api/v1/orders/{id}` - Update order
- `DELETE /api/v1/orders/{id}` - Delete order
- `POST   /api/v1/orders/{id}/confirm` - Confirm order
- `POST   /api/v1/orders/{id}/cancel` - Cancel order
- `POST   /api/v1/orders/{id}/complete` - Mark as completed
- `POST   /api/v1/orders/{id}/invoice` - Convert to invoice

### Invoices
- `GET    /api/v1/invoices` - List invoices
- `POST   /api/v1/invoices` - Create invoice
- `GET    /api/v1/invoices/{id}` - Get invoice
- `PUT    /api/v1/invoices/{id}` - Update invoice
- `DELETE /api/v1/invoices/{id}` - Delete invoice
- `POST   /api/v1/invoices/{id}/send` - Send to customer
- `POST   /api/v1/invoices/{id}/payment` - Record payment
- `GET    /api/v1/invoices/{id}/payments` - List payments

## Events

- `QuotationCreated` - Fired when quotation is created
- `QuotationSent` - Fired when quotation is sent to customer
- `QuotationAccepted` - Fired when quotation is accepted
- `QuotationRejected` - Fired when quotation is rejected
- `OrderCreated` - Fired when order is created
- `OrderConfirmed` - Fired when order is confirmed
- `OrderCancelled` - Fired when order is cancelled
- `OrderCompleted` - Fired when order is completed
- `InvoiceCreated` - Fired when invoice is created
- `InvoicePaymentReceived` - Fired when payment is recorded

## Business Rules

### Quotation Rules
- Must have at least one line item
- Cannot modify quotation after it's accepted
- Auto-expire after expiration date
- Can only convert to order if status is Accepted

### Order Rules
- Must have at least one line item
- Stock reservation on confirmation (if inventory tracking enabled)
- Cannot modify confirmed orders (only cancel)
- Payment tracking (paid amount vs total)

### Invoice Rules
- Must have at least one line item
- Due date defaults to invoice date + payment terms
- Auto-mark overdue if unpaid after due date
- Payment recording updates paid amount and status
- Cannot delete invoices with payments

## Configuration

Configuration file: `config/sales.php`

```php
return [
    'quotation' => [
        'prefix' => env('SALES_QUOTATION_PREFIX', 'QUO-'),
        'default_validity_days' => env('SALES_QUOTATION_VALIDITY', 30),
        'auto_expire' => env('SALES_QUOTATION_AUTO_EXPIRE', true),
    ],
    'order' => [
        'prefix' => env('SALES_ORDER_PREFIX', 'ORD-'),
        'reserve_stock' => env('SALES_ORDER_RESERVE_STOCK', true),
    ],
    'invoice' => [
        'prefix' => env('SALES_INVOICE_PREFIX', 'INV-'),
        'default_payment_terms' => env('SALES_INVOICE_PAYMENT_TERMS', 30), // days
        'auto_overdue' => env('SALES_INVOICE_AUTO_OVERDUE', true),
    ],
];
```

## Dependencies

- **Required Modules**: Core, Tenant, Auth, CRM, Product, Pricing
- **Optional Modules**: Inventory (for stock tracking), Accounting (for journal entries)

## Installation

1. The module is auto-discovered via `SalesServiceProvider`
2. Run migrations: `php artisan migrate`
3. Publish config (optional): `php artisan vendor:publish --tag=sales-config`

## Usage Example

```php
// Create a quotation
$quotation = $quotationService->createQuotation($customerId, [
    'valid_until' => now()->addDays(30),
    'items' => [
        ['product_id' => '...', 'quantity' => 10],
        ['product_id' => '...', 'quantity' => 5],
    ],
]);

// Convert to order
$order = $quotationService->convertToOrder($quotation);

// Confirm order (reserves stock)
$orderService->confirmOrder($order);

// Create invoice from order
$invoice = $orderService->convertToInvoice($order);

// Record payment
$invoiceService->recordPayment($invoice, 1000.00, 'bank_transfer');
```

## Security

- All endpoints require JWT authentication
- Policy-based authorization per entity
- Tenant isolation enforced
- Audit logging for all operations

## Testing

```bash
php artisan test --filter=Sales
```

## License

MIT
