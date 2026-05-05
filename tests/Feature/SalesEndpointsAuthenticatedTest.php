<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Sales\Application\Contracts\ApproveSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\CancelSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\ConfirmSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\CreateSalesInvoiceServiceInterface;
use Modules\Sales\Application\Contracts\CreateSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\CreateSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\CreateShipmentServiceInterface;
use Modules\Sales\Application\Contracts\DeleteSalesInvoiceServiceInterface;
use Modules\Sales\Application\Contracts\DeleteSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\DeleteSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\DeleteShipmentServiceInterface;
use Modules\Sales\Application\Contracts\FindSalesInvoiceServiceInterface;
use Modules\Sales\Application\Contracts\FindSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\FindSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\FindShipmentServiceInterface;
use Modules\Sales\Application\Contracts\PostSalesInvoiceServiceInterface;
use Modules\Sales\Application\Contracts\ProcessShipmentServiceInterface;
use Modules\Sales\Application\Contracts\ReceiveSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\RecordSalesPaymentServiceInterface;
use Modules\Sales\Application\Contracts\UpdateSalesInvoiceServiceInterface;
use Modules\Sales\Application\Contracts\UpdateSalesOrderServiceInterface;
use Modules\Sales\Application\Contracts\UpdateSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\UpdateShipmentServiceInterface;
use Modules\Sales\Domain\Entities\SalesOrder;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class SalesEndpointsAuthenticatedTest extends TestCase
{
    private static bool $routesCleared = false;

    /** @var FindSalesOrderServiceInterface&MockObject */
    private FindSalesOrderServiceInterface $findSalesOrderService;

    /** @var CreateSalesOrderServiceInterface&MockObject */
    private CreateSalesOrderServiceInterface $createSalesOrderService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearRoutesCacheOnce();

        $this->findSalesOrderService = $this->createMock(FindSalesOrderServiceInterface::class);
        $this->createSalesOrderService = $this->createMock(CreateSalesOrderServiceInterface::class);

        $this->app->instance(FindSalesOrderServiceInterface::class, $this->findSalesOrderService);
        $this->app->instance(CreateSalesOrderServiceInterface::class, $this->createSalesOrderService);
        $this->app->instance(UpdateSalesOrderServiceInterface::class, $this->createMock(UpdateSalesOrderServiceInterface::class));
        $this->app->instance(DeleteSalesOrderServiceInterface::class, $this->createMock(DeleteSalesOrderServiceInterface::class));
        $this->app->instance(ConfirmSalesOrderServiceInterface::class, $this->createMock(ConfirmSalesOrderServiceInterface::class));
        $this->app->instance(CancelSalesOrderServiceInterface::class, $this->createMock(CancelSalesOrderServiceInterface::class));

        $this->app->instance(FindShipmentServiceInterface::class, $this->createMock(FindShipmentServiceInterface::class));
        $this->app->instance(CreateShipmentServiceInterface::class, $this->createMock(CreateShipmentServiceInterface::class));
        $this->app->instance(UpdateShipmentServiceInterface::class, $this->createMock(UpdateShipmentServiceInterface::class));
        $this->app->instance(DeleteShipmentServiceInterface::class, $this->createMock(DeleteShipmentServiceInterface::class));
        $this->app->instance(ProcessShipmentServiceInterface::class, $this->createMock(ProcessShipmentServiceInterface::class));

        $this->app->instance(FindSalesInvoiceServiceInterface::class, $this->createMock(FindSalesInvoiceServiceInterface::class));
        $this->app->instance(CreateSalesInvoiceServiceInterface::class, $this->createMock(CreateSalesInvoiceServiceInterface::class));
        $this->app->instance(UpdateSalesInvoiceServiceInterface::class, $this->createMock(UpdateSalesInvoiceServiceInterface::class));
        $this->app->instance(DeleteSalesInvoiceServiceInterface::class, $this->createMock(DeleteSalesInvoiceServiceInterface::class));
        $this->app->instance(PostSalesInvoiceServiceInterface::class, $this->createMock(PostSalesInvoiceServiceInterface::class));
        $this->app->instance(RecordSalesPaymentServiceInterface::class, $this->createMock(RecordSalesPaymentServiceInterface::class));

        $this->app->instance(FindSalesReturnServiceInterface::class, $this->createMock(FindSalesReturnServiceInterface::class));
        $this->app->instance(CreateSalesReturnServiceInterface::class, $this->createMock(CreateSalesReturnServiceInterface::class));
        $this->app->instance(UpdateSalesReturnServiceInterface::class, $this->createMock(UpdateSalesReturnServiceInterface::class));
        $this->app->instance(DeleteSalesReturnServiceInterface::class, $this->createMock(DeleteSalesReturnServiceInterface::class));
        $this->app->instance(ApproveSalesReturnServiceInterface::class, $this->createMock(ApproveSalesReturnServiceInterface::class));
        $this->app->instance(ReceiveSalesReturnServiceInterface::class, $this->createMock(ReceiveSalesReturnServiceInterface::class));

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

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
            'id' => 503,
            'tenant_id' => 7,
            'email' => 'sales.test@example.com',
            'password' => 'secret',
            'first_name' => 'Sales',
            'last_name' => 'Tester',
        ]);
        $user->setAttribute('id', 503);
        $user->setAttribute('tenant_id', 7);

        $this->actingAs($user, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));
    }

    public function test_authenticated_sales_order_index_returns_success_payload(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildSalesOrder(id: 31)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findSalesOrderService
            ->expects($this->once())
            ->method('list')
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/sales-orders?tenant_id=7');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 31)
            ->assertJsonPath('data.0.tenant_id', 7)
            ->assertJsonPath('data.0.status', 'draft');
    }

    public function test_authenticated_sales_order_show_returns_success_payload(): void
    {
        $this->findSalesOrderService
            ->expects($this->once())
            ->method('find')
            ->with(31)
            ->willReturn($this->buildSalesOrder(id: 31));

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/sales-orders/31');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 31)
            ->assertJsonPath('data.tenant_id', 7)
            ->assertJsonPath('data.status', 'draft');
    }

    public function test_authenticated_sales_order_store_validates_required_fields(): void
    {
        $this->createSalesOrderService
            ->expects($this->never())
            ->method('execute');

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/sales-orders', []);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['customer_id']);
    }

    private function buildSalesOrder(int $id): SalesOrder
    {
        return new SalesOrder(
            tenantId: 7,
            customerId: 1,
            warehouseId: 1,
            currencyId: 1,
            orderDate: new \DateTimeImmutable('2024-01-01'),
            status: 'draft',
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
