<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Inventory\Application\Contracts\AllocationEngineServiceInterface;
use Modules\Inventory\Application\Contracts\ManageValuationConfigServiceInterface;
use Modules\Inventory\Application\Contracts\ValuationEngineServiceInterface;
use Modules\Inventory\Domain\Entities\ValuationConfig;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class InventoryValuationEndpointsAuthenticatedTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $authUser;
    private ValuationConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authUser = new UserModel([
            'name' => 'Valuation Admin',
            'email' => 'valuation-admin@example.com',
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

        $this->config = new ValuationConfig(
            tenantId: 1,
            orgUnitId: null,
            warehouseId: 2,
            productId: 10,
            transactionType: 'receipt',
            valuationMethod: 'fifo',
            allocationStrategy: 'fifo',
            isActive: true,
            metadata: null,
            id: 1,
        );

        $manageService = $this->createMock(ManageValuationConfigServiceInterface::class);
        $manageService->method('list')->willReturn([
            'data' => [$this->config],
            'total' => 1,
            'per_page' => 15,
            'current_page' => 1,
        ]);
        $manageService->method('create')->willReturn($this->config);
        $manageService->method('find')->willReturn($this->config);
        $manageService->method('update')->willReturn(new ValuationConfig(
            tenantId: 1,
            orgUnitId: null,
            warehouseId: 2,
            productId: 10,
            transactionType: 'receipt',
            valuationMethod: 'weighted_average',
            allocationStrategy: 'nearest_bin',
            isActive: true,
            metadata: null,
            id: 1,
        ));
        $this->app->instance(ManageValuationConfigServiceInterface::class, $manageService);

        $valuationEngine = $this->createMock(ValuationEngineServiceInterface::class);
        $valuationEngine->method('resolveValuationMethod')->willReturn('fifo');
        $this->app->instance(ValuationEngineServiceInterface::class, $valuationEngine);

        $allocationEngine = $this->createMock(AllocationEngineServiceInterface::class);
        $allocationEngine->method('resolveAllocationStrategy')->willReturn('nearest_bin');
        $this->app->instance(AllocationEngineServiceInterface::class, $allocationEngine);
    }

    private function actingAsUser(): static
    {
        return $this->withHeader('X-Tenant-ID', '1')
            ->actingAs(
                $this->authUser,
                (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')),
            );
    }

    public function test_index_returns_configs(): void
    {
        $response = $this->actingAsUser()
            ->getJson('/api/inventory/valuation-configs?tenant_id=1');

        $response->assertStatus(HttpResponse::HTTP_OK);
    }

    public function test_store_creates_config(): void
    {
        $response = $this->actingAsUser()
            ->postJson('/api/inventory/valuation-configs', [
                'tenant_id' => 1,
                'warehouse_id' => 2,
                'product_id' => 10,
                'transaction_type' => 'receipt',
                'valuation_method' => 'fifo',
                'allocation_strategy' => 'fifo',
                'is_active' => true,
            ]);

        $response->assertStatus(HttpResponse::HTTP_CREATED);
        $response->assertJsonPath('data.id', 1);
        $response->assertJsonPath('data.valuation_method', 'fifo');
    }

    public function test_show_returns_config(): void
    {
        $response = $this->actingAsUser()
            ->getJson('/api/inventory/valuation-configs/1?tenant_id=1');

        $response->assertStatus(HttpResponse::HTTP_OK);
        $response->assertJsonPath('data.id', 1);
    }

    public function test_update_returns_updated_config(): void
    {
        $response = $this->actingAsUser()
            ->putJson('/api/inventory/valuation-configs/1', [
                'tenant_id' => 1,
                'valuation_method' => 'weighted_average',
                'allocation_strategy' => 'nearest_bin',
            ]);

        $response->assertStatus(HttpResponse::HTTP_OK);
        $response->assertJsonPath('data.valuation_method', 'weighted_average');
        $response->assertJsonPath('data.allocation_strategy', 'nearest_bin');
    }

    public function test_destroy_returns_no_content(): void
    {
        $response = $this->actingAsUser()
            ->deleteJson('/api/inventory/valuation-configs/1?tenant_id=1');

        $response->assertStatus(HttpResponse::HTTP_NO_CONTENT);
    }

    public function test_resolve_returns_method_and_strategy(): void
    {
        $response = $this->actingAsUser()
            ->getJson('/api/inventory/valuation-configs/resolve?tenant_id=1&product_id=10&warehouse_id=2');

        $response->assertStatus(HttpResponse::HTTP_OK);
        $response->assertJsonPath('valuation_method', 'fifo');
        $response->assertJsonPath('allocation_strategy', 'nearest_bin');
    }
}
