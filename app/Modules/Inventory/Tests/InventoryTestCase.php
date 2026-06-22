<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Services\StockMovementService;
use Modules\Item\DTOs\CreateItemData;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Models\Item;
use Modules\Item\Services\ItemCreationService;
use Tests\TestCase;

abstract class InventoryTestCase extends TestCase
{
    use RefreshDatabase;

    protected function stockContext(): array
    {
        $tenantId = $this->createTenant();
        $warehouseId = $this->createWarehouse($tenantId, 'WH-'.Str::upper(Str::random(4)));
        $item = $this->createItem($tenantId, 'ITEM-'.Str::upper(Str::random(4)));

        return [$tenantId, $warehouseId, $item];
    }

    protected function receipt(
        int $tenantId,
        int $warehouseId,
        Item $item,
        string $quantity,
        string $unitCost,
        ?int $batchId = null,
        ?int $serialId = null,
    ): InventoryMovement {
        return app(StockMovementService::class)->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-06',
            movementType: InventoryMovementType::Receipt,
            direction: InventoryDirection::In,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: $quantity,
            batchId: $batchId,
            serialNumberId: $serialId,
            unitCost: $unitCost,
        ));
    }

    protected function createItem(
        int $tenantId,
        string $code,
        TrackingType $tracking = TrackingType::None,
        CostingMethod $costing = CostingMethod::Fifo,
        ItemType $type = ItemType::Stock,
        bool $stockable = true,
    ): Item {
        return app(ItemCreationService::class)->create(new CreateItemData(
            tenantId: $tenantId,
            code: $code,
            name: 'Inventory '.$code,
            itemType: $type,
            trackingType: $tracking,
            costingMethod: $costing,
            isStockable: $stockable,
        ));
    }

    protected function createTenant(string $suffix = ''): int
    {
        $suffix = $suffix !== '' ? $suffix : Str::upper(Str::random(4));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-INV-'.$suffix,
            'name' => 'Inventory Tenant '.$suffix,
            'slug' => 'inventory-tenant-'.Str::lower($suffix),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()]);
    }

    protected function createWarehouse(int $tenantId, string $code): int
    {
        return (int) DB::table('warehouses')->insertGetId([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'name' => 'Warehouse '.$code,
            'code' => $code,
            'type' => 'standard',
            'is_active' => true,
            'is_default' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createWarehouseLocation(int $tenantId, int $warehouseId, string $code): int
    {
        return (int) DB::table('warehouse_locations')->insertGetId([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouseId,
            'name' => 'Location '.$code,
            'code' => $code,
            'type' => 'bin',
            'is_active' => true,
            'is_pickable' => true,
            'is_receivable' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
