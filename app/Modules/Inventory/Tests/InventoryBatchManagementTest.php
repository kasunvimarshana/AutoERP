<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Inventory\DTOs\BatchPriceData;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Services\BatchPriceService;
use Modules\Inventory\Services\BatchTrackingService;
use Modules\Inventory\Services\SellableBatchLookupService;
use Modules\Inventory\Services\StockMovementService;
use Modules\Item\Enums\ItemPriceType;
use Modules\Item\Enums\TrackingType;
use Tests\Support\CurrencyFixture;

final class InventoryBatchManagementTest extends InventoryTestCase
{
    public function test_only_positive_stock_batches_are_returned_as_service_options(): void
    {
        $tenantId = $this->createTenant();
        $warehouseId = $this->createWarehouse($tenantId, 'WH-BATCH-OPTIONS');
        $item = $this->createItem($tenantId, 'BATCH-OPTIONS', TrackingType::Batch);
        $currencyId = CurrencyFixture::create(['code' => 'BPO', 'name' => 'Batch Price Option Currency', 'symbol' => 'BPO']);
        $uomId = (int) DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => 'BPO-PCS',
            'name' => 'Batch Price Pieces',
            'symbol' => 'pcs',
            'type' => 'unit',
            'category' => 'quantity',
            'decimal_precision' => 6,
            'allow_fractional_quantity' => true,
            'is_base' => true,
            'is_active' => true,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('tenants')->where('id', $tenantId)->update(['base_currency_id' => $currencyId]);
        DB::table('items')->where('id', $item->getKey())->update(['base_uom_id' => $uomId]);
        DB::table('item_units')->insert([
            'tenant_id' => $tenantId,
            'item_id' => $item->getKey(),
            'uom_id' => $uomId,
            'unit_role' => 'base',
            'conversion_factor' => '1.000000',
            'is_default' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $item->refresh();
        $batches = app(BatchTrackingService::class);
        $oldBatch = $this->withTenantExecutionContext($tenantId, fn () => $batches->create($tenantId, (int) $item->getKey(), 'OLD-PRICE'));
        $newBatch = $this->withTenantExecutionContext($tenantId, fn () => $batches->create($tenantId, (int) $item->getKey(), 'NEW-PRICE'));
        $prices = app(BatchPriceService::class);
        $this->withTenantExecutionContext($tenantId, fn () => $prices->create($tenantId, new BatchPriceData(
            batchId: (int) $oldBatch->getKey(),
            priceType: ItemPriceType::Service,
            amount: '10.000000',
            currencyId: $currencyId,
            uomId: $uomId,
            organizationUnitId: null,
            effectiveFrom: '2026-01-01',
        )));
        $this->withTenantExecutionContext($tenantId, fn () => $prices->create($tenantId, new BatchPriceData(
            batchId: (int) $newBatch->getKey(),
            priceType: ItemPriceType::Service,
            amount: '20.000000',
            currencyId: $currencyId,
            uomId: $uomId,
            organizationUnitId: null,
            effectiveFrom: '2026-01-01',
        )));

        $this->receipt($tenantId, $warehouseId, $item, '2.000000', '5.000000', (int) $oldBatch->getKey());
        $this->receipt($tenantId, $warehouseId, $item, '3.000000', '7.000000', (int) $newBatch->getKey());

        $serviceOptions = app(SellableBatchLookupService::class);
        $initial = $this->withTenantExecutionContext($tenantId, fn () => $serviceOptions->paginate(
            tenantId: $tenantId,
            organizationUnitId: null,
            search: '',
            perPage: 20,
            warehouseId: $warehouseId,
        ));
        $this->assertEqualsCanonicalizing([(int) $oldBatch->getKey(), (int) $newBatch->getKey()], $initial->getCollection()->modelKeys());
        $this->assertSame('10.000000', $initial->getCollection()->firstWhere('id', $oldBatch->getKey())?->resolved_service_unit_price);
        $this->assertSame('20.000000', $initial->getCollection()->firstWhere('id', $newBatch->getKey())?->resolved_service_unit_price);

        $this->withTenantExecutionContext($tenantId, fn () => app(StockMovementService::class)->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-07',
            movementType: InventoryMovementType::Issue,
            direction: InventoryDirection::Out,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: '2.000000',
            batchId: (int) $oldBatch->getKey(),
        )));

        $result = $this->withTenantExecutionContext($tenantId, fn () => $serviceOptions->paginate(
            tenantId: $tenantId,
            organizationUnitId: null,
            search: '',
            perPage: 20,
            warehouseId: $warehouseId,
        ));

        $this->assertSame([(int) $newBatch->getKey()], $result->getCollection()->modelKeys());
        $this->assertSame(3.0, (float) $result->getCollection()->first()?->available_stock_quantity);
    }

    public function test_non_tracked_items_cannot_own_batches(): void
    {
        $tenantId = $this->createTenant();
        $item = $this->createItem($tenantId, 'NO-BATCH', TrackingType::None);

        $this->expectException(ValidationException::class);
        $this->withTenantExecutionContext($tenantId, fn () => app(BatchTrackingService::class)->create(
            $tenantId,
            (int) $item->getKey(),
            'INVALID-BATCH',
        ));
    }
}
