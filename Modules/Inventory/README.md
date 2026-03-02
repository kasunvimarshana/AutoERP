# Inventory Module

Manages stock levels using an immutable double-entry-style ledger, warehouse management and stock adjustments.

## Responsibilities

- Immutable stock ledger (append-only, no soft deletes)
- Warehouse management
- Stock adjustments (add/remove with reason)
- Warehouse-to-warehouse stock transfers
- Pessimistic locking to prevent race conditions
- BCMath for all quantity calculations

## Architecture

```
Inventory/
├── Domain/
│   ├── ValueObjects/Quantity.php      — BCMath quantity VO
│   ├── Enums/LedgerEntryType.php      — IN/OUT/ADJUSTMENT/TRANSFER/SALE/etc.
│   ├── Entities/StockLedgerEntry.php  — Immutable ledger entry
│   ├── Entities/Warehouse.php
│   ├── Entities/StockAdjustment.php
│   └── Contracts/InventoryRepositoryInterface.php
├── Application/
│   ├── Commands/AdjustStockCommand.php
│   ├── Commands/TransferStockCommand.php
│   ├── Handlers/AdjustStockHandler.php   — Pessimistic locking + BCMath
│   └── Handlers/TransferStockHandler.php
├── Infrastructure/
│   ├── Models/StockLedgerEntry.php     — No softDeletes (immutable)
│   ├── Models/Warehouse.php
│   ├── Models/StockAdjustment.php
│   ├── Repositories/InventoryRepository.php
│   └── Database/Migrations/
└── Interfaces/Http/Controllers/InventoryController.php
```

## API Endpoints

| Method | Endpoint                                  | Description              |
|--------|-------------------------------------------|--------------------------|
| GET    | /api/v1/inventory/stock/{prod}/{wh}       | Get current stock level  |
| POST   | /api/v1/inventory/adjust                  | Adjust stock             |
| POST   | /api/v1/inventory/transfer                | Transfer between WH      |
| GET    | /api/v1/inventory/history                 | Get stock history        |

## Concurrency Safety

- `getStockLevelForUpdate()` uses `SELECT ... FOR UPDATE` to acquire a row-level lock
- All adjustments and transfers run inside a DB transaction
- Stock never goes negative for outbound operations
