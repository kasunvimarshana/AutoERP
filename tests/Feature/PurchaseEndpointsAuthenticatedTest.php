<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Purchase\Application\Contracts\ApprovePurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\Contracts\CancelPurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\ConfirmPurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\CreateGrnServiceInterface;
use Modules\Purchase\Application\Contracts\CreatePurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\Contracts\CreatePurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\CreatePurchaseReturnServiceInterface;
use Modules\Purchase\Application\Contracts\DeleteGrnServiceInterface;
use Modules\Purchase\Application\Contracts\DeletePurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\Contracts\DeletePurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\DeletePurchaseReturnServiceInterface;
use Modules\Purchase\Application\Contracts\FindGrnServiceInterface;
use Modules\Purchase\Application\Contracts\FindPurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\Contracts\FindPurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\FindPurchaseReturnServiceInterface;
use Modules\Purchase\Application\Contracts\PostGrnServiceInterface;
use Modules\Purchase\Application\Contracts\PostPurchaseReturnServiceInterface;
use Modules\Purchase\Application\Contracts\RecordPurchasePaymentServiceInterface;
use Modules\Purchase\Application\Contracts\SendPurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\UpdateGrnServiceInterface;
use Modules\Purchase\Application\Contracts\UpdatePurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\Contracts\UpdatePurchaseOrderServiceInterface;
use Modules\Purchase\Application\Contracts\UpdatePurchaseReturnServiceInterface;
use Modules\Purchase\Domain\Entities\PurchaseOrder;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class PurchaseEndpointsAuthenticatedTest extends TestCase
{
    private static bool $routesCleared = false;

    /** @var FindPurchaseOrderServiceInterface&MockObject */
    private FindPurchaseOrderServiceInterface $findPurchaseOrderService;

    /** @var CreatePurchaseOrderServiceInterface&MockObject */
    private CreatePurchaseOrderServiceInterface $createPurchaseOrderService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearRoutesCacheOnce();

        $this->findPurchaseOrderService = $this->createMock(FindPurchaseOrderServiceInterface::class);
        $this->createPurchaseOrderService = $this->createMock(CreatePurchaseOrderServiceInterface::class);

        $this->app->instance(FindPurchaseOrderServiceInterface::class, $this->findPurchaseOrderService);
        $this->app->instance(CreatePurchaseOrderServiceInterface::class, $this->createPurchaseOrderService);
        $this->app->instance(UpdatePurchaseOrderServiceInterface::class, $this->createMock(UpdatePurchaseOrderServiceInterface::class));
        $this->app->instance(DeletePurchaseOrderServiceInterface::class, $this->createMock(DeletePurchaseOrderServiceInterface::class));
        $this->app->instance(ConfirmPurchaseOrderServiceInterface::class, $this->createMock(ConfirmPurchaseOrderServiceInterface::class));
        $this->app->instance(CancelPurchaseOrderServiceInterface::class, $this->createMock(CancelPurchaseOrderServiceInterface::class));
        $this->app->instance(SendPurchaseOrderServiceInterface::class, $this->createMock(SendPurchaseOrderServiceInterface::class));

        $this->app->instance(FindGrnServiceInterface::class, $this->createMock(FindGrnServiceInterface::class));
        $this->app->instance(CreateGrnServiceInterface::class, $this->createMock(CreateGrnServiceInterface::class));
        $this->app->instance(UpdateGrnServiceInterface::class, $this->createMock(UpdateGrnServiceInterface::class));
        $this->app->instance(DeleteGrnServiceInterface::class, $this->createMock(DeleteGrnServiceInterface::class));
        $this->app->instance(PostGrnServiceInterface::class, $this->createMock(PostGrnServiceInterface::class));

        $this->app->instance(FindPurchaseInvoiceServiceInterface::class, $this->createMock(FindPurchaseInvoiceServiceInterface::class));
        $this->app->instance(CreatePurchaseInvoiceServiceInterface::class, $this->createMock(CreatePurchaseInvoiceServiceInterface::class));
        $this->app->instance(UpdatePurchaseInvoiceServiceInterface::class, $this->createMock(UpdatePurchaseInvoiceServiceInterface::class));
        $this->app->instance(DeletePurchaseInvoiceServiceInterface::class, $this->createMock(DeletePurchaseInvoiceServiceInterface::class));
        $this->app->instance(ApprovePurchaseInvoiceServiceInterface::class, $this->createMock(ApprovePurchaseInvoiceServiceInterface::class));
        $this->app->instance(RecordPurchasePaymentServiceInterface::class, $this->createMock(RecordPurchasePaymentServiceInterface::class));

        $this->app->instance(FindPurchaseReturnServiceInterface::class, $this->createMock(FindPurchaseReturnServiceInterface::class));
        $this->app->instance(CreatePurchaseReturnServiceInterface::class, $this->createMock(CreatePurchaseReturnServiceInterface::class));
        $this->app->instance(UpdatePurchaseReturnServiceInterface::class, $this->createMock(UpdatePurchaseReturnServiceInterface::class));
        $this->app->instance(DeletePurchaseReturnServiceInterface::class, $this->createMock(DeletePurchaseReturnServiceInterface::class));
        $this->app->instance(PostPurchaseReturnServiceInterface::class, $this->createMock(PostPurchaseReturnServiceInterface::class));

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
            'id' => 504,
            'tenant_id' => 7,
            'email' => 'purchase.test@example.com',
            'password' => 'secret',
            'first_name' => 'Purchase',
            'last_name' => 'Tester',
        ]);
        $user->setAttribute('id', 504);
        $user->setAttribute('tenant_id', 7);

        $this->actingAs($user, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));
    }

    public function test_authenticated_purchase_order_index_returns_success_payload(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildPurchaseOrder(id: 41)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findPurchaseOrderService
            ->expects($this->once())
            ->method('list')
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/purchase-orders?tenant_id=7');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 41)
            ->assertJsonPath('data.0.tenant_id', 7)
            ->assertJsonPath('data.0.status', 'draft');
    }

    public function test_authenticated_purchase_order_show_returns_success_payload(): void
    {
        $this->findPurchaseOrderService
            ->expects($this->once())
            ->method('find')
            ->with(41)
            ->willReturn($this->buildPurchaseOrder(id: 41));

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->getJson('/api/purchase-orders/41');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 41)
            ->assertJsonPath('data.tenant_id', 7)
            ->assertJsonPath('data.status', 'draft');
    }

    public function test_authenticated_purchase_order_store_validates_required_fields(): void
    {
        $this->createPurchaseOrderService
            ->expects($this->never())
            ->method('execute');

        $response = $this->withHeader('X-Tenant-ID', '7')
            ->postJson('/api/purchase-orders', []);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['supplier_id']);
    }

    private function buildPurchaseOrder(int $id): PurchaseOrder
    {
        return new PurchaseOrder(
            tenantId: 7,
            supplierId: 1,
            warehouseId: 1,
            poNumber: 'PO-041',
            status: 'draft',
            currencyId: 1,
            exchangeRate: '1.000000',
            orderDate: new \DateTimeImmutable('2024-01-01'),
            createdBy: 504,
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
