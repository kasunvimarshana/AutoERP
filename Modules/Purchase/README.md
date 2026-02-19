# Purchase Module

The Purchase module provides comprehensive vendor management and procurement functionality for the multi-tenant ERP/CRM platform.

## Features

### Vendor Management
- Complete vendor master data with contact information
- Payment terms and credit limit tracking
- Vendor status management (Active, Inactive, Blocked, Pending)
- Automatic credit limit validation
- Current balance tracking for vendor payables

### Purchase Orders
- Create and manage purchase orders to vendors
- Approval workflow with configurable thresholds
- Send POs to vendors via email/integration
- Track PO confirmation from vendors
- Monitor receipt and billing status
- Cancel POs with reason tracking
- Automatic PO code generation

### Goods Receipts
- Record goods received against purchase orders
- Track quantity received vs. ordered
- Handle partial receipts
- Quality control with acceptance/rejection tracking
- Post receipts to inventory
- Over-receipt tolerance configuration
- Automatic GR code generation

### Vendor Bills
- Create bills from purchase orders or standalone
- Link bills to goods receipts for 3-way matching
- Track payment status and due dates
- Record partial and full payments
- Automatic overdue bill detection
- Update vendor balance on payments
- Automatic bill code generation

## Architecture

### Models
- **Vendor**: Vendor/supplier master data
- **PurchaseOrder**: Purchase orders with line items
- **PurchaseOrderItem**: Individual line items on POs
- **GoodsReceipt**: Goods receipt notes
- **GoodsReceiptItem**: Individual items received
- **Bill**: Vendor bills/invoices
- **BillItem**: Individual line items on bills
- **BillPayment**: Payments made to vendors

All models implement:
- `TenantScoped`: Automatic tenant isolation
- `Auditable`: Comprehensive audit logging
- `HasUlids`: Distributed ID generation
- `SoftDeletes`: Data retention

### Services
- **VendorService**: Vendor lifecycle and credit management
- **PurchaseOrderService**: PO workflow and approval
- **GoodsReceiptService**: Receipt processing and inventory posting
- **BillService**: Bill management and payment recording

### Domain Events
- `VendorCreated`
- `PurchaseOrderCreated`, `PurchaseOrderApproved`, `PurchaseOrderSent`, `PurchaseOrderConfirmed`, `PurchaseOrderCancelled`
- `GoodsReceiptCreated`, `GoodsReceiptPosted`
- `BillCreated`, `BillSent`, `BillPaymentRecorded`

## API Endpoints

### Vendors
- `GET /api/purchase/vendors` - List vendors with filters
- `POST /api/purchase/vendors` - Create vendor
- `GET /api/purchase/vendors/{id}` - Get vendor details
- `PUT /api/purchase/vendors/{id}` - Update vendor
- `DELETE /api/purchase/vendors/{id}` - Delete vendor
- `POST /api/purchase/vendors/{id}/activate` - Activate vendor
- `POST /api/purchase/vendors/{id}/deactivate` - Deactivate vendor
- `POST /api/purchase/vendors/{id}/block` - Block vendor

### Purchase Orders
- `GET /api/purchase/purchase-orders` - List POs with filters
- `POST /api/purchase/purchase-orders` - Create PO
- `GET /api/purchase/purchase-orders/{id}` - Get PO details
- `PUT /api/purchase/purchase-orders/{id}` - Update PO
- `DELETE /api/purchase/purchase-orders/{id}` - Delete PO
- `POST /api/purchase/purchase-orders/{id}/approve` - Approve PO
- `POST /api/purchase/purchase-orders/{id}/send` - Send PO to vendor
- `POST /api/purchase/purchase-orders/{id}/confirm` - Confirm PO receipt
- `POST /api/purchase/purchase-orders/{id}/cancel` - Cancel PO

### Goods Receipts
- `GET /api/purchase/goods-receipts` - List receipts
- `POST /api/purchase/goods-receipts` - Create receipt
- `GET /api/purchase/goods-receipts/{id}` - Get receipt details
- `PUT /api/purchase/goods-receipts/{id}` - Update receipt
- `DELETE /api/purchase/goods-receipts/{id}` - Delete receipt
- `POST /api/purchase/goods-receipts/{id}/confirm` - Confirm receipt
- `POST /api/purchase/goods-receipts/{id}/post-to-inventory` - Post to inventory
- `POST /api/purchase/goods-receipts/{id}/cancel` - Cancel receipt

### Bills
- `GET /api/purchase/bills` - List bills with filters
- `POST /api/purchase/bills` - Create bill
- `GET /api/purchase/bills/{id}` - Get bill details
- `PUT /api/purchase/bills/{id}` - Update bill
- `DELETE /api/purchase/bills/{id}` - Delete bill
- `POST /api/purchase/bills/{id}/send` - Send bill
- `POST /api/purchase/bills/{id}/record-payment` - Record payment
- `POST /api/purchase/bills/{id}/cancel` - Cancel bill

## Configuration

All configuration is in `config/purchase.php`:

```php
// Code prefixes for auto-generation
'vendor_code_prefix' => env('PURCHASE_VENDOR_CODE_PREFIX', 'VEN-'),
'po_code_prefix' => env('PURCHASE_PO_CODE_PREFIX', 'PO-'),
'gr_code_prefix' => env('PURCHASE_GR_CODE_PREFIX', 'GR-'),
'bill_code_prefix' => env('PURCHASE_BILL_CODE_PREFIX', 'BILL-'),

// Purchase order settings
'po_requires_approval' => env('PURCHASE_PO_REQUIRES_APPROVAL', true),
'po_approval_threshold' => env('PURCHASE_PO_APPROVAL_THRESHOLD', 10000),

// Goods receipt settings
'gr_auto_post_to_inventory' => env('PURCHASE_GR_AUTO_POST_TO_INVENTORY', false),
'gr_allow_over_receipt' => env('PURCHASE_GR_ALLOW_OVER_RECEIPT', false),
'gr_over_receipt_tolerance_percent' => env('PURCHASE_GR_OVER_RECEIPT_TOLERANCE', 5.0),

// Bill settings
'bill_default_due_days' => env('PURCHASE_BILL_DEFAULT_DUE_DAYS', 30),
'bill_requires_gr' => env('PURCHASE_BILL_REQUIRES_GR', false),
'bill_allow_partial_payment' => env('PURCHASE_BILL_ALLOW_PARTIAL_PAYMENT', true),

// 3-way matching
'3way_matching_enabled' => env('PURCHASE_3WAY_MATCHING_ENABLED', false),
'3way_matching_tolerance_percent' => env('PURCHASE_3WAY_MATCHING_TOLERANCE', 2.0),

// Vendor settings
'vendor_credit_limit_check' => env('PURCHASE_VENDOR_CREDIT_LIMIT_CHECK', true),
```

## Workflows

### Purchase-to-Pay Cycle
1. **Purchase Request** → Create PO
2. **PO Approval** → Approve PO (if required)
3. **Send to Vendor** → Send PO to vendor
4. **Vendor Confirmation** → Confirm PO
5. **Goods Receipt** → Record goods received
6. **Post to Inventory** → Update stock levels
7. **Vendor Bill** → Create bill from PO/GR
8. **Payment** → Record payment to vendor

### 3-Way Matching (Optional)
- Match Purchase Order → Goods Receipt → Vendor Bill
- Ensure quantities and prices match within tolerance
- Automatic discrepancy detection

## Security

- All endpoints protected by authentication
- Policy-based authorization on all actions
- Tenant isolation enforced at query level
- Credit limit validation before PO approval
- Status-based workflow validation

## Data Integrity

- All financial calculations use BCMath
- Database transactions for all mutations
- Foreign key constraints
- Optimistic locking for concurrency
- Comprehensive audit logging

## Integration Points

- **Product Module**: Product and unit information
- **Inventory Module**: Stock updates on goods receipt
- **Accounting Module**: Journal entries for bills and payments
- **Audit Module**: Automatic audit trail
- **Notification Module**: Email alerts for approvals, overdue bills

## Testing

Run module tests:
```bash
php artisan test --filter Purchase
```

## License

MIT License - Part of the Multi-Tenant Enterprise ERP/CRM SaaS Platform
