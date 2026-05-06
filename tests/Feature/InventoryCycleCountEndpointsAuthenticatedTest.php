<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Inventory\Application\Contracts\CompleteCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\CreateCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\FindCycleCountServiceInterface;
use Modules\Inventory\Application\Contracts\StartCycleCountServiceInterface;
use Modules\Inventory\Domain\Entities\CycleCountHeader;
use Modules\Inventory\Domain\Entities\CycleCountLine;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

class InventoryCycleCountEndpointsAuthenticatedTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $authUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authUser = new UserModel([
            'name' => 'Inventory Admin',
            'email' => 'inventory-admin@example.com',
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

        // Setup default mocks for all services
        $createCycleCountService = $this->createMock(CreateCycleCountServiceInterface::class);
        $createCycleCountService->method('execute')->willReturn(
            new CycleCountHeader(
                tenantId: 1,
                warehouseId: 1,
                locationId: null,
                status: 'draft',
                countedByUserId: null,
                countedAt: null,
                approvedByUserId: null,
                approvedAt: null,
                lines: [
                    new CycleCountLine(
                        tenantId: 1,
                        productId: 100,
                        variantId: null,
                        batchId: null,
                        serialId: null,
                        systemQty: '10.000000',
                        countedQty: '0.000000',
                        varianceQty: '0.000000',
                        unitCost: '25.000000',
                        varianceValue: '0.000000',
                        adjustmentMovementId: null,
                        id: 1,
                    ),
                ],
                id: 1,
            )
        );
        $this->app->instance(CreateCycleCountServiceInterface::class, $createCycleCountService);

        $findCycleCountService = $this->createMock(FindCycleCountServiceInterface::class);
        $findCycleCountService->method('list')->willReturn([
            'data' => [
                new CycleCountHeader(
                    tenantId: 1,
                    warehouseId: 1,
                    locationId: null,
                    status: 'draft',
                    countedByUserId: null,
                    countedAt: null,
                    approvedByUserId: null,
                    approvedAt: null,
                    lines: [],
                    id: 1,
                ),
            ],
        ]);
        $findCycleCountService->method('find')->willReturn(
            new CycleCountHeader(
                tenantId: 1,
                warehouseId: 1,
                locationId: null,
                status: 'draft',
                countedByUserId: null,
                countedAt: null,
                approvedByUserId: null,
                approvedAt: null,
                lines: [],
                id: 1,
            )
        );
        $this->app->instance(FindCycleCountServiceInterface::class, $findCycleCountService);

        $startCycleCountService = $this->createMock(StartCycleCountServiceInterface::class);
        $startCycleCountService->method('execute')->willReturn(
            new CycleCountHeader(
                tenantId: 1,
                warehouseId: 1,
                locationId: null,
                status: 'in_progress',
                countedByUserId: 99,
                countedAt: '2026-05-04T10:00:00Z',
                approvedByUserId: null,
                approvedAt: null,
                lines: [],
                id: 1,
            )
        );
        $this->app->instance(StartCycleCountServiceInterface::class, $startCycleCountService);

        $completeCycleCountService = $this->createMock(CompleteCycleCountServiceInterface::class);
        $completeCycleCountService->method('execute')->willReturn(
            new CycleCountHeader(
                tenantId: 1,
                warehouseId: 1,
                locationId: null,
                status: 'completed',
                countedByUserId: 99,
                countedAt: '2026-05-04T10:00:00Z',
                approvedByUserId: 99,
                approvedAt: '2026-05-04T11:00:00Z',
                lines: [
                    new CycleCountLine(
                        tenantId: 1,
                        productId: 100,
                        variantId: null,
                        batchId: null,
                        serialId: null,
                        systemQty: '10.000000',
                        countedQty: '10.000000',
                        varianceQty: '0.000000',
                        unitCost: '25.000000',
                        varianceValue: '0.000000',
                        adjustmentMovementId: null,
                        id: 1,
                    ),
                ],
                id: 1,
            )
        );
        $this->app->instance(CompleteCycleCountServiceInterface::class, $completeCycleCountService);
    }

    private function actingAsUser(): static
    {
        return $this->withHeader('X-Tenant-ID', '1')->actingAs(
            $this->authUser,
            (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api'))
        );
    }

    public function test_cycle_count_index(): void
    {
        $response = $this->actingAsUser()
            ->getJson('/api/inventory/cycle-counts?tenant_id=1');

        $response->assertOk();
    }

    public function test_cycle_count_store(): void
    {
        $response = $this->actingAsUser()
            ->postJson('/api/inventory/cycle-counts', [
                'tenant_id' => 1,
                'warehouse_id' => 1,
                'lines' => [
                    [
                        'product_id' => 100,
                        'counted_qty' => '0.000000',
                        'unit_cost' => '25.000000',
                    ],
                ],
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.id', 1);
    }

    public function test_cycle_count_show(): void
    {
        $response = $this->actingAsUser()
            ->getJson('/api/inventory/cycle-counts/1?tenant_id=1');

        $response->assertOk();
        $response->assertJsonPath('data.id', 1);
    }

    public function test_cycle_count_start(): void
    {
        $response = $this->actingAsUser()
            ->postJson('/api/inventory/cycle-counts/1/start', [
                'tenant_id' => 1,
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.id', 1);
    }

    public function test_cycle_count_complete(): void
    {
        $response = $this->actingAsUser()
            ->postJson('/api/inventory/cycle-counts/1/complete', [
                'tenant_id' => 1,
                'approved_by_user_id' => 99,
                'lines' => [
                    [
                        'line_id' => 1,
                        'counted_qty' => '10.000000',
                    ],
                ],
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.id', 1);
    }
}
