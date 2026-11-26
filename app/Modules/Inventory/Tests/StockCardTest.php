<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Inventory\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Services\StockCardService;
use Modules\Inventory\Services\StockMovementService;
use Modules\Item\Services\ItemBaseUomConversionService;

class StockCardTest extends InventoryTestCase
{
    public function test_stock_card_aggregates_locations_and_keeps_opening_outside_period_totals(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $firstLocation = $this->createWarehouseLocation($tenantId, $warehouseId, 'CARD-A');
        $secondLocation = $this->createWarehouseLocation($tenantId, $warehouseId, 'CARD-B');

        $this->travelTo(CarbonImmutable::parse('2026-05-31 09:00:00 UTC'));
        $this->receipt($tenantId, $warehouseId, $item, '11.000000', '2.000000', warehouseLocationId: $firstLocation);

        $this->travelTo(CarbonImmutable::parse('2026-06-10 09:00:00 UTC'));
        $this->receipt($tenantId, $warehouseId, $item, '4.000000', '2.000000', warehouseLocationId: $secondLocation);

        $this->travelTo(CarbonImmutable::parse('2026-06-11 09:00:00 UTC'));
        $this->withTenantExecutionContext($tenantId, fn () => app(StockMovementService::class)->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-06',
            movementType: InventoryMovementType::Issue,
            direction: InventoryDirection::Out,
            itemId: (int) $item->id,
            warehouseId: $warehouseId,
            quantity: '5.000000',
            warehouseLocationId: $firstLocation,
        )));

        $filters = [
            'item_id' => (int) $item->id,
            'warehouse_id' => $warehouseId,
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-30',
            'timezone' => 'UTC',
        ];
        $card = $this->withTenantExecutionContext($tenantId, fn () => app(StockCardService::class)->build($filters, $tenantId, null));

        $this->assertSame('11.000000', $card['opening']);
        $this->assertSame('4.000000', $card['total_in']);
        $this->assertSame('5.000000', $card['total_out']);
        $this->assertSame('10.000000', $card['closing']);
        $this->assertCount(2, $card['rows']);
        $this->assertSame('2026-06-06', $card['rows'][0]['document_date']);
        $this->assertSame('6.000000', $card['rows'][0]['balance']);

        $firstLocationCard = $this->withTenantExecutionContext($tenantId, fn () => app(StockCardService::class)->build([
            ...$filters,
            'warehouse_location_id' => $firstLocation,
        ], $tenantId, null));
        $this->assertSame('11.000000', $firstLocationCard['opening']);
        $this->assertSame('6.000000', $firstLocationCard['closing']);
        $this->assertCount(1, $firstLocationCard['rows']);
    }

    public function test_stock_card_includes_both_sides_of_a_reversal(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $this->travelTo(CarbonImmutable::parse('2026-06-10 09:00:00 UTC'));
        $movement = $this->receipt($tenantId, $warehouseId, $item, '3.000000', '2.000000');
        $this->travelTo(CarbonImmutable::parse('2026-06-11 09:00:00 UTC'));
        $this->withTenantExecutionContext($tenantId, fn () => app(StockMovementService::class)->reverse($movement));

        $card = $this->withTenantExecutionContext($tenantId, fn () => app(StockCardService::class)->build([
            'item_id' => (int) $item->id,
            'warehouse_id' => $warehouseId,
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-30',
            'timezone' => 'UTC',
        ], $tenantId, null));

        $this->assertCount(2, $card['rows']);
        $this->assertSame('3.000000', $card['total_in']);
        $this->assertSame('3.000000', $card['total_out']);
        $this->assertSame('0.000000', $card['closing']);
    }

    public function test_grn_document_is_displayed_by_number_with_a_detail_link(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $this->travelTo(CarbonImmutable::parse('2026-06-10 09:00:00 UTC'));
        $grnId = (int) DB::table('goods_receipt_notes')->insertGetId([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouseId,
            'grn_number' => 'GRN-CARD-001',
            'received_date' => '2026-06-10',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->withTenantExecutionContext($tenantId, fn () => app(StockMovementService::class)->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-10',
            movementType: InventoryMovementType::Receipt,
            direction: InventoryDirection::In,
            itemId: (int) $item->id,
            warehouseId: $warehouseId,
            quantity: '4.000000',
            unitCost: '2.000000',
            sourceType: 'goods_receipt_note',
            sourceId: $grnId,
        )));

        $card = $this->withTenantExecutionContext($tenantId, fn () => app(StockCardService::class)->build([
            'item_id' => (int) $item->id,
            'warehouse_id' => $warehouseId,
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-30',
            'timezone' => 'UTC',
        ], $tenantId, null));

        $this->assertSame('GRN-CARD-001', $card['rows'][0]['document']['label']);
        $this->assertSame('/purchase/goods-receipts/'.$grnId, $card['rows'][0]['document']['url']);
    }

    public function test_historical_opening_is_expressed_in_the_current_base_unit(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $this->travelTo(CarbonImmutable::parse('2026-06-01 09:00:00 UTC'));
        $oldUnitId = (int) DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => 'CARD-PCS',
            'name' => 'Card Piece',
            'symbol' => 'pc',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('items')->where('id', $item->id)->update(['base_uom_id' => $oldUnitId]);
        $item->refresh();
        $this->receipt($tenantId, $warehouseId, $item, '10.000000', '2.000000');
        $newUnitId = (int) DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => 'CARD-BOX',
            'name' => 'Card Box',
            'symbol' => 'box',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->travelTo(CarbonImmutable::parse('2026-06-10 09:00:00 UTC'));
        $this->withTenantExecutionContext($tenantId, fn () => app(ItemBaseUomConversionService::class)->apply(
            $item->fresh(),
            $newUnitId,
            '0.100000',
            null,
            'Pack size change',
            null,
        ));

        $this->travelTo(CarbonImmutable::parse('2026-06-11 09:00:00 UTC'));
        $card = $this->withTenantExecutionContext($tenantId, fn () => app(StockCardService::class)->build([
            'item_id' => (int) $item->id,
            'warehouse_id' => $warehouseId,
            'date_from' => '2026-06-11',
            'date_to' => '2026-06-30',
            'timezone' => 'UTC',
        ], $tenantId, null));

        $this->assertSame('1.000000', $card['opening']);
        $this->assertSame('1.000000', $card['closing']);
        $this->assertSame('Card Box', $card['item']['uom']);
    }

    public function test_stock_card_resolves_workspace_timezone_when_filters_omit_timezone(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();

        $this->travelTo(CarbonImmutable::parse('2026-06-10 09:00:00 UTC'));
        $this->receipt($tenantId, $warehouseId, $item, '5.000000', '2.000000');

        $card = $this->withTenantExecutionContext($tenantId, fn () => app(StockCardService::class)->build([
            'item_id' => (int) $item->id,
            'warehouse_id' => $warehouseId,
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-30',
        ], $tenantId, null));

        $this->assertSame('5.000000', $card['closing']);
    }

    public function test_backdated_movement_with_uom_revision_expresses_in_current_base_unit(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $oldUnitId = (int) DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => 'BD-PCS-'.Str::upper(Str::random(3)),
            'name' => 'Backdate Piece',
            'symbol' => 'pc',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('items')->where('id', $item->id)->update(['base_uom_id' => $oldUnitId]);
        $item->refresh();

        // 1. UOM revision applied on 2026-06-10 (conversion factor 0.1, 10 pcs = 1 box)
        $newUnitId = (int) DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => 'BD-BOX-'.Str::upper(Str::random(3)),
            'name' => 'Backdate Box',
            'symbol' => 'box',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->travelTo(CarbonImmutable::parse('2026-06-10 09:00:00 UTC'));
        $this->withTenantExecutionContext($tenantId, fn () => app(ItemBaseUomConversionService::class)->apply(
            $item->fresh(),
            $newUnitId,
            '0.100000',
            null,
            'Pack size change',
            null,
        ));

        // 2. On physical day 2026-06-20, record a receipt with BACKDATED movement_date = 2026-06-01
        $this->travelTo(CarbonImmutable::parse('2026-06-20 14:00:00 UTC'));
        $movements = app(StockMovementService::class);
        $movement = $this->withTenantExecutionContext($tenantId, fn () => $movements->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-01',
            movementType: InventoryMovementType::Receipt,
            direction: InventoryDirection::In,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: '20.000000',
            unitCost: '1.000000',
        )));
        DB::table('inventory_movements')->where('id', $movement->getKey())->update([
            'quantity' => '20.000000',
            'base_uom_id' => $oldUnitId,
        ]);

        // 3. Stock card on June 20 must correctly convert the 20 pieces from June 1st into 2 boxes (20 * 0.1 = 2)
        $card = $this->withTenantExecutionContext($tenantId, fn () => app(StockCardService::class)->build([
            'item_id' => (int) $item->id,
            'warehouse_id' => $warehouseId,
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-30',
            'timezone' => 'UTC',
        ], $tenantId, null));

        $this->assertSame('2.000000', $card['closing']);
        $this->assertSame('Backdate Box', $card['item']['uom']);
    }

    public function test_backdated_movement_with_multi_tier_cascading_uom_revisions(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();

        // Base unit: Piece
        $pieceUnitId = (int) DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => 'TIER-PC-'.Str::upper(Str::random(3)),
            'name' => 'Tier Piece',
            'symbol' => 'pc',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('items')->where('id', $item->id)->update(['base_uom_id' => $pieceUnitId]);
        $item->refresh();

        // 1. On 2026-06-05, movement of 100 pieces
        $this->travelTo(CarbonImmutable::parse('2026-06-05 09:00:00 UTC'));
        $movements = app(StockMovementService::class);
        $this->withTenantExecutionContext($tenantId, fn () => $movements->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-05',
            movementType: InventoryMovementType::Receipt,
            direction: InventoryDirection::In,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: '100.000000',
            unitCost: '1.000000',
        )));

        // 2. On 2026-06-10, revision 1: Piece -> Box (factor 0.1, 10 pcs = 1 box)
        $boxUnitId = (int) DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => 'TIER-BOX-'.Str::upper(Str::random(3)),
            'name' => 'Tier Box',
            'symbol' => 'box',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->travelTo(CarbonImmutable::parse('2026-06-10 09:00:00 UTC'));
        $this->withTenantExecutionContext($tenantId, fn () => app(ItemBaseUomConversionService::class)->apply(
            $item->fresh(),
            $boxUnitId,
            '0.100000',
            null,
            'First revision: Piece to Box',
            null,
        ));

        // 3. On 2026-06-15, movement of 5 boxes
        $this->travelTo(CarbonImmutable::parse('2026-06-15 09:00:00 UTC'));
        $this->withTenantExecutionContext($tenantId, fn () => $movements->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-15',
            movementType: InventoryMovementType::Receipt,
            direction: InventoryDirection::In,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: '5.000000',
            unitCost: '10.000000',
        )));

        // 4. On 2026-06-20, revision 2: Box -> Crate (factor 0.2, 5 boxes = 1 crate)
        $crateUnitId = (int) DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => 'TIER-CRATE-'.Str::upper(Str::random(3)),
            'name' => 'Tier Crate',
            'symbol' => 'crate',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->travelTo(CarbonImmutable::parse('2026-06-20 09:00:00 UTC'));
        $this->withTenantExecutionContext($tenantId, fn () => app(ItemBaseUomConversionService::class)->apply(
            $item->fresh(),
            $crateUnitId,
            '0.200000',
            null,
            'Second revision: Box to Crate',
            null,
        ));

        // 5. On physical date 2026-06-25, backdated receipt of 50 pieces on 2026-06-02
        $this->travelTo(CarbonImmutable::parse('2026-06-25 09:00:00 UTC'));
        $backdatedMov = $this->withTenantExecutionContext($tenantId, fn () => $movements->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-02',
            movementType: InventoryMovementType::Receipt,
            direction: InventoryDirection::In,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: '50.000000',
            unitCost: '1.000000',
        )));
        DB::table('inventory_movements')->where('id', $backdatedMov->getKey())->update([
            'quantity' => '50.000000',
            'base_uom_id' => $pieceUnitId,
        ]);

        // 6. Build stock card
        // Calculation:
        // Movement 1 (June 5): 100 pcs * 0.1 = 10 boxes * 0.2 = 2 crates
        // Movement 2 (June 15): 5 boxes * 0.2 = 1 crate
        // Movement 3 (June 2 backdated): 50 pcs * 0.1 = 5 boxes * 0.2 = 1 crate
        // Total = 4 crates
        $card = $this->withTenantExecutionContext($tenantId, fn () => app(StockCardService::class)->build([
            'item_id' => (int) $item->id,
            'warehouse_id' => $warehouseId,
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-30',
            'timezone' => 'UTC',
        ], $tenantId, null));

        $this->assertSame('4.000000', $card['closing']);
        $this->assertSame('Tier Crate', $card['item']['uom']);
    }
}
