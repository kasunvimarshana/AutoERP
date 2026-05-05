<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class WarehouseEndpointsAuthenticatedTest extends TestCase
{
    private static bool $routesCleared = false;

    /** @var FindWarehouseServiceInterface&MockObject */
    private FindWarehouseServiceInterface $findWarehouseService;

    /** @var CreateWarehouseServiceInterface&MockObject */
    private CreateWarehouseServiceInterface $createWarehouseService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearRoutesCacheOnce();

        $this->findWarehouseService = $this->createMock(FindWarehouseServiceInterface::class);
        $this->createWarehouseService = $this->createMock(CreateWarehouseServiceInterface::class);

        $this->app->instance(FindWarehouseServiceInterface::class, $this->findWarehouseService);
        $this->app->instance(CreateWarehouseServiceInterface::class, $this->createWarehouseService);
        $this->app->instance(UpdateWarehouseServiceInterface::class, $this->createMock(UpdateWarehouseServiceInterface::class));
        $this->app->instance(DeleteWarehouseServiceInterface::class, $this->createMock(DeleteWarehouseServiceInterface::class));

        $this->app->instance(FindWarehouseLocationServiceInterface::class, $this->createMock(FindWarehouseLocationServiceInterface::class));
        $this->app->instance(CreateWarehouseLocationServiceInterface::class, $this->createMock(CreateWarehouseLocationServiceInterface::class));
        $this->app->instance(UpdateWarehouseLocationServiceInterface::class, $this->createMock(UpdateWarehouseLocationServiceInterface::class));
        $this->app->instance(DeleteWarehouseLocationServiceInterface::class, $this->createMock(DeleteWarehouseLocationServiceInterface::class));

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient->method('getConfig')->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);

        $tenantConfigManager = $this->createMock(TenantConfigManagerInterface::class);
        $this->app->instance(TenantConfigManagerInterface::class, $tenantConfigManager);

        $presenceVerifier = $this->createMock(PresenceVerifierInterface::class);
        $presenceVerifier->method('getCount')->willReturn(1);
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app->instance(PresenceVerifierInterface::class, $presenceVerifier);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        $user = new UserModel([
            'id' => 502,
            'tenant_id' => 7,
            'email' => 'warehouse.test@example.com',
            'password' => 'secret',
            'first_name' => 'Warehouse',
            'last_name' => 'Tester',
        ]);
        $user->setAttribute('id', 502);
        $user->setAttribute('tenant_id', 7);

        $this->actingAs($user, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));
    }

    public function test_authenticated_warehouse_index_returns_success_payload(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildWarehouse(id: 21)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findWarehouseService
            ->expects($this->once())
            ->method('list')
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/warehouses?tenant_id=7');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 21)
            ->assertJsonPath('data.0.tenant_id', 7)
            ->assertJsonPath('data.0.name', 'Main Warehouse');
    }

    public function test_authenticated_warehouse_show_returns_success_payload(): void
    {
        $this->findWarehouseService
            ->expects($this->once())
            ->method('find')
            ->with(21)
            ->willReturn($this->buildWarehouse(id: 21));

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/warehouses/21');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 21)
            ->assertJsonPath('data.tenant_id', 7)
            ->assertJsonPath('data.name', 'Main Warehouse');
    }

    public function test_authenticated_warehouse_store_validates_required_fields(): void
    {
        $this->createWarehouseService
            ->expects($this->never())
            ->method('execute');

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/warehouses', []);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name']);
    }

    private function buildWarehouse(int $id): Warehouse
    {
        return new Warehouse(
            tenantId: 7,
            name: 'Main Warehouse',
            type: 'standard',
            isActive: true,
            isDefault: false,
            id: $id,
            createdAt: new \DateTimeImmutable('2024-01-01'),
            updatedAt: new \DateTimeImmutable('2024-01-01'),
        );
    }

    private function clearRoutesCacheOnce(): void
    {
        if (self::$routesCleared) {
            return;
        }

        Artisan::call('route:clear');
        self::$routesCleared = true;
    }
}
