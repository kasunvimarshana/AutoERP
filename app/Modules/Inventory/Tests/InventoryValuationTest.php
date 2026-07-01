<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests;

use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Inventory\Models\InventoryValuationConsumption;
use Modules\Inventory\Models\InventoryValuationLayer;
use Modules\Inventory\Services\StockMovementService;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\TrackingType;

final class InventoryValuationTest extends InventoryTestCase
{
    public function test_fifo_layers_are_consumed_and_weighted_average_recalculates(): void
    {
        [$tenantId, $warehouseId, $fifoItem] = $this->stockContext();
        $this->receipt($tenantId, $warehouseId, $fifoItem, '10.000000', '5.000000');
        $this->receipt($tenantId, $warehouseId, $fifoItem, '5.000000', '8.000000');

        $this->withTenantExecutionContext($tenantId, fn () => app(StockMovementService::class)->record(new StockMovementData($tenantId, '2026-06-06', InventoryMovementType::Issue, InventoryDirection::Out, (int) $fifoItem->getKey(), $warehouseId, '12.000000')));

        $layers = $this->withTenantExecutionContext($tenantId, fn () => InventoryValuationLayer::query()->where('item_id', $fifoItem->getKey())->orderBy('id')->get());
        $this->assertSame('0.000000', (string) $layers[0]->remaining_quantity);
        $this->assertSame('3.000000', (string) $layers[1]->remaining_quantity);

        $weightedItem = $this->createItem($tenantId, 'WA-ITEM', TrackingType::None, CostingMethod::WeightedAverage);
        $this->receipt($tenantId, $warehouseId, $weightedItem, '10.000000', '5.000000');
        $this->receipt($tenantId, $warehouseId, $weightedItem, '10.000000', '15.000000');

        $balance = $this->withTenantExecutionContext($tenantId, fn () => InventoryStockBalance::query()->where('item_id', $weightedItem->getKey())->firstOrFail());
        $this->assertSame('10.000000', (string) $balance->average_cost);
    }

    public function test_fifo_issue_audits_each_consumed_layer_and_reversal_restores_exact_cost(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $this->receipt($tenantId, $warehouseId, $item, '10.000000', '5.000000');
        $this->receipt($tenantId, $warehouseId, $item, '5.000000', '8.000000');

        $issue = $this->withTenantExecutionContext($tenantId, fn () => app(StockMovementService::class)->record(new StockMovementData(
            $tenantId,
            '2026-06-06',
            InventoryMovementType::Issue,
            InventoryDirection::Out,
            (int) $item->getKey(),
            $warehouseId,
            '12.000000',
        )));

        $this->assertSame('5.500000', (string) $issue->unit_cost);
        $this->assertSame('66.000000', (string) $issue->total_cost);
        $consumptions = $this->withTenantExecutionContext($tenantId, fn () => InventoryValuationConsumption::query()
            ->where('issue_movement_id', $issue->getKey())
            ->orderBy('id')
            ->get());
        $this->assertCount(2, $consumptions);
        $this->assertSame('10.000000', (string) $consumptions[0]->quantity_consumed);
        $this->assertSame('50.000000', (string) $consumptions[0]->total_cost);
        $this->assertSame('2.000000', (string) $consumptions[1]->quantity_consumed);
        $this->assertSame('16.000000', (string) $consumptions[1]->total_cost);

        $reversal = $this->withTenantExecutionContext($tenantId, fn () => app(StockMovementService::class)->reverse($issue));
        $balance = $this->withTenantExecutionContext($tenantId, fn () => InventoryStockBalance::query()->where('item_id', $item->getKey())->firstOrFail());
        $this->assertSame('15.000000', (string) $balance->quantity_on_hand);
        $this->assertSame('90.000000', (string) $balance->total_value);
        $this->assertSame('66.000000', (string) $reversal->total_cost);
        $this->assertSame(2, $this->withTenantExecutionContext($tenantId, fn () => InventoryValuationConsumption::query()
            ->where('issue_movement_id', $issue->getKey())
            ->where('reversed_by_movement_id', $reversal->getKey())
            ->count()));
    }

    public function test_weighted_average_standard_and_manual_cost_methods_reconcile(): void
    {
        $tenantId = $this->createTenant();
        $warehouseId = $this->createWarehouse($tenantId, 'WH-COST');

        $weighted = $this->createItem($tenantId, 'WA-COST', costing: CostingMethod::WeightedAverage);
        $this->receipt($tenantId, $warehouseId, $weighted, '10.000000', '5.000000');
        $this->receipt($tenantId, $warehouseId, $weighted, '10.000000', '15.000000');
        $weightedIssue = $this->withTenantExecutionContext($tenantId, fn () => app(StockMovementService::class)->record(new StockMovementData(
            $tenantId,
            '2026-06-06',
            InventoryMovementType::Issue,
            InventoryDirection::Out,
            (int) $weighted->getKey(),
            $warehouseId,
            '4.000000',
        )));
        $this->assertSame('10.000000', (string) $weightedIssue->unit_cost);
        $this->assertSame('40.000000', (string) $weightedIssue->total_cost);
        $weightedLayer = $this->withTenantExecutionContext($tenantId, fn () => InventoryValuationLayer::query()->where('item_id', $weighted->getKey())->firstOrFail());
        $this->assertSame('16.000000', (string) $weightedLayer->remaining_quantity);
        $this->assertSame('160.000000', (string) $weightedLayer->remaining_value);

        $standard = $this->createItem($tenantId, 'STD-COST', costing: CostingMethod::Standard);
        $this->withTenantExecutionContext($tenantId, function () use ($standard): void {
            $standard->metadata = ['inventory' => ['standard_cost' => '7.250000']];
            $standard->save();
        });
        $standardReceipt = $this->receipt($tenantId, $warehouseId, $standard, '3.000000', '99.000000');
        $this->assertSame('7.250000', (string) $standardReceipt->unit_cost);
        $this->assertSame('21.750000', (string) $standardReceipt->total_cost);

        $manual = $this->createItem($tenantId, 'MAN-COST', costing: CostingMethod::Manual);
        $this->receipt($tenantId, $warehouseId, $manual, '3.000000', '2.125000');
        $manualIssue = $this->withTenantExecutionContext($tenantId, fn () => app(StockMovementService::class)->record(new StockMovementData(
            $tenantId,
            '2026-06-06',
            InventoryMovementType::Issue,
            InventoryDirection::Out,
            (int) $manual->getKey(),
            $warehouseId,
            '2.000000',
        )));
        $this->assertSame('4.250000', (string) $manualIssue->total_cost);
    }
}
