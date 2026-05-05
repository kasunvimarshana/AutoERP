<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Inventory\Application\Contracts\FindStockLevelServiceInterface;
use Modules\Inventory\Application\Contracts\FindStockMovementServiceInterface;
use Modules\Inventory\Application\Contracts\RecordStockMovementServiceInterface;
use Modules\Inventory\Domain\Entities\StockMovement;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\Warehouse\Application\Contracts\CreateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\CreateWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\DeleteWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\DeleteWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\FindWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\FindWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\UpdateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\UpdateWarehouseServiceInterface;
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\Entities\WarehouseLocation;
use Tests\TestCase;

class WarehouseEndpointsAuthenticatedTest extends TestCase
{
    private UserModel $authUser;

    private Warehouse $warehouse;

    private WarehouseLocation $location;

    private StockMovement $movement;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authUser = new UserModel([
            'name' => 'Warehouse Admin',
            'email' => 'warehouse-admin@example.com',
            'password' => 'hashed',
        ]);
        $this->authUser->setAttribute('id', 99);
        $this->authUser->setAttribute('tenant_id', 1);

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $presenceVerifier = $this->createMock(PresenceVerifierInterface::class);
        $presenceVerifier->method('getCount')->willReturnCallback(
            static function (string $collection, string $column): int {
                if (
                    ($collection === 'warehouses' && $column === 'code')
                    || ($collection === 'warehouse_locations' && $column === 'code')
                ) {
                    return 0;
                }

                return 1;
            }
        );
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        $this->warehouse = new Warehouse(
            tenantId: 1,
            name: 'Main Warehouse',
            type: 'standard',
            code: 'WH-001',
            isActive: true,
            isDefault: true,
            id: 1,
        );

        $this->location = new WarehouseLocation(
            tenantId: 1,
            warehouseId: 1,
            name: 'A1-BIN-01',
            type: 'bin',
            code: 'BIN-01',
            path: '/A1/BIN-01',
            depth: 2,
            isActive: true,
            isPickable: true,
            isReceivable: true,
            capacity: '100.000000',
            id: 10,
        );

        $this->movement = new StockMovement(
            tenantId: 1,
            productId: 100,
            variantId: null,
            batchId: null,
            serialId: null,
            fromLocationId: null,
            toLocationId: 10,
            movementType: 'receipt',
            referenceType: 'purchase_receipt',
            referenceId: 200,
            uomId: 1,
            quantity: '5.000000',
            unitCost: '10.000000',
            performedBy: 99,
            performedAt: new \DateTimeImmutable('2026-01-01T10:00:00+00:00'),
            notes: 'Initial stock receipt',
            metadata: null,
            id: 500,
        );

        $findWarehouseService = $this->createMock(FindWarehouseServiceInterface::class);
        $findWarehouseService->method('find')->willReturn($this->warehouse);
        $findWarehouseService->method('list')->willReturn($this->makePaginator([$this->warehouse]));
        $this->app->instance(FindWarehouseServiceInterface::class, $findWarehouseService);

        $createWarehouseService = $this->createMock(CreateWarehouseServiceInterface::class);
        $createWarehouseService->method('execute')->willReturn($this->warehouse);
        $this->app->instance(CreateWarehouseServiceInterface::class, $createWarehouseService);

        $updateWarehouseService = $this->createMock(UpdateWarehouseServiceInterface::class);
        $updateWarehouseService->method('execute')->willReturn($this->warehouse);
        $this->app->instance(UpdateWarehouseServiceInterface::class, $updateWarehouseService);

        $deleteWarehouseService = $this->createMock(DeleteWarehouseServiceInterface::class);
        $this->app->instance(DeleteWarehouseServiceInterface::class, $deleteWarehouseService);

        $findLocationService = $this->createMock(FindWarehouseLocationServiceInterface::class);
        $findLocationService->method('find')->willReturn($this->location);
        $findLocationService->method('list')->willReturn($this->makePaginator([$this->location]));
        $this->app->instance(FindWarehouseLocationServiceInterface::class, $findLocationService);

        $createLocationService = $this->createMock(CreateWarehouseLocationServiceInterface::class);
        $createLocationService->method('execute')->willReturn($this->location);
        $this->app->instance(CreateWarehouseLocationServiceInterface::class, $createLocationService);

        $updateLocationService = $this->createMock(UpdateWarehouseLocationServiceInterface::class);
        $updateLocationService->method('execute')->willReturn($this->location);
        $this->app->instance(UpdateWarehouseLocationServiceInterface::class, $updateLocationService);

        $deleteLocationService = $this->createMock(DeleteWarehouseLocationServiceInterface::class);
        $this->app->instance(DeleteWarehouseLocationServiceInterface::class, $deleteLocationService);

        $recordStockMovementService = $this->createMock(RecordStockMovementServiceInterface::class);
        $recordStockMovementService->method('execute')->willReturn($this->movement);
        $this->app->instance(RecordStockMovementServiceInterface::class, $recordStockMovementService);

        $findStockMovementService = $this->createMock(FindStockMovementServiceInterface::class);
        $findStockMovementService->method('listByWarehouse')->willReturn([
            'data' => [[
                'id' => 500,
                'movement_type' => 'receipt',
                'quantity' => '5.000000',
            ]],
        ]);
        $this->app->instance(FindStockMovementServiceInterface::class, $findStockMovementService);

        $findStockLevelService = $this->createMock(FindStockLevelServiceInterface::class);
        $findStockLevelService->method('listByWarehouse')->willReturn([
            'data' => [[
                'product_id' => 100,
                'available_qty' => '5.000000',
            ]],
        ]);
        $this->app->instance(FindStockLevelServiceInterface::class, $findStockLevelService);
    }

    private function actingAsUser(): static
    {
        return $this->withHeader('X-Tenant-ID', '1')->actingAs(
            $this->authUser,
            (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api'))
        );
    }

    /**
     * @param  array<int, mixed>  $items
     */
    private function makePaginator(array $items): LengthAwarePaginator
    {
        return new LengthAwarePaginator($items, count($items), 15, 1);
    }

    public function test_index_returns_paginated_warehouses(): void
    {
        $response = $this->actingAsUser()->getJson('/api/warehouses?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_show_returns_warehouse_resource(): void
    {
        $response = $this->actingAsUser()->getJson('/api/warehouses/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_store_creates_warehouse(): void
    {
        $response = $this->actingAsUser()->postJson('/api/warehouses', [
            'tenant_id' => 1,
            'name' => 'Main Warehouse',
            'code' => 'WH-001',
            'type' => 'standard',
            'is_active' => true,
            'is_default' => true,
        ]);

        $response->assertCreated()->assertJsonPath('data.id', 1);
    }

    public function test_update_modifies_warehouse(): void
    {
        $response = $this->actingAsUser()->putJson('/api/warehouses/1', [
            'tenant_id' => 1,
            'name' => 'Main Warehouse Updated',
            'code' => 'WH-001',
            'type' => 'standard',
            'is_active' => true,
            'is_default' => true,
            'row_version' => 1,
        ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_destroy_deletes_warehouse(): void
    {
        $response = $this->actingAsUser()->deleteJson('/api/warehouses/1');

        $response->assertOk()->assertJsonPath('message', 'Warehouse deleted successfully');
    }

    public function test_location_index_returns_warehouse_locations(): void
    {
        $response = $this->actingAsUser()->getJson('/api/warehouses/1/locations?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 10);
    }

    public function test_location_show_returns_warehouse_location_resource(): void
    {
        $response = $this->actingAsUser()->getJson('/api/warehouses/1/locations/10');

        $response->assertOk()->assertJsonPath('data.id', 10);
    }

    public function test_location_store_creates_warehouse_location(): void
    {
        $response = $this->actingAsUser()->postJson('/api/warehouses/1/locations', [
            'tenant_id' => 1,
            'name' => 'A1-BIN-01',
            'code' => 'BIN-01',
            'type' => 'bin',
            'is_active' => true,
            'is_pickable' => true,
            'is_receivable' => true,
            'capacity' => 100,
        ]);

        $response->assertCreated()->assertJsonPath('data.id', 10);
    }

    public function test_location_update_modifies_warehouse_location(): void
    {
        $response = $this->actingAsUser()->putJson('/api/warehouses/1/locations/10', [
            'tenant_id' => 1,
            'name' => 'A1-BIN-01-UPDATED',
            'code' => 'BIN-01',
            'type' => 'bin',
            'is_active' => true,
            'is_pickable' => true,
            'is_receivable' => true,
            'capacity' => 120,
        ]);

        $response->assertOk()->assertJsonPath('data.id', 10);
    }

    public function test_location_destroy_deletes_warehouse_location(): void
    {
        $response = $this->actingAsUser()->deleteJson('/api/warehouses/1/locations/10');

        $response->assertOk()->assertJsonPath('message', 'Warehouse location deleted successfully');
    }

    public function test_movements_returns_warehouse_stock_movements(): void
    {
        $response = $this->actingAsUser()->getJson('/api/warehouses/1/stock-movements?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.id', 500);
    }

    public function test_store_movement_records_stock_movement(): void
    {
        $response = $this->actingAsUser()->postJson('/api/warehouses/1/stock-movements', [
            'tenant_id' => 1,
            'product_id' => 100,
            'to_location_id' => 10,
            'movement_type' => 'receipt',
            'uom_id' => 1,
            'quantity' => 5,
            'unit_cost' => 10,
            'performed_by' => 99,
            'notes' => 'Initial stock receipt',
        ]);

        $response->assertCreated()->assertJsonPath('data.id', 500);
    }

    public function test_stock_levels_returns_warehouse_stock_levels(): void
    {
        $response = $this->actingAsUser()->getJson('/api/warehouses/1/stock-levels?tenant_id=1');

        $response->assertOk()->assertJsonPath('data.0.product_id', 100);
    }
}
