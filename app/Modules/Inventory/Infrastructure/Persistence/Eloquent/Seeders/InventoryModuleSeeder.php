<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class InventoryModuleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            if (! Schema::hasTable('stock_levels') || ! Schema::hasTable('items')) {
                return;
            }

            $tenantId = (int) DB::table('tenants')->where('code', 'AUTOERP')->value('id');
            $tenantId = $tenantId > 0 ? $tenantId : (int) DB::table('tenants')->value('id');
            if ($tenantId < 1) {
                return;
            }

            $organizationUnitId = (int) DB::table('organization_units')
                ->where('tenant_id', $tenantId)
                ->where('code', 'MAIN')
                ->value('id');
            $organizationUnitId = $organizationUnitId > 0 ? $organizationUnitId : null;

            $userId = (int) DB::table('users')->where('email', 'admin@example.com')->value('id');
            $userId = $userId > 0 ? $userId : (int) DB::table('users')->value('id');
            $userId = $userId > 0 ? $userId : null;

            $mainWarehouseId = $this->warehouse($tenantId, $organizationUnitId, 'MAIN', 'Main Warehouse', true);
            $serviceWarehouseId = $this->warehouse($tenantId, $organizationUnitId, 'SERVICE', 'Service Store', false);
            $mainLocationId = $this->location($tenantId, $organizationUnitId, $mainWarehouseId, 'MAIN-BIN', 'Main Bin');
            $serviceLocationId = $this->location($tenantId, $organizationUnitId, $serviceWarehouseId, 'SERVICE-BIN', 'Service Bin');

            $item = DB::table('items')
                ->where('tenant_id', $tenantId)
                ->where('sku', 'ITM-FILTER-001')
                ->first()
                ?? DB::table('items')
                    ->where('tenant_id', $tenantId)
                    ->where('is_stockable', true)
                    ->first();

            if ($item === null) {
                return;
            }

            $itemId = (int) $item->id;
            $uomId = (int) ($item->base_uom_id ?? 0);
            if ($uomId < 1) {
                return;
            }

            $batchId = $this->batch($tenantId, $organizationUnitId, $itemId);
            $serialId = $this->serial($tenantId, $organizationUnitId, $itemId, $batchId, $mainLocationId);

            $this->stockLevel($tenantId, $organizationUnitId, $itemId, $uomId, $mainWarehouseId, $mainLocationId, $batchId, null, 50, 5);
            $this->stockLevel($tenantId, $organizationUnitId, $itemId, $uomId, $serviceWarehouseId, $serviceLocationId, null, null, 12, 1);
            $this->movement($tenantId, $organizationUnitId, $itemId, $uomId, $mainWarehouseId, $mainLocationId, $batchId, $userId);
            $this->reservation($tenantId, $organizationUnitId, $itemId, $uomId, $mainWarehouseId, $mainLocationId, $batchId, $userId);
            $this->costLayer($tenantId, $organizationUnitId, $itemId, $mainWarehouseId, $mainLocationId, $batchId);
            $this->transfer($tenantId, $organizationUnitId, $itemId, $uomId, $mainWarehouseId, $serviceWarehouseId, $mainLocationId, $serviceLocationId, $userId);
            $this->adjustment($tenantId, $organizationUnitId, $itemId, $uomId, $mainWarehouseId, $mainLocationId, $userId);
            $this->trace($tenantId, $organizationUnitId, $itemId, $mainWarehouseId, $serviceWarehouseId, $mainLocationId, $serviceLocationId, $userId);
        });
    }

    private function warehouse(int $tenantId, ?int $organizationUnitId, string $code, string $name, bool $isDefault): int
    {
        DB::table('warehouses')->updateOrInsert(
            ['tenant_id' => $tenantId, 'name' => $name],
            [
                'code' => $code,
                'is_active' => true,
                'is_default' => $isDefault,
                'metadata' => json_encode(['seed_source' => 'inventory_module']),
                'organization_unit_id' => $organizationUnitId,
                'row_version' => 1,
                'type' => 'standard',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('warehouses')->where('tenant_id', $tenantId)->where('name', $name)->value('id');
    }

    private function location(int $tenantId, ?int $organizationUnitId, int $warehouseId, string $code, string $name): int
    {
        DB::table('warehouse_locations')->updateOrInsert(
            ['tenant_id' => $tenantId, 'warehouse_id' => $warehouseId, 'name' => $name],
            [
                'code' => $code,
                'depth' => 0,
                'is_active' => true,
                'is_pickable' => true,
                'is_receivable' => true,
                'metadata' => json_encode(['seed_source' => 'inventory_module']),
                'organization_unit_id' => $organizationUnitId,
                'path' => $code,
                'row_version' => 1,
                'type' => 'bin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('warehouse_locations')
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->where('name', $name)
            ->value('id');
    }

    private function batch(int $tenantId, ?int $organizationUnitId, int $itemId): int
    {
        DB::table('batches')->updateOrInsert(
            ['tenant_id' => $tenantId, 'item_id' => $itemId, 'batch_number' => 'BATCH-SEED-001'],
            [
                'expiry_date' => now()->addYear()->toDateString(),
                'metadata' => json_encode(['seed_source' => 'inventory_module']),
                'organization_unit_id' => $organizationUnitId,
                'received_date' => now()->toDateString(),
                'row_version' => 1,
                'status' => 'active',
                'unit_cost' => 25,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('batches')->where('tenant_id', $tenantId)->where('batch_number', 'BATCH-SEED-001')->value('id');
    }

    private function serial(int $tenantId, ?int $organizationUnitId, int $itemId, int $batchId, int $locationId): int
    {
        DB::table('serials')->updateOrInsert(
            ['tenant_id' => $tenantId, 'serial_number' => 'SER-SEED-001'],
            [
                'batch_id' => $batchId,
                'current_location_id' => $locationId,
                'item_id' => $itemId,
                'metadata' => json_encode(['seed_source' => 'inventory_module']),
                'organization_unit_id' => $organizationUnitId,
                'row_version' => 1,
                'status' => 'AVAILABLE',
                'unit_cost' => 25,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return (int) DB::table('serials')->where('tenant_id', $tenantId)->where('serial_number', 'SER-SEED-001')->value('id');
    }

    private function stockLevel(int $tenantId, ?int $organizationUnitId, int $itemId, int $uomId, int $warehouseId, int $locationId, ?int $batchId, ?int $serialId, float $onHand, float $reserved): void
    {
        DB::table('stock_levels')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
                'location_id' => $locationId,
                'batch_id' => $batchId,
                'serial_id' => $serialId,
                'condition' => 'good',
            ],
            [
                'base_uom_id' => $uomId,
                'last_movement_at' => now(),
                'metadata' => json_encode(['seed_source' => 'inventory_module']),
                'organization_unit_id' => $organizationUnitId,
                'quantity_blocked' => 0,
                'quantity_damaged' => 0,
                'quantity_in_transit' => 0,
                'quantity_on_hand' => $onHand,
                'quantity_reserved' => $reserved,
                'row_version' => 1,
                'unit_cost' => 25,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function movement(int $tenantId, ?int $organizationUnitId, int $itemId, int $uomId, int $warehouseId, int $locationId, int $batchId, ?int $userId): void
    {
        DB::table('stock_movements')->updateOrInsert(
            ['tenant_id' => $tenantId, 'source_type' => 'inventory_seed', 'source_id' => 1, 'movement_type' => 'OPENING_BALANCE'],
            [
                'approved_by' => $userId,
                'base_quantity' => 50,
                'base_quantity_in' => 50,
                'base_quantity_out' => 0,
                'base_uom_id' => $uomId,
                'balance_quantity' => 50,
                'balance_value' => 1250,
                'batch_id' => $batchId,
                'direction' => 'IN',
                'item_id' => $itemId,
                'location_id' => $locationId,
                'metadata' => json_encode(['item_name' => 'Seeded stock item', 'warehouse_name' => 'Main Warehouse', 'location_name' => 'Main Bin']),
                'organization_unit_id' => $organizationUnitId,
                'performed_at' => now(),
                'performed_by' => $userId,
                'quantity' => 50,
                'quantity_in' => 50,
                'quantity_out' => 0,
                'row_version' => 1,
                'source_module' => 'inventory',
                'source_reference' => 'INV-SEED-OPENING',
                'status' => 'POSTED',
                'total_cost' => 1250,
                'transaction_uom_id' => $uomId,
                'unit_cost' => 25,
                'warehouse_id' => $warehouseId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function reservation(int $tenantId, ?int $organizationUnitId, int $itemId, int $uomId, int $warehouseId, int $locationId, int $batchId, ?int $userId): void
    {
        DB::table('stock_reservations')->updateOrInsert(
            ['tenant_id' => $tenantId, 'reserved_for_type' => 'inventory_seed', 'reserved_for_id' => 1],
            [
                'base_quantity' => 5,
                'base_uom_id' => $uomId,
                'batch_id' => $batchId,
                'expires_at' => now()->addDays(7),
                'item_id' => $itemId,
                'location_id' => $locationId,
                'metadata' => json_encode(['seed_source' => 'inventory_module', 'source_reference' => 'INV-SEED-RES']),
                'organization_unit_id' => $organizationUnitId,
                'quantity' => 5,
                'reserved_by' => $userId,
                'row_version' => 1,
                'status' => 'ACTIVE',
                'transaction_uom_id' => $uomId,
                'warehouse_id' => $warehouseId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function costLayer(int $tenantId, ?int $organizationUnitId, int $itemId, int $warehouseId, int $locationId, int $batchId): void
    {
        DB::table('inventory_cost_layers')->updateOrInsert(
            ['tenant_id' => $tenantId, 'reference_type' => 'inventory_seed', 'reference_id' => 1],
            [
                'batch_id' => $batchId,
                'is_closed' => false,
                'item_id' => $itemId,
                'layer_date' => now()->toDateString(),
                'location_id' => $locationId,
                'metadata' => json_encode(['seed_source' => 'inventory_module', 'source_reference' => 'INV-SEED-OPENING']),
                'organization_unit_id' => $organizationUnitId,
                'quantity_in' => 50,
                'quantity_remaining' => 45,
                'row_version' => 1,
                'unit_cost' => 25,
                'valuation_method' => 'weighted_average',
                'warehouse_id' => $warehouseId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function transfer(int $tenantId, ?int $organizationUnitId, int $itemId, int $uomId, int $fromWarehouseId, int $toWarehouseId, int $fromLocationId, int $toLocationId, ?int $userId): void
    {
        DB::table('stock_transfers')->updateOrInsert(
            ['tenant_id' => $tenantId, 'reference_number' => 'TRF-SEED-001'],
            [
                'from_location_id' => $fromLocationId,
                'from_warehouse_id' => $fromWarehouseId,
                'metadata' => json_encode(['seed_source' => 'inventory_module']),
                'notes' => 'Seeded transfer draft for UI verification.',
                'organization_unit_id' => $organizationUnitId,
                'requested_by' => $userId,
                'row_version' => 1,
                'status' => 'DRAFT',
                'to_location_id' => $toLocationId,
                'to_warehouse_id' => $toWarehouseId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $transferId = (int) DB::table('stock_transfers')->where('tenant_id', $tenantId)->where('reference_number', 'TRF-SEED-001')->value('id');

        DB::table('stock_transfer_lines')->updateOrInsert(
            ['tenant_id' => $tenantId, 'stock_transfer_id' => $transferId, 'item_id' => $itemId],
            [
                'base_quantity' => 2,
                'from_location_id' => $fromLocationId,
                'metadata' => json_encode(['seed_source' => 'inventory_module']),
                'organization_unit_id' => $organizationUnitId,
                'quantity' => 2,
                'row_version' => 1,
                'to_location_id' => $toLocationId,
                'unit_cost' => 25,
                'uom_id' => $uomId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function adjustment(int $tenantId, ?int $organizationUnitId, int $itemId, int $uomId, int $warehouseId, int $locationId, ?int $userId): void
    {
        DB::table('stock_adjustments')->updateOrInsert(
            ['tenant_id' => $tenantId, 'reference_number' => 'ADJ-SEED-001'],
            [
                'counted_by' => $userId,
                'location_id' => $locationId,
                'metadata' => json_encode(['seed_source' => 'inventory_module']),
                'organization_unit_id' => $organizationUnitId,
                'reason' => 'Seeded adjustment draft for UI verification.',
                'row_version' => 1,
                'status' => 'DRAFT',
                'type' => 'cycle_count',
                'warehouse_id' => $warehouseId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $adjustmentId = (int) DB::table('stock_adjustments')->where('tenant_id', $tenantId)->where('reference_number', 'ADJ-SEED-001')->value('id');

        DB::table('stock_adjustment_lines')->updateOrInsert(
            ['tenant_id' => $tenantId, 'stock_adjustment_id' => $adjustmentId, 'item_id' => $itemId],
            [
                'adjustment_quantity' => 1,
                'base_adjustment_quantity' => 1,
                'base_current_quantity' => 50,
                'base_resulting_quantity' => 51,
                'base_uom_id' => $uomId,
                'current_quantity' => 50,
                'direction' => 'INCREASE',
                'location_id' => $locationId,
                'metadata' => json_encode(['seed_source' => 'inventory_module']),
                'organization_unit_id' => $organizationUnitId,
                'reason_code' => 'FOUND_STOCK',
                'resulting_quantity' => 51,
                'row_version' => 1,
                'transaction_uom_id' => $uomId,
                'unit_cost' => 25,
                'warehouse_id' => $warehouseId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function trace(int $tenantId, ?int $organizationUnitId, int $itemId, int $sourceWarehouseId, int $destinationWarehouseId, int $sourceLocationId, int $destinationLocationId, ?int $userId): void
    {
        DB::table('trace_logs')->updateOrInsert(
            ['tenant_id' => $tenantId, 'entity_type' => 'item', 'entity_id' => $itemId, 'reference_type' => 'inventory_seed', 'reference_id' => 1],
            [
                'action_type' => 'receive',
                'destination_location_id' => $destinationLocationId,
                'destination_warehouse_id' => $destinationWarehouseId,
                'metadata' => json_encode(['description' => 'Seeded stock trace entry', 'seed_source' => 'inventory_module']),
                'organization_unit_id' => $organizationUnitId,
                'performed_at' => now(),
                'performed_by' => $userId,
                'quantity' => 2,
                'row_version' => 1,
                'source_location_id' => $sourceLocationId,
                'source_warehouse_id' => $sourceWarehouseId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
}
