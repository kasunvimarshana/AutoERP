# Inventory Module

## Overview

The Inventory Module provides comprehensive warehouse management, stock tracking, and inventory valuation capabilities for the ERP/CRM platform. It manages real-time stock levels, multi-warehouse operations, stock movements, physical counts, and integrates seamlessly with Sales and Purchase modules.

## Key Features

### 1. Warehouse Management
- **Multi-warehouse support**: Manage multiple physical warehouses
- **Bin location tracking**: Organize stock within warehouses using bin locations
- **Warehouse status management**: Active, Inactive, Maintenance, Closed states
- **Location hierarchy**: Support for complex warehouse layouts

### 2. Stock Tracking
- **Real-time stock levels**: Instant visibility of available stock
- **Stock movements**: Track all inventory transactions (receipts, issues, transfers, adjustments)
- **Batch/Lot tracking**: Track products by batch or lot numbers
- **Serial number tracking**: Individual item tracking via serial numbers
- **Stock reservations**: Reserve stock for sales orders

### 3. Inventory Valuation
- **Multiple valuation methods**:
  - FIFO (First In First Out)
  - LIFO (Last In First Out)
  - Weighted Average
  - Standard Cost
- **Automatic cost calculation**: Based on selected valuation method
- **Valuation reporting**: Stock value reports by warehouse, category, or product

### 4. Stock Movements
- **Receipt**: Receive stock into warehouse (from purchases)
- **Issue**: Issue stock from warehouse (for sales/production)
- **Transfer**: Move stock between warehouses or locations
- **Adjustment**: Manual stock adjustments with approval workflow
- **Scrap**: Write off damaged or obsolete stock
- **Return**: Process customer/vendor returns

### 5. Physical Stock Counts
- **Planned counts**: Schedule periodic stock counts
- **Cycle counting**: Continuous rolling inventory counts
- **Count reconciliation**: Compare counted vs. system quantities
- **Automatic adjustments**: Update stock levels based on counts
- **Variance reporting**: Identify and analyze discrepancies

### 6. Reorder Management
- **Reorder point alerts**: Automatic notifications when stock falls below threshold
- **Min/Max levels**: Define minimum and maximum stock levels per product
- **Safety stock**: Configure safety stock quantities
- **Lead time tracking**: Track supplier lead times for reorder calculations

## Module Structure

### Models
- **Warehouse**: Physical storage locations
- **StockLocation**: Bin/shelf locations within warehouses
- **StockItem**: Product inventory records with quantities
- **StockMovement**: All inventory transactions
- **StockCount**: Physical inventory count records
- **StockCountItem**: Individual count line items
- **BatchLot**: Batch/lot tracking records
- **SerialNumber**: Serial number tracking records

### Enums
- **StockMovementType**: Receipt, Issue, Transfer, Adjustment, Count, Return, Scrap, Reserved, Released
- **ValuationMethod**: FIFO, LIFO, WeightedAverage, StandardCost
- **StockCountStatus**: Planned, InProgress, Completed, Reconciled, Cancelled
- **WarehouseStatus**: Active, Inactive, Maintenance, Closed

### Services
- **WarehouseService**: Warehouse CRUD and management
- **StockMovementService**: Process all types of stock movements
- **InventoryValuationService**: Calculate stock values using different methods
- **StockCountService**: Manage physical inventory counts
- **ReorderService**: Analyze stock levels and generate reorder suggestions
- **SerialNumberService**: Manage serial number tracking

### Repositories
- **WarehouseRepository**: Warehouse data access
- **StockItemRepository**: Stock item queries and reporting
- **StockMovementRepository**: Movement history and analytics
- **StockCountRepository**: Count data management

### Controllers (API)
- **WarehouseController**: Warehouse CRUD operations
- **StockItemController**: Stock level queries and reports
- **StockMovementController**: Stock movement processing
- **StockCountController**: Physical count management
- **ReorderController**: Reorder point analysis

### Policies
- **WarehousePolicy**: Authorization for warehouse operations
- **StockMovementPolicy**: Authorization for stock movements
- **StockCountPolicy**: Authorization for inventory counts

## Database Schema

### Tables
1. **warehouses**: Physical warehouse locations
2. **stock_locations**: Bin/shelf locations within warehouses
3. **stock_items**: Current stock quantities per product/warehouse
4. **stock_movements**: Historical record of all inventory transactions
5. **stock_counts**: Physical inventory count headers
6. **stock_count_items**: Individual count line items
7. **batch_lots**: Batch/lot tracking
8. **serial_numbers**: Serial number tracking

### Key Indexes
- Product + Warehouse for fast stock lookups
- Movement date for historical queries
- Status fields for filtering
- Foreign keys for data integrity

## Integration Points

### Sales Module
- **Automatic stock reservation**: When order is confirmed
- **Automatic stock issue**: When order is shipped/delivered
- **Stock availability check**: Before order confirmation

### Purchase Module
- **Automatic goods receipt**: When goods are received
- **Return processing**: Process vendor returns
- **Stock update**: Update quantities on receipt

### Accounting Module (Future)
- **Inventory valuation entries**: Post to general ledger
- **COGS calculation**: Calculate cost of goods sold
- **Variance accounting**: Account for stock count variances

## API Endpoints

### Warehouses
- `GET /api/v1/inventory/warehouses` - List all warehouses
- `POST /api/v1/inventory/warehouses` - Create warehouse
- `GET /api/v1/inventory/warehouses/{id}` - Get warehouse details
- `PUT /api/v1/inventory/warehouses/{id}` - Update warehouse
- `DELETE /api/v1/inventory/warehouses/{id}` - Delete warehouse
- `POST /api/v1/inventory/warehouses/{id}/activate` - Activate warehouse
- `POST /api/v1/inventory/warehouses/{id}/deactivate` - Deactivate warehouse

### Stock Items
- `GET /api/v1/inventory/stock-items` - List stock items
- `GET /api/v1/inventory/stock-items/{id}` - Get stock item details
- `GET /api/v1/inventory/stock-items/by-product/{productId}` - Get stock by product
- `GET /api/v1/inventory/stock-items/low-stock` - Get low stock items
- `GET /api/v1/inventory/stock-items/valuation` - Get stock valuation report

### Stock Movements
- `GET /api/v1/inventory/movements` - List all movements
- `POST /api/v1/inventory/movements/receive` - Record receipt
- `POST /api/v1/inventory/movements/issue` - Issue stock
- `POST /api/v1/inventory/movements/transfer` - Transfer stock
- `POST /api/v1/inventory/movements/adjust` - Adjust stock
- `GET /api/v1/inventory/movements/{id}` - Get movement details
- `POST /api/v1/inventory/movements/{id}/approve` - Approve adjustment

### Stock Counts
- `GET /api/v1/inventory/stock-counts` - List all counts
- `POST /api/v1/inventory/stock-counts` - Create count
- `GET /api/v1/inventory/stock-counts/{id}` - Get count details
- `PUT /api/v1/inventory/stock-counts/{id}` - Update count
- `POST /api/v1/inventory/stock-counts/{id}/start` - Start count
- `POST /api/v1/inventory/stock-counts/{id}/complete` - Complete count
- `POST /api/v1/inventory/stock-counts/{id}/reconcile` - Reconcile count

## Configuration

All configuration is managed via environment variables and `config/inventory.php`:

```env
# Code Prefixes
INVENTORY_WAREHOUSE_CODE_PREFIX=WH-
INVENTORY_STOCK_MOVEMENT_CODE_PREFIX=SM-
INVENTORY_STOCK_COUNT_CODE_PREFIX=CNT-

# Valuation
INVENTORY_VALUATION_METHOD=FIFO

# Stock Management
INVENTORY_ENABLE_NEGATIVE_STOCK=false
INVENTORY_AUTO_RESERVE_STOCK=true
INVENTORY_ENABLE_REORDER_ALERTS=true

# Tracking
INVENTORY_ENABLE_BATCH_TRACKING=true
INVENTORY_ENABLE_SERIAL_TRACKING=true

# Stock Counts
INVENTORY_COUNT_TOLERANCE_PERCENT=2.0
INVENTORY_AUTO_ADJUST_ON_COUNT=false

# Integration
INVENTORY_AUTO_RECEIVE_FROM_PURCHASE=false
INVENTORY_AUTO_ISSUE_FOR_SALES=false
INVENTORY_SYNC_WITH_ACCOUNTING=true

# Audit
INVENTORY_AUDIT_ENABLED=true
INVENTORY_AUDIT_ASYNC=true
```

## Security

- **Policy-based authorization**: All operations protected by policies
- **Tenant isolation**: Automatic tenant scoping on all queries
- **Approval workflows**: Adjustments above threshold require approval
- **Audit logging**: Complete audit trail of all stock movements

## Performance

- **Indexed queries**: Optimized for fast stock lookups
- **Queue-based processing**: Heavy operations queued for background processing
- **Cached stock levels**: Real-time stock cache for quick access
- **Efficient valuation**: Optimized cost calculation algorithms

## Testing

Comprehensive test coverage including:
- Unit tests for valuation methods
- Feature tests for API endpoints
- Integration tests with Sales/Purchase modules
- Concurrency tests for stock movements

## Future Enhancements

- **Barcode scanning**: Mobile app with barcode scanner
- **Advanced analytics**: Inventory turnover, aging, ABC analysis
- **Demand forecasting**: AI-powered demand prediction
- **Automated replenishment**: Auto-generate purchase requisitions
- **Multi-unit support**: Support different units for same product
- **Consignment inventory**: Track consignment stock
- **Quality control**: Inspection workflows for receipts

## Dependencies

- **Core Module**: Base infrastructure
- **Tenant Module**: Multi-tenancy support
- **Product Module**: Product catalog
- **Auth Module**: Authentication and authorization
- **Audit Module**: Audit logging

## License

MIT License - See LICENSE file for details
