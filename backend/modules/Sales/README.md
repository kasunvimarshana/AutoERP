# Sales Module

## Overview

The Sales module handles all sales-related operations including orders, quotations, invoicing, and customer relationship management.

## Features

- Sales Order Management (CRUD operations)
- Order status tracking (draft → completed)
- Customer order history
- Multi-item orders with line items
- Tax and discount calculations
- Automated order numbering
- Integration with Inventory for stock management

## Models

### SalesOrder
- Represents a complete sales order
- Tracks order status through lifecycle
- Links to customer and order items
- Calculates totals automatically

### SalesOrderItem
- Individual line items in an order
- Links to products from Inventory
- Supports quantity, pricing, tax, and discounts

## API Endpoints

### Sales Orders

```
GET    /api/sales/orders          - List all orders
POST   /api/sales/orders          - Create new order
GET    /api/sales/orders/{id}     - Get order details
PUT    /api/sales/orders/{id}     - Update order
DELETE /api/sales/orders/{id}     - Delete order
```

## Usage Example

### Create Sales Order

```json
POST /api/sales/orders

{
  "customer_id": "uuid",
  "order_date": "2024-02-05",
  "delivery_date": "2024-02-12",
  "items": [
    {
      "product_id": "uuid",
      "quantity": 10,
      "unit_price": 50.00,
      "tax_rate": 10,
      "discount_amount": 0
    }
  ],
  "notes": "Urgent order"
}
```

## Status Workflow

1. **Draft** - Order being prepared
2. **Pending** - Awaiting approval
3. **Confirmed** - Approved and confirmed
4. **Processing** - Being prepared
5. **Shipped** - Dispatched to customer
6. **Delivered** - Received by customer
7. **Completed** - Fully processed
8. **Cancelled** - Cancelled order

## Integration Points

- **Inventory Module**: Product and stock management
- **CRM Module**: Customer information
- **Accounting Module**: Invoice generation
- **Core Module**: Audit logs and multi-tenancy
