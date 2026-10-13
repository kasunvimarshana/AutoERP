<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests;

use Carbon\CarbonImmutable;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Http\Requests\InventoryLookupRequest;
use Modules\Inventory\Services\HistoricalStockBalanceService;
use Modules\Inventory\Services\StockMovementService;

final class HistoricalStockBalanceTest extends InventoryTestCase
{
    public function test_history_reconciles_receipts_issues_and_reversals_at_each_cutoff_and_excludes_other_tenants(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        [$otherTenant, $otherWarehouse, $otherItem] = $this->stockContext();
        $this->travelTo(CarbonImmutable::parse('2026-06-06 10:00:00'));
        $this->receipt($tenantId, $warehouseId, $item, '10.000000', '2.000000');
        $this->receipt($otherTenant, $otherWarehouse, $otherItem, '99.000000', '2.000000');
        $this->travelTo(CarbonImmutable::parse('2026-06-07 10:00:00'));
        $movement = $this->withTenantExecutionContext($tenantId, fn () => app(StockMovementService::class)->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-07',
            movementType: InventoryMovementType::Issue,
            direction: InventoryDirection::Out,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: '4.000000',
        )));
        $this->travelTo(CarbonImmutable::parse('2026-06-08 10:00:00'));
        $this->withTenantExecutionContext($tenantId, fn () => app(StockMovementService::class)->reverse($movement));

        $request = InventoryLookupRequest::create('/', 'GET');
        $request->attributes->set('current_tenant_id', $tenantId);
        foreach (['2026-06-06' => '10.000000', '2026-06-07' => '6.000000', '2026-06-08' => '10.000000'] as $date => $expected) {
            [$page, $summary] = app(HistoricalStockBalanceService::class)->page($request, CarbonImmutable::parse($date)->endOfDay());
            $this->assertSame(1, $page->total());
            $this->assertSame($item->getKey(), (int) $page->items()[0]->item_id);
            $this->assertSame($expected, (string) $page->items()[0]->quantity_available);
            $this->assertSame($expected, (string) $page->items()[0]->quantity_on_hand);
            $this->assertSame(1, $summary['in_stock']);
        }
    }
}
