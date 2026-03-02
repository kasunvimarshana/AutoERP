# Procurement Module

Manages vendor management, purchase orders and goods receipt.

## Responsibilities
- Vendor CRUD with credit limit tracking
- Purchase order lifecycle (Draft → Sent → Confirmed → Received/Partial → Billed)
- PO line items with BCMath calculations
- Goods receipt workflow (integrates with Inventory module)
- PO number generation per tenant/month

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/v1/purchase-orders | List purchase orders |
| POST | /api/v1/purchase-orders | Create purchase order |
| GET | /api/v1/purchase-orders/{id} | Get purchase order |
| PUT | /api/v1/purchase-orders/{id} | Update purchase order |
| DELETE | /api/v1/purchase-orders/{id} | Delete purchase order |
| POST | /api/v1/procurement/receive/{id} | Receive goods |
