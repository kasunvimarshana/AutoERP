<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Inventory\Application\Contracts\FindStockLevelServiceInterface;
use Modules\Inventory\Application\Contracts\FindStockMovementServiceInterface;
use Modules\Inventory\Application\Contracts\RecordStockMovementServiceInterface;
use Modules\Inventory\Domain\Entities\StockMovement;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

class InventoryStockEndpointsAuthenticatedTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $authUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authUser = new UserModel([
            'name' => 'Stock Admin',
            'email' => 'stock-admin@example.com',
            'password' => 'hashed',
        ]);
        $this->authUser->setAttribute('id', 99);
        $this->authUser->setAttribute('tenant_id', 1);

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $presenceVerifier = $this->createMock(PresenceVerifierInterface::class);
        $presenceVerifier->method('getCount')->willReturn(1);
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        $movement = new StockMovement(
            tenantId: 1,
            productId: 10,
            variantId: null,
            batchId: null,
            serialId: null,
            fromLocationId: null,
            toLocationId: 5,
            movementType: 'receipt',
            referenceType: null,
            referenceId: null,
            uomId: 1,
            quantity: '50.000000',
            unitCost: '10.000000',
            performedBy: 99,
            performedAt: null,
            notes: null,
            metadata: null,
            id: 1,
        );

        $recordService = $this->createMock(RecordStockMovementServiceInterface::class);
        $recordService->method('execute')->willReturn($movement);
        $this->app->instance(RecordStockMovementServiceInterface::class, $recordService);

        $findMovementService = $this->createMock(FindStockMovementServiceInterface::class);
        $findMovementService->method('listByWarehouse')->willReturn([
            'data' => [$movement],
            'total' => 1,
            'per_page' => 15,
            'current_page' => 1,
        ]);
        $this->app->instance(FindStockMovementServiceInterface::class, $findMovementService);

        $findLevelService = $this->createMock(FindStockLevelServiceInterface::class);
        $findLevelService->method('listByWarehouse')->willReturn([
            'data' => [
                ['product_id' => 10, 'warehouse_id' => 7, 'quantity_on_hand' => '50.000000'],
            ],
            'total' => 1,
            'per_page' => 15,
            'current_page' => 1,
        ]);
        $this->app->instance(FindStockLevelServiceInterface::class, $findLevelService);
    }

    private function actingAsUser(): static
    {
        return $this->withHeader('X-Tenant-ID', '1')
            ->actingAs($this->authUser, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));
    }

    public function test_movements_index_returns_list(): void
    {
        $response = $this->actingAsUser()
            ->getJson('/api/inventory/warehouses/7/movements?tenant_id=1');

        $response->assertStatus(200);
    }

    public function test_store_movement_creates_record(): void
    {
        $response = $this->actingAsUser()
            ->postJson('/api/inventory/warehouses/7/movements', [
                'tenant_id' => 1,
                'warehouse_id' => 7,
                'product_id' => 10,
                'uom_id' => 1,
                'movement_type' => 'receipt',
                'quantity' => 50,
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.movement_type', 'receipt');
    }

    public function test_stock_levels_returns_list(): void
    {
        $response = $this->actingAsUser()
            ->getJson('/api/inventory/warehouses/7/stock-levels?tenant_id=1');

        $response->assertStatus(200);
    }
}
