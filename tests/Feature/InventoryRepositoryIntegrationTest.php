<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Domain\Entities\CycleCountHeader;
use Modules\Inventory\Domain\Entities\CycleCountLine;
use Modules\Inventory\Domain\Entities\InventoryCostLayer;
use Modules\Inventory\Domain\Entities\StockMovement;
use Modules\Inventory\Domain\Entities\StockReservation;
use Modules\Inventory\Domain\Entities\TransferOrder;
use Modules\Inventory\Domain\Entities\TransferOrderLine;
use Modules\Inventory\Domain\Entities\ValuationConfig;
use Modules\Inventory\Domain\RepositoryInterfaces\CostLayerRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\CycleCountRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryStockRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\StockReservationRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\TraceLogRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\TransferOrderRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\ValuationConfigRepositoryInterface;
use Tests\TestCase;

class InventoryRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 1;
    private int $tenant2Id = 2;
    private int $userId = 101;
    private int $user2Id = 102;

    private int $uomId = 2001;
    private int $uom2Id = 2002;
    private int $productId = 1001;
    private int $product2Id = 1002;

    private int $warehouseId = 3001;
    private int $warehouse2Id = 3002;
    private int $locationId = 4001;
    private int $location2Id = 4002;
    private int $locationOtherTenantId = 4003;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    public function test_valuation_config_create_resolve_update_paginate_and_delete(): void
    {
        /** @var ValuationConfigRepositoryInterface $repository */
        $repository = app(ValuationConfigRepositoryInterface::class);

        $tenantLevel = $repository->create(new ValuationConfig(
            tenantId: $this->tenantId,
            orgUnitId: null,
            warehouseId: null,
            productId: null,
            transactionType: null,
            valuationMethod: 'fifo',
            allocationStrategy: 'fifo',
            isActive: true,
            metadata: ['scope' => 'tenant'],
        ));

        $warehouseLevel = $repository->create(new ValuationConfig(
            tenantId: $this->tenantId,
            orgUnitId: null,
            warehouseId: $this->warehouseId,
            productId: null,
            transactionType: 'receipt',
            valuationMethod: 'weighted_average',
            allocationStrategy: 'nearest_bin',
            isActive: true,
            metadata: ['scope' => 'warehouse'],
        ));

        $productLevel = $repository->create(new ValuationConfig(
            tenantId: $this->tenantId,
            orgUnitId: null,
            warehouseId: $this->warehouseId,
            productId: $this->productId,
            transactionType: 'receipt',
            valuationMethod: 'fefo',
            allocationStrategy: 'fefo',
            isActive: true,
            metadata: ['scope' => 'product'],
        ));

        $found = $repository->findById($this->tenantId, $tenantLevel->getId());
        $effective = $repository->resolveEffective(
            tenantId: $this->tenantId,
            productId: $this->productId,
            warehouseId: $this->warehouseId,
            orgUnitId: null,
            transactionType: 'receipt',
        );

        $updatedWarehouseLevel = new ValuationConfig(
            tenantId: $this->tenantId,
            orgUnitId: null,
            warehouseId: $this->warehouseId,
            productId: null,
            transactionType: 'receipt',
            valuationMethod: 'weighted_average',
            allocationStrategy: 'manual',
            isActive: true,
            metadata: ['scope' => 'warehouse', 'updated' => true],
            id: $warehouseLevel->getId(),
        );
        $repository->update($updatedWarehouseLevel);

        $page = $repository->paginate($this->tenantId, 10, 1);

        $this->assertNotNull($found);
        $this->assertNotNull($effective);
        $this->assertSame($tenantLevel->getId(), $found->getId());
        $this->assertSame($productLevel->getId(), $effective->getId());
        $this->assertGreaterThanOrEqual(3, $page->total());

        $repository->delete($this->tenantId, $tenantLevel->getId());
        $this->assertNull($repository->findById($this->tenantId, $tenantLevel->getId()));
    }

    public function test_cost_layer_create_update_and_query_orders(): void
    {
        /** @var CostLayerRepositoryInterface $repository */
        $repository = app(CostLayerRepositoryInterface::class);

        $batch1Id = $this->insertBatch($this->tenantId, $this->productId, 'BATCH-OLD', '2026-06-15');
        $batch2Id = $this->insertBatch($this->tenantId, $this->productId, 'BATCH-NEW', '2026-05-20');

        $layerOld = $repository->create(new InventoryCostLayer(
            tenantId: $this->tenantId,
            productId: $this->productId,
            variantId: null,
            batchId: $batch1Id,
            locationId: $this->locationId,
            valuationMethod: 'fifo',
            layerDate: '2026-01-10',
            quantityIn: '10.000000',
            quantityRemaining: '10.000000',
            unitCost: '12.500000',
            referenceType: null,
            referenceId: null,
            isClosed: false,
        ));

        $layerNew = $repository->create(new InventoryCostLayer(
            tenantId: $this->tenantId,
            productId: $this->productId,
            variantId: null,
            batchId: $batch2Id,
            locationId: $this->locationId,
            valuationMethod: 'fifo',
            layerDate: '2026-02-10',
            quantityIn: '5.000000',
            quantityRemaining: '5.000000',
            unitCost: '13.000000',
            referenceType: null,
            referenceId: null,
            isClosed: false,
        ));

        $layerOld->setQuantityRemaining('6.000000');
        $layerOld->setUnitCost('12.750000');
        $repository->update($layerOld);

        $found = $repository->findById($this->tenantId, $layerOld->getId());
        $oldest = $repository->findOpenLayersOldestFirst($this->tenantId, $this->productId, $this->locationId);
        $newest = $repository->findOpenLayersNewestFirst($this->tenantId, $this->productId, $this->locationId);
        $expiry = $repository->findOpenLayersByExpiryAsc($this->tenantId, $this->productId, $this->locationId);
        $allOpen = $repository->findAllOpenLayers($this->tenantId, $this->productId, $this->locationId);

        $this->assertNotNull($found);
        $this->assertSame('6.000000', $found->getQuantityRemaining());
        $this->assertCount(2, $allOpen);
        $this->assertSame($layerOld->getId(), $oldest[0]->getId());
        $this->assertSame($layerNew->getId(), $newest[0]->getId());
        $this->assertSame($layerNew->getId(), $expiry[0]->getId());
    }

    public function test_inventory_stock_record_adjust_and_query_helpers(): void
    {
        /** @var InventoryStockRepositoryInterface $repository */
        $repository = app(InventoryStockRepositoryInterface::class);

        $this->insertStockLevel(
            tenantId: $this->tenantId,
            productId: $this->productId,
            locationId: $this->locationId,
            uomId: $this->uomId,
            quantityOnHand: '10.000000',
            quantityReserved: '0.000000',
        );

        $movement = $repository->recordMovement(new StockMovement(
            tenantId: $this->tenantId,
            productId: $this->productId,
            variantId: null,
            batchId: null,
            serialId: null,
            fromLocationId: $this->locationId,
            toLocationId: $this->location2Id,
            movementType: 'transfer',
            referenceType: 'transfer_order',
            referenceId: 9001,
            uomId: $this->uomId,
            quantity: '3.000000',
            unitCost: '10.000000',
            performedBy: $this->userId,
            performedAt: new \DateTimeImmutable('2026-03-01 10:00:00'),
            notes: 'Internal transfer',
            metadata: ['source' => 'test'],
        ));

        $repository->adjustStockLevel($movement);

        $fromQty = DB::table('stock_levels')
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $this->productId)
            ->where('location_id', $this->locationId)
            ->value('quantity_on_hand');

        $toQty = DB::table('stock_levels')
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $this->productId)
            ->where('location_id', $this->location2Id)
            ->value('quantity_on_hand');

        $movementsPage = $repository->paginateByWarehouse(
            tenantId: $this->tenantId,
            warehouseId: $this->warehouseId,
            filters: ['movement_type' => 'transfer'],
            perPage: 10,
            page: 1,
            sort: 'id:asc',
        );

        $stockLevelsPage = $repository->paginateStockLevelsByWarehouse($this->tenantId, $this->warehouseId, 10, 1);

        $this->assertSame(7.0, (float) $fromQty);
        $this->assertSame(3.0, (float) $toQty);
        $this->assertGreaterThanOrEqual(1, $movementsPage->total());
        $this->assertGreaterThanOrEqual(2, $stockLevelsPage->total());
        $this->assertTrue($repository->warehouseExists($this->tenantId, $this->warehouseId));
        $this->assertFalse($repository->warehouseExists($this->tenant2Id, $this->warehouseId));
        $this->assertTrue($repository->locationBelongsToWarehouse($this->tenantId, $this->warehouseId, $this->locationId));
        $this->assertFalse($repository->locationBelongsToWarehouse($this->tenant2Id, $this->warehouseId, $this->locationId));
    }

    public function test_stock_reservation_create_release_delete_expired_and_find(): void
    {
        /** @var StockReservationRepositoryInterface $repository */
        $repository = app(StockReservationRepositoryInterface::class);

        $this->insertStockLevel(
            tenantId: $this->tenantId,
            productId: $this->productId,
            locationId: $this->locationId,
            uomId: $this->uomId,
            quantityOnHand: '20.000000',
            quantityReserved: '0.000000',
        );

        $reservation = $repository->create(new StockReservation(
            tenantId: $this->tenantId,
            productId: $this->productId,
            variantId: null,
            batchId: null,
            serialId: null,
            locationId: $this->locationId,
            quantity: '5.000000',
            reservedForType: 'sales_order',
            reservedForId: 7001,
            expiresAt: '2030-01-01 00:00:00',
        ));

        $found = $repository->findById($this->tenantId, $reservation->getId());
        $page = $repository->paginate($this->tenantId, 10, 1);

        $reservedAfterCreate = DB::table('stock_levels')
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $this->productId)
            ->where('location_id', $this->locationId)
            ->value('quantity_reserved');

        $released = $repository->releaseByReference($this->tenantId, 'sales_order', 7001);

        $reservedAfterRelease = DB::table('stock_levels')
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $this->productId)
            ->where('location_id', $this->locationId)
            ->value('quantity_reserved');

        $expiredReservation = $repository->create(new StockReservation(
            tenantId: $this->tenantId,
            productId: $this->productId,
            variantId: null,
            batchId: null,
            serialId: null,
            locationId: $this->locationId,
            quantity: '2.000000',
            reservedForType: 'sales_order',
            reservedForId: 7002,
            expiresAt: '2020-01-01 00:00:00',
        ));

        $reservedBeforeDeleteExpired = DB::table('stock_levels')
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $this->productId)
            ->where('location_id', $this->locationId)
            ->value('quantity_reserved');

        $deletedExpiredCount = $repository->deleteExpired($this->tenantId, '2021-01-01 00:00:00');

        $reservedAfterDeleteExpired = DB::table('stock_levels')
            ->where('tenant_id', $this->tenantId)
            ->where('product_id', $this->productId)
            ->where('location_id', $this->locationId)
            ->value('quantity_reserved');

        $this->assertNotNull($found);
        $this->assertSame($reservation->getId(), $found->getId());
        $this->assertGreaterThanOrEqual(1, $page->total());
        $this->assertSame(5.0, (float) $reservedAfterCreate);
        $this->assertSame(1, $released);
        $this->assertSame(0.0, (float) $reservedAfterRelease);
        $this->assertSame(2.0, (float) $reservedBeforeDeleteExpired);
        $this->assertSame(1, $deletedExpiredCount);
        $this->assertSame(0.0, (float) $reservedAfterDeleteExpired);
        $this->assertNull($repository->findById($this->tenantId, $expiredReservation->getId()));
    }

    public function test_transfer_order_create_approve_receive_and_paginate(): void
    {
        /** @var TransferOrderRepositoryInterface $repository */
        $repository = app(TransferOrderRepositoryInterface::class);

        $transferOrder = $repository->create(new TransferOrder(
            tenantId: $this->tenantId,
            fromWarehouseId: $this->warehouseId,
            toWarehouseId: $this->warehouse2Id,
            transferNumber: 'TO-0001',
            status: 'draft',
            requestDate: '2026-03-10',
            expectedDate: '2026-03-12',
            shippedDate: null,
            receivedDate: null,
            notes: 'Rebalance stock',
            metadata: ['priority' => 'high'],
            lines: [
                new TransferOrderLine(
                    tenantId: $this->tenantId,
                    productId: $this->productId,
                    variantId: null,
                    batchId: null,
                    serialId: null,
                    fromLocationId: $this->locationId,
                    toLocationId: $this->location2Id,
                    uomId: $this->uomId,
                    requestedQty: '10.000000',
                    shippedQty: '0.000000',
                    receivedQty: '0.000000',
                    unitCost: '10.000000',
                ),
            ],
            orgUnitId: null,
        ));

        $approved = $repository->markAsApproved($this->tenantId, $transferOrder->getId());

        $received = $repository->markAsReceived(
            tenantId: $this->tenantId,
            transferOrderId: $transferOrder->getId(),
            receivedLines: [
                ['line_id' => $transferOrder->getLines()[0]->getId(), 'received_qty' => '8.000000'],
            ],
            receivedDate: '2026-03-13',
        );

        $found = $repository->findById($this->tenantId, $transferOrder->getId());
        $page = $repository->paginate($this->tenantId, 10, 1);

        $this->assertNotNull($approved);
        $this->assertSame('approved', $approved->getStatus());
        $this->assertNotNull($received);
        $this->assertSame('received', $received->getStatus());
        $this->assertSame('8.000000', $received->getLines()[0]->getReceivedQty());
        $this->assertSame('8.000000', $received->getLines()[0]->getShippedQty());
        $this->assertNotNull($found);
        $this->assertGreaterThanOrEqual(1, $page->total());
    }

    public function test_cycle_count_create_mark_in_progress_complete_and_paginate(): void
    {
        /** @var CycleCountRepositoryInterface $repository */
        $repository = app(CycleCountRepositoryInterface::class);

        $created = $repository->create(new CycleCountHeader(
            tenantId: $this->tenantId,
            warehouseId: $this->warehouseId,
            locationId: $this->locationId,
            status: 'draft',
            countedByUserId: $this->userId,
            countedAt: null,
            approvedByUserId: null,
            approvedAt: null,
            lines: [
                new CycleCountLine(
                    tenantId: $this->tenantId,
                    productId: $this->productId,
                    variantId: null,
                    batchId: null,
                    serialId: null,
                    systemQty: '10.000000',
                    countedQty: '10.000000',
                    varianceQty: '0.000000',
                    unitCost: '10.000000',
                    varianceValue: '0.000000',
                    adjustmentMovementId: null,
                ),
            ],
        ));

        $inProgress = $repository->markInProgress($this->tenantId, $created->getId());

        $completed = $repository->complete(
            tenantId: $this->tenantId,
            countId: $created->getId(),
            lineUpdates: [
                ['line_id' => $created->getLines()[0]->getId(), 'counted_qty' => '8.000000', 'adjustment_movement_id' => null],
            ],
            approvedByUserId: $this->userId,
        );

        $found = $repository->findById($this->tenantId, $created->getId());
        $page = $repository->paginate($this->tenantId, 10, 1);

        $this->assertNotNull($inProgress);
        $this->assertSame('in_progress', $inProgress->getStatus());
        $this->assertNotNull($completed);
        $this->assertSame('completed', $completed->getStatus());
        $this->assertSame('8.000000', $completed->getLines()[0]->getCountedQty());
        $this->assertSame('-2.000000', $completed->getLines()[0]->getVarianceQty());
        $this->assertNotNull($found);
        $this->assertGreaterThanOrEqual(1, $page->total());
    }

    public function test_trace_log_record_for_movement(): void
    {
        /** @var TraceLogRepositoryInterface $repository */
        $repository = app(TraceLogRepositoryInterface::class);

        $movement = new StockMovement(
            tenantId: $this->tenantId,
            productId: $this->productId,
            variantId: null,
            batchId: null,
            serialId: null,
            fromLocationId: $this->locationId,
            toLocationId: $this->location2Id,
            movementType: 'transfer',
            referenceType: 'transfer_order',
            referenceId: 9002,
            uomId: $this->uomId,
            quantity: '4.000000',
            unitCost: '10.000000',
            performedBy: $this->userId,
            performedAt: new \DateTimeImmutable('2026-03-20 08:00:00'),
            notes: 'trace test',
            metadata: ['device_id' => 'SCN-01', 'source' => 'integration-test'],
        );

        $repository->recordForMovement($movement);

        $this->assertDatabaseHas('trace_logs', [
            'tenant_id' => $this->tenantId,
            'entity_type' => 'product',
            'entity_id' => $this->productId,
            'action_type' => 'transfer',
            'reference_type' => 'transfer_order',
            'reference_id' => 9002,
            'source_location_id' => $this->locationId,
            'destination_location_id' => $this->location2Id,
            'performed_by' => $this->userId,
            'device_id' => 'SCN-01',
        ]);
    }

    private function seedReferenceData(): void
    {
        foreach ([$this->tenantId, $this->tenant2Id] as $tenantId) {
            DB::table('tenants')->insert([
                'id' => $tenantId,
                'name' => 'Tenant '.$tenantId,
                'slug' => 'tenant-'.$tenantId,
                'domain' => null,
                'logo_path' => null,
                'database_config' => null,
                'mail_config' => null,
                'cache_config' => null,
                'queue_config' => null,
                'feature_flags' => null,
                'api_keys' => null,
                'settings' => null,
                'plan' => 'free',
                'tenant_plan_id' => null,
                'status' => 'active',
                'active' => true,
                'trial_ends_at' => null,
                'subscription_ends_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);
        }

        DB::table('users')->insert([
            [
                'id' => $this->userId,
                'tenant_id' => $this->tenantId,
                'org_unit_id' => null,
                'first_name' => 'User',
                'last_name' => 'One',
                'email' => 'inventory-user-1@example.com',
                'email_verified_at' => null,
                'password' => bcrypt('password'),
                'phone' => null,
                'avatar' => null,
                'status' => 'active',
                'preferences' => null,
                'address' => null,
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => $this->user2Id,
                'tenant_id' => $this->tenant2Id,
                'org_unit_id' => null,
                'first_name' => 'User',
                'last_name' => 'Two',
                'email' => 'inventory-user-2@example.com',
                'email_verified_at' => null,
                'password' => bcrypt('password'),
                'phone' => null,
                'avatar' => null,
                'status' => 'active',
                'preferences' => null,
                'address' => null,
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ]);

        DB::table('units_of_measure')->insert([
            [
                'id' => $this->uomId,
                'tenant_id' => $this->tenantId,
                'name' => 'Each',
                'symbol' => 'ea',
                'type' => 'unit',
                'is_base' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => $this->uom2Id,
                'tenant_id' => $this->tenant2Id,
                'name' => 'Each',
                'symbol' => 'ea',
                'type' => 'unit',
                'is_base' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ]);

        DB::table('products')->insert([
            [
                'id' => $this->productId,
                'tenant_id' => $this->tenantId,
                'category_id' => null,
                'brand_id' => null,
                'org_unit_id' => null,
                'type' => 'physical',
                'name' => 'Inventory Product 1',
                'slug' => 'inventory-product-1',
                'sku' => 'INV-001',
                'description' => null,
                'base_uom_id' => $this->uomId,
                'purchase_uom_id' => null,
                'sales_uom_id' => null,
                'tax_group_id' => null,
                'uom_conversion_factor' => '1.0000000000',
                'is_batch_tracked' => false,
                'is_lot_tracked' => false,
                'is_serial_tracked' => false,
                'valuation_method' => 'fifo',
                'standard_cost' => '10.000000',
                'income_account_id' => null,
                'cogs_account_id' => null,
                'inventory_account_id' => null,
                'expense_account_id' => null,
                'is_active' => true,
                'image_path' => null,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => $this->product2Id,
                'tenant_id' => $this->tenant2Id,
                'category_id' => null,
                'brand_id' => null,
                'org_unit_id' => null,
                'type' => 'physical',
                'name' => 'Inventory Product 2',
                'slug' => 'inventory-product-2',
                'sku' => 'INV-002',
                'description' => null,
                'base_uom_id' => $this->uom2Id,
                'purchase_uom_id' => null,
                'sales_uom_id' => null,
                'tax_group_id' => null,
                'uom_conversion_factor' => '1.0000000000',
                'is_batch_tracked' => false,
                'is_lot_tracked' => false,
                'is_serial_tracked' => false,
                'valuation_method' => 'fifo',
                'standard_cost' => '10.000000',
                'income_account_id' => null,
                'cogs_account_id' => null,
                'inventory_account_id' => null,
                'expense_account_id' => null,
                'is_active' => true,
                'image_path' => null,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ]);

        DB::table('warehouses')->insert([
            [
                'id' => $this->warehouseId,
                'tenant_id' => $this->tenantId,
                'org_unit_id' => null,
                'row_version' => 1,
                'name' => 'Main Warehouse',
                'code' => 'WH-001',
                'image_path' => null,
                'type' => 'standard',
                'address_id' => null,
                'is_active' => true,
                'is_default' => true,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $this->warehouse2Id,
                'tenant_id' => $this->tenantId,
                'org_unit_id' => null,
                'row_version' => 1,
                'name' => 'Secondary Warehouse',
                'code' => 'WH-002',
                'image_path' => null,
                'type' => 'standard',
                'address_id' => null,
                'is_active' => true,
                'is_default' => false,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3101,
                'tenant_id' => $this->tenant2Id,
                'org_unit_id' => null,
                'row_version' => 1,
                'name' => 'Tenant2 Warehouse',
                'code' => 'WH-901',
                'image_path' => null,
                'type' => 'standard',
                'address_id' => null,
                'is_active' => true,
                'is_default' => true,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('warehouse_locations')->insert([
            [
                'id' => $this->locationId,
                'tenant_id' => $this->tenantId,
                'org_unit_id' => null,
                'row_version' => 1,
                'warehouse_id' => $this->warehouseId,
                'parent_id' => null,
                'name' => 'A-01',
                'code' => 'A01',
                'path' => 'A01',
                'depth' => 0,
                'type' => 'bin',
                'is_active' => true,
                'is_pickable' => true,
                'is_receivable' => true,
                'capacity' => null,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $this->location2Id,
                'tenant_id' => $this->tenantId,
                'org_unit_id' => null,
                'row_version' => 1,
                'warehouse_id' => $this->warehouseId,
                'parent_id' => null,
                'name' => 'B-01',
                'code' => 'B01',
                'path' => 'B01',
                'depth' => 0,
                'type' => 'bin',
                'is_active' => true,
                'is_pickable' => true,
                'is_receivable' => true,
                'capacity' => null,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $this->locationOtherTenantId,
                'tenant_id' => $this->tenant2Id,
                'org_unit_id' => null,
                'row_version' => 1,
                'warehouse_id' => 3101,
                'parent_id' => null,
                'name' => 'C-01',
                'code' => 'C01',
                'path' => 'C01',
                'depth' => 0,
                'type' => 'bin',
                'is_active' => true,
                'is_pickable' => true,
                'is_receivable' => true,
                'capacity' => null,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function insertStockLevel(
        int $tenantId,
        int $productId,
        int $locationId,
        int $uomId,
        string $quantityOnHand,
        string $quantityReserved,
    ): void {
        DB::table('stock_levels')->insert([
            'tenant_id' => $tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'product_id' => $productId,
            'variant_id' => null,
            'location_id' => $locationId,
            'batch_id' => null,
            'serial_id' => null,
            'uom_id' => $uomId,
            'quantity_on_hand' => $quantityOnHand,
            'quantity_reserved' => $quantityReserved,
            'unit_cost' => '10.000000',
            'last_movement_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertBatch(int $tenantId, int $productId, string $batchNumber, string $expiryDate): int
    {
        return (int) DB::table('batches')->insertGetId([
            'tenant_id' => $tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'product_id' => $productId,
            'variant_id' => null,
            'batch_number' => $batchNumber,
            'lot_number' => null,
            'manufacture_date' => null,
            'expiry_date' => $expiryDate,
            'received_date' => '2026-01-01',
            'supplier_id' => null,
            'status' => 'active',
            'notes' => null,
            'metadata' => null,
            'sales_price' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
