<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests;

use Modules\Inventory\DTOs\CostAdjustmentData;
use Modules\Inventory\DTOs\CostAdjustmentLineData;
use Modules\Inventory\DTOs\StockAdjustmentData;
use Modules\Inventory\DTOs\StockAdjustmentLineData;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\DTOs\StockCountData;
use Modules\Inventory\DTOs\StockCountLineData;
use Modules\Inventory\DTOs\StockTransferData;
use Modules\Inventory\DTOs\StockTransferLineData;
use Modules\Inventory\Enums\AdjustmentStatus;
use Modules\Inventory\Enums\AdjustmentType;
use Modules\Inventory\Enums\CostAdjustmentStatus;
use Modules\Inventory\Enums\InventoryStockState;
use Modules\Inventory\Enums\StockCountStatus;
use Modules\Inventory\Enums\TransferStatus;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Inventory\Models\InventoryValuationLayer;
use Modules\Inventory\Services\InventoryCostAdjustmentService;
use Modules\Inventory\Services\InventoryStockCountService;
use Modules\Inventory\Services\StockAdjustmentService;
use Modules\Inventory\Services\StockAvailabilityService;
use Modules\Inventory\Services\StockTransferService;

final class InventoryWorkflowTest extends InventoryTestCase
{
    public function test_opening_balance_adjustment_posts_stock(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $adjustment = app(StockAdjustmentService::class)->create(new StockAdjustmentData(
            tenantId: $tenantId,
            adjustmentDate: '2026-06-06',
            adjustmentType: AdjustmentType::OpeningBalance,
            warehouseId: $warehouseId,
            lines: [
                new StockAdjustmentLineData((int) $item->getKey(), '0.000000', '15.000000', '15.000000', '8.000000'),
            ],
        ));

        app(StockAdjustmentService::class)->post($adjustment);

        $this->assertSame('15.000000', app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $warehouseId))->quantityOnHand);
        $this->assertSame(AdjustmentStatus::Posted, $adjustment->refresh()->status);

        app(StockAdjustmentService::class)->reverse($adjustment);
        $this->assertSame('0.000000', app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $warehouseId))->quantityOnHand);
        $this->assertSame(AdjustmentStatus::Reversed, $adjustment->refresh()->status);
    }

    public function test_transfer_dispatch_receive_and_reverse_tracks_in_transit_stock(): void
    {
        [$tenantId, $fromWarehouseId, $item] = $this->stockContext();
        $toWarehouseId = $this->createWarehouse($tenantId, 'WH-TO');
        $this->receipt($tenantId, $fromWarehouseId, $item, '10.000000', '4.000000');

        $transfer = app(StockTransferService::class)->create(new StockTransferData(
            tenantId: $tenantId,
            transferDate: '2026-06-06',
            fromWarehouseId: $fromWarehouseId,
            toWarehouseId: $toWarehouseId,
            lines: [
                new StockTransferLineData((int) $item->getKey(), '3.000000', '4.000000'),
            ],
        ));

        app(StockTransferService::class)->post($transfer);

        $this->assertSame(TransferStatus::Dispatched, $transfer->refresh()->status);
        $this->assertSame('7.000000', app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $fromWarehouseId))->quantityOnHand);
        $toAvailability = app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $toWarehouseId));
        $this->assertSame('0.000000', $toAvailability->quantityOnHand);
        $this->assertSame('3.000000', $toAvailability->quantityInTransit);
        $this->assertSame('3.000000', (string) InventoryStockBalance::query()
            ->where('item_id', $item->getKey())
            ->where('warehouse_id', $toWarehouseId)
            ->value('quantity_in_transit'));
        $this->assertDatabaseHas('inventory_stock_state_changes', [
            'item_id' => $item->getKey(),
            'from_state' => InventoryStockState::Available->value,
            'to_state' => InventoryStockState::InTransit->value,
            'source_type' => 'inventory_transfer',
        ]);

        app(StockTransferService::class)->receive($transfer);

        $this->assertSame(TransferStatus::Received, $transfer->refresh()->status);
        $toAvailability = app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $toWarehouseId));
        $this->assertSame('3.000000', $toAvailability->quantityOnHand);
        $this->assertSame('0.000000', $toAvailability->quantityInTransit);
        $this->assertSame('0.000000', (string) InventoryStockBalance::query()
            ->where('item_id', $item->getKey())
            ->where('warehouse_id', $toWarehouseId)
            ->value('quantity_in_transit'));
        $this->assertDatabaseHas('inventory_stock_state_changes', [
            'item_id' => $item->getKey(),
            'from_state' => InventoryStockState::InTransit->value,
            'to_state' => InventoryStockState::Available->value,
            'source_type' => 'inventory_transfer',
        ]);

        app(StockTransferService::class)->reverse($transfer);
        $this->assertSame(TransferStatus::Reversed, $transfer->refresh()->status);
        $this->assertSame('10.000000', app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $fromWarehouseId))->quantityOnHand);
        $this->assertSame('0.000000', app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $toWarehouseId))->quantityOnHand);
    }

    public function test_cost_adjustment_updates_open_valuation_layer_without_receiving_again(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $this->receipt($tenantId, $warehouseId, $item, '10.000000', '5.000000');
        $layer = InventoryValuationLayer::query()->where('item_id', $item->getKey())->firstOrFail();

        $adjustment = app(InventoryCostAdjustmentService::class)->create(new CostAdjustmentData(
            tenantId: $tenantId,
            adjustmentDate: '2026-06-06',
            lines: [
                new CostAdjustmentLineData((int) $layer->getKey(), '20.000000', 'Freight'),
            ],
        ));

        app(InventoryCostAdjustmentService::class)->post($adjustment);

        $this->assertSame(CostAdjustmentStatus::Posted, $adjustment->refresh()->status);
        $layer->refresh();
        $this->assertSame('70.000000', (string) $layer->remaining_value);
        $this->assertSame('7.000000', (string) $layer->unit_cost);
        $balance = InventoryStockBalance::query()->where('item_id', $item->getKey())->firstOrFail();
        $this->assertSame('70.000000', (string) $balance->total_value);
        $this->assertSame('7.000000', (string) $balance->average_cost);
    }

    public function test_stock_count_detects_variance_and_posts_recount_adjustment(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $this->receipt($tenantId, $warehouseId, $item, '10.000000', '5.000000');

        $count = app(InventoryStockCountService::class)->create(new StockCountData(
            tenantId: $tenantId,
            countDate: '2026-06-06',
            warehouseId: $warehouseId,
            lines: [
                new StockCountLineData((int) $item->getKey(), '8.000000'),
            ],
        ));

        $this->assertSame('10.000000', (string) $count->lines[0]->system_quantity);
        $this->assertSame('-2.000000', (string) $count->lines[0]->variance_quantity);

        app(InventoryStockCountService::class)->approve($count);
        $this->assertSame(StockCountStatus::Approved, $count->refresh()->status);

        app(InventoryStockCountService::class)->post($count);

        $this->assertSame(StockCountStatus::Posted, $count->refresh()->status);
        $this->assertNotNull($count->inventory_adjustment_id);
        $availability = app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $warehouseId));
        $this->assertSame('8.000000', $availability->quantityOnHand);
        $this->assertSame('8.000000', $availability->quantityAvailable);
    }
}
