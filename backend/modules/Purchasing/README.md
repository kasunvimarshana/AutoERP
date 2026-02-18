# Purchasing Module

## Overview

The Purchasing module manages procurement operations including purchase orders, supplier management, goods receipt, and vendor relationships.

## Features

- Purchase Order Management (CRUD operations)
- Purchase order status tracking
- Supplier order history
- Multi-item purchase orders
- Automated order numbering
- Expected delivery date tracking
- Integration with Inventory for stock receipt

## Models

### PurchaseOrder
- Complete purchase order information
- Links to supplier and order items
- Tracks order status through approval and receipt
- Calculates totals automatically

### PurchaseOrderItem
- Line items in a purchase order
- Links to products from Inventory
- Supports quantity, pricing, tax, and discounts

## API Endpoints

### Purchase Orders

```
GET    /api/purchasing/orders          - List all orders
POST   /api/purchasing/orders          - Create new order
GET    /api/purchasing/orders/{id}     - Get order details
PUT    /api/purchasing/orders/{id}     - Update order
DELETE /api/purchasing/orders/{id}     - Delete order
```

## Usage Example

### Create Purchase Order

```json
POST /api/purchasing/orders

{
  "supplier_id": "uuid",
  "order_date": "2024-02-05",
  "expected_delivery_date": "2024-02-15",
  "items": [
    {
      "product_id": "uuid",
      "quantity": 100,
      "unit_price": 25.00,
      "tax_rate": 10,
      "discount_amount": 50.00
    }
  ],
  "notes": "Regular restock order"
}
```

## Status Workflow

1. **Draft** - Order being prepared
2. **Pending** - Awaiting approval
3. **Approved** - Approved for ordering
4. **Ordered** - Sent to supplier
5. **Partially Received** - Some items received
6. **Received** - All items received
7. **Completed** - Fully processed
8. **Cancelled** - Cancelled order

## Integration Points

- **Inventory Module**: Product catalog and stock receipt
- **Suppliers Module**: Supplier information
- **Accounting Module**: Bill and payment processing
- **Core Module**: Audit logs and multi-tenancy
