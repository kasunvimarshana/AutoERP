# Procurement Module

---

**⚠️ IMPLEMENTATION PRINCIPLE**: Rely strictly on native Laravel and Vue features. Always implement functionality manually instead of using third-party libraries.

---


Complete procurement management module for the ERP/CRM system with supplier management, purchase requisitions, purchase orders, and goods receipt functionality.

## Overview

The Procurement module provides comprehensive functionality for managing the entire procure-to-pay process, including:

- **Supplier Management**: Maintain supplier information, ratings, and performance tracking
- **Purchase Requisitions**: Request purchasing with multi-level approval workflow
- **Purchase Orders**: Generate and manage purchase orders to suppliers
- **Goods Receipts**: Record received goods with 3-way matching
- **Supplier Performance**: Track and evaluate supplier performance metrics

## Features

### Supplier Management
- Create and manage supplier records
- Track supplier ratings (0-5 stars)
- Manage payment terms and credit limits
- Search suppliers by name, code, or rating
- Evaluate supplier performance (completion rate, on-time delivery)

### Purchase Requisitions
- Create purchase requests with line items
- Multi-level approval workflow (pending/approved/rejected)
- Department-based requisitioning
- Automatic total calculations
- Convert approved requisitions to purchase orders

### Purchase Orders
- Generate POs manually or from requisitions
- Track order status (draft/sent/confirmed/received/closed)
- Support for multiple currencies
- Automatic calculation of subtotal, tax, discounts, and shipping
- Send orders to suppliers
- Track payment status

### Goods Receipts
- Record received goods from purchase orders
- Track received quantities vs. ordered quantities
- 3-way matching (PO, receipt, invoice)
- Warehouse integration
- Confirm receipts and update inventory

## Architecture

The module follows Clean Architecture and SOLID principles:

### Entities (Domain Models)
- `Supplier` - Supplier information and ratings
- `PurchaseRequisition` - Purchase requests
- `PurchaseRequisitionLine` - Requisition line items
- `PurchaseOrder` - Purchase orders
- `PurchaseOrderLine` - PO line items
- `GoodsReceipt` - Received goods records

### Repositories
Each entity has an interface and implementation following the Repository pattern:
- `SupplierRepository`
- `PurchaseRequisitionRepository`
- `PurchaseRequisitionLineRepository`
- `PurchaseOrderRepository`
- `PurchaseOrderLineRepository`
- `GoodsReceiptRepository`

### Services (Business Logic)
- `SupplierService` - Supplier management and rating
- `PurchaseRequisitionService` - Requisition workflow and approval
- `PurchaseOrderService` - PO generation and management
- `GoodsReceiptService` - Receipt processing and 3-way matching

### Events
- `RequisitionApproved` - Fired when a requisition is approved
- `PurchaseOrderCreated` - Fired when a PO is created/sent
- `GoodsReceived` - Fired when goods are received and confirmed
- `SupplierRated` - Fired when a supplier rating is updated

## API Endpoints

### Suppliers
```
GET    /api/v1/suppliers                    - List all suppliers
POST   /api/v1/suppliers                    - Create supplier
GET    /api/v1/suppliers/{id}               - Get supplier details
PUT    /api/v1/suppliers/{id}               - Update supplier
DELETE /api/v1/suppliers/{id}               - Delete supplier
GET    /api/v1/suppliers/search             - Search suppliers
GET    /api/v1/suppliers/by-rating          - Get suppliers by rating
POST   /api/v1/suppliers/{id}/rate          - Rate a supplier
GET    /api/v1/suppliers/{id}/evaluate      - Get supplier performance metrics
```

### Purchase Requisitions
```
GET    /api/v1/purchase-requisitions               - List requisitions
POST   /api/v1/purchase-requisitions               - Create requisition
GET    /api/v1/purchase-requisitions/{id}          - Get requisition details
PUT    /api/v1/purchase-requisitions/{id}          - Update requisition
DELETE /api/v1/purchase-requisitions/{id}          - Delete requisition
POST   /api/v1/purchase-requisitions/{id}/approve  - Approve requisition
POST   /api/v1/purchase-requisitions/{id}/reject   - Reject requisition
GET    /api/v1/purchase-requisitions/search        - Search requisitions
GET    /api/v1/purchase-requisitions/by-status     - Get by status
```

### Purchase Requisition Lines
```
GET    /api/v1/purchase-requisition-lines                        - List lines
POST   /api/v1/purchase-requisition-lines                        - Create line
GET    /api/v1/purchase-requisition-lines/{id}                   - Get line details
PUT    /api/v1/purchase-requisition-lines/{id}                   - Update line
DELETE /api/v1/purchase-requisition-lines/{id}                   - Delete line
GET    /api/v1/purchase-requisition-lines/by-requisition/{id}    - Get by requisition
```

### Purchase Orders
```
GET    /api/v1/purchase-orders                     - List purchase orders
POST   /api/v1/purchase-orders                     - Create PO
GET    /api/v1/purchase-orders/{id}                - Get PO details
PUT    /api/v1/purchase-orders/{id}                - Update PO
DELETE /api/v1/purchase-orders/{id}                - Delete PO
POST   /api/v1/purchase-orders/{id}/send           - Send PO to supplier
POST   /api/v1/purchase-orders/{id}/confirm        - Confirm PO
POST   /api/v1/purchase-orders/{id}/close          - Close PO
POST   /api/v1/purchase-orders/from-requisition    - Create PO from requisition
GET    /api/v1/purchase-orders/search              - Search POs
GET    /api/v1/purchase-orders/by-status           - Get by status
```

### Purchase Order Lines
```
GET    /api/v1/purchase-order-lines                 - List lines
POST   /api/v1/purchase-order-lines                 - Create line
GET    /api/v1/purchase-order-lines/{id}            - Get line details
PUT    /api/v1/purchase-order-lines/{id}            - Update line
DELETE /api/v1/purchase-order-lines/{id}            - Delete line
GET    /api/v1/purchase-order-lines/by-order/{id}   - Get by order
```

### Goods Receipts
```
GET    /api/v1/goods-receipts                              - List receipts
POST   /api/v1/goods-receipts                              - Create receipt
GET    /api/v1/goods-receipts/{id}                         - Get receipt details
PUT    /api/v1/goods-receipts/{id}                         - Update receipt
DELETE /api/v1/goods-receipts/{id}                         - Delete receipt
POST   /api/v1/goods-receipts/{id}/confirm                 - Confirm receipt
POST   /api/v1/goods-receipts/{id}/match                   - Perform 3-way match
GET    /api/v1/goods-receipts/by-purchase-order/{id}       - Get by PO
GET    /api/v1/goods-receipts/search                       - Search receipts
```

## Database Schema

### Suppliers Table
- `id`, `tenant_id`, `code`, `name`, `email`, `phone`, `mobile`
- `website`, `tax_id`, `payment_terms`, `credit_limit`
- `currency`, `rating`, `status`, `notes`, `internal_notes`
- Indexes: tenant_id, code, name, status, rating

### Purchase Requisitions Table
- `id`, `tenant_id`, `requisition_number`, `requester_id`
- `department`, `requested_date`, `required_date`
- `status`, `approval_status`, `approved_by`, `approved_at`
- `supplier_id`, `currency`, `total_amount`
- `notes`, `internal_notes`, `rejection_reason`
- Foreign keys: supplier_id → suppliers

### Purchase Requisition Lines Table
- `id`, `tenant_id`, `purchase_requisition_id`, `product_id`
- `description`, `quantity`, `unit_of_measure`
- `estimated_unit_price`, `estimated_total`, `notes`
- Foreign keys: purchase_requisition_id, product_id

### Purchase Orders Table
- `id`, `tenant_id`, `order_number`, `purchase_requisition_id`
- `supplier_id`, `order_date`, `expected_delivery_date`
- `status`, `payment_status`, `payment_terms`, `currency`
- `subtotal`, `tax_amount`, `discount_amount`, `shipping_amount`, `total_amount`
- `notes`, `internal_notes`, `terms_and_conditions`
- Foreign keys: supplier_id, purchase_requisition_id

### Purchase Order Lines Table
- `id`, `tenant_id`, `purchase_order_id`, `product_id`
- `description`, `quantity`, `unit_of_measure`, `unit_price`
- `tax_rate`, `tax_amount`, `line_total`, `received_quantity`
- Foreign keys: purchase_order_id, product_id

### Goods Receipts Table
- `id`, `tenant_id`, `receipt_number`, `purchase_order_id`
- `received_date`, `received_by`, `status`, `matched_status`
- `warehouse_id`, `notes`, `internal_notes`
- Foreign keys: purchase_order_id, warehouse_id

## Usage Examples

### Create a Supplier
```php
$supplier = SupplierService::create([
    'name' => 'Acme Corp',
    'email' => 'sales@acme.com',
    'phone' => '+1-555-0100',
    'payment_terms' => 'Net 30',
    'currency' => 'USD',
    'status' => 'active',
]);
```

### Create a Purchase Requisition with Lines
```php
$requisition = PurchaseRequisitionService::createWithLines([
    'requester_id' => 1,
    'department' => 'IT',
    'requested_date' => now(),
    'currency' => 'USD',
], [
    [
        'product_id' => 100,
        'quantity' => 10,
        'estimated_unit_price' => 99.99,
    ],
]);
```

### Approve a Requisition
```php
$requisition = PurchaseRequisitionService::approve($requisitionId, $approverId);
```

### Create PO from Requisition
```php
$purchaseOrder = PurchaseOrderService::createFromRequisition($requisitionId, [
    'expected_delivery_date' => now()->addDays(30),
    'payment_terms' => 'Net 30',
]);
```

### Receive Goods
```php
$receipt = GoodsReceiptService::createFromPurchaseOrder($purchaseOrderId, [
    $lineId1 => 10, // Received quantity for line 1
    $lineId2 => 5,  // Received quantity for line 2
], [
    'received_by' => 1,
    'warehouse_id' => 1,
]);
```

### Perform 3-Way Matching
```php
$matched = GoodsReceiptService::performThreeWayMatch($receiptId);
```

## Integration

### With Inventory Module
- Product references in requisition and order lines
- Updates inventory on goods receipt confirmation

### With Accounting Module
- Generates accounts payable on confirmed purchase orders
- Tracks payment status
- Records supplier invoices for 3-way matching

### With Warehouse Module
- Receives goods into specific warehouses
- Updates stock levels on confirmation

## Testing

Run module tests:
```bash
php artisan test --filter Procurement
```

Seed test data:
```bash
php artisan db:seed --class="Modules\Procurement\Database\Seeders\ProcurementSeeder"
```

## Security

- All endpoints protected with `auth:sanctum` and `tenant` middleware
- Tenant isolation enforced in all queries
- Authorization checks in controllers (TODO: Implement policies)
- Input validation via Form Requests
- SQL injection prevention via Eloquent ORM

## Performance Considerations

- Indexes on all foreign keys and frequently queried fields
- Eager loading for relationships to prevent N+1 queries
- Database transactions for multi-table operations
- Soft deletes for data retention

## Future Enhancements

- [ ] Email notifications for requisition approvals
- [ ] PDF generation for purchase orders
- [ ] Supplier portal for PO acknowledgment
- [ ] Advanced approval workflows (multi-level)
- [ ] Budget checking for requisitions
- [ ] Contract management for suppliers
- [ ] RFQ (Request for Quotation) functionality
- [ ] Automated PO generation based on reorder points
- [ ] Supplier catalog integration
- [ ] Advanced analytics and reporting

## License

Part of the AutoERP system.
