<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\ServiceCenter\Application\Contracts\CompleteServiceOrderServiceInterface;
use Modules\ServiceCenter\Application\Contracts\CreateServiceOrderServiceInterface;
use Modules\ServiceCenter\Domain\Entities\ServiceOrder;
use Modules\ServiceCenter\Domain\RepositoryInterfaces\ServiceOrderRepositoryInterface;
use Modules\ServiceCenter\Domain\RepositoryInterfaces\ServicePartUsageRepositoryInterface;
use Modules\ServiceCenter\Domain\RepositoryInterfaces\ServiceTaskRepositoryInterface;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class ServiceCenterEndpointsAuthenticatedTest extends TestCase
{
    private static bool $routesCleared = false;

    /** @var ServiceOrderRepositoryInterface&MockObject */
    private ServiceOrderRepositoryInterface $serviceOrderRepository;

    /** @var CreateServiceOrderServiceInterface&MockObject */
    private CreateServiceOrderServiceInterface $createServiceOrderService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearRoutesCacheOnce();

        $this->serviceOrderRepository = $this->createMock(ServiceOrderRepositoryInterface::class);
        $this->createServiceOrderService = $this->createMock(CreateServiceOrderServiceInterface::class);

        $this->app->instance(ServiceOrderRepositoryInterface::class, $this->serviceOrderRepository);
        $this->app->instance(CreateServiceOrderServiceInterface::class, $this->createServiceOrderService);
        $this->app->instance(CompleteServiceOrderServiceInterface::class, $this->createMock(CompleteServiceOrderServiceInterface::class));
        $this->app->instance(ServiceTaskRepositoryInterface::class, $this->createMock(ServiceTaskRepositoryInterface::class));
        $this->app->instance(ServicePartUsageRepositoryInterface::class, $this->createMock(ServicePartUsageRepositoryInterface::class));

        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient->method('getConfig')->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);

        $tenantConfigManager = $this->createMock(TenantConfigManagerInterface::class);
        $this->app->instance(TenantConfigManagerInterface::class, $tenantConfigManager);

        $presenceVerifier = $this->createMock(PresenceVerifierInterface::class);
        $presenceVerifier->method('getCount')->willReturn(0);
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app->instance(PresenceVerifierInterface::class, $presenceVerifier);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        $user = new UserModel([
            'id' => 505,
            'tenant_id' => 7,
            'email' => 'sc.test@example.com',
            'password' => 'secret',
            'first_name' => 'Service',
            'last_name' => 'Tester',
        ]);
        $user->setAttribute('id', 505);
        $user->setAttribute('tenant_id', 7);

        $this->actingAs($user, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));
    }

    public function test_authenticated_service_order_index_returns_success_payload(): void
    {
        $order = $this->buildServiceOrder('order-uuid-1');

        $this->serviceOrderRepository
            ->expects($this->once())
            ->method('getByTenant')
            ->willReturn([
                'data' => [$order],
                'total' => 1,
                'page' => 1,
                'limit' => 50,
            ]);

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/service-orders');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 'order-uuid-1')
            ->assertJsonPath('data.0.tenant_id', '7')
            ->assertJsonPath('data.0.status', 'pending');
    }

    public function test_authenticated_service_order_show_returns_success_payload(): void
    {
        $order = $this->buildServiceOrder('order-uuid-1');

        $this->serviceOrderRepository
            ->expects($this->once())
            ->method('findById')
            ->with('order-uuid-1')
            ->willReturn($order);

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/service-orders/order-uuid-1');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonFragment(['id' => 'order-uuid-1'])
            ->assertJsonFragment(['status' => 'pending']);
    }

    public function test_authenticated_service_order_store_validates_required_fields(): void
    {
        $this->createServiceOrderService
            ->expects($this->never())
            ->method('execute');

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/service-orders', []);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['asset_id']);
    }

    private function buildServiceOrder(string $id): ServiceOrder
    {
        return new ServiceOrder(
            id: $id,
            tenantId: '7',
            assetId: 'asset-uuid-1',
            assignedTechnicianId: null,
            orderNumber: 'SO-001',
            serviceType: 'maintenance',
            status: 'pending',
            description: 'Routine maintenance',
            scheduledAt: null,
            startedAt: null,
            completedAt: null,
            estimatedCost: '100.000000',
            totalCost: '0.000000',
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
