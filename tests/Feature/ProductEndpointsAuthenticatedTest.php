<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Passport\Passport;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Product\Application\Contracts\CreateProductServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductServiceInterface;
use Modules\Product\Application\Contracts\FindProductServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductServiceInterface;
use Modules\Product\Domain\Entities\Product;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class ProductEndpointsAuthenticatedTest extends TestCase
{
    /** @var FindProductServiceInterface&MockObject */
    private FindProductServiceInterface $findProductService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->findProductService = $this->createMock(FindProductServiceInterface::class);
        $this->app->instance(FindProductServiceInterface::class, $this->findProductService);

        $this->app->instance(CreateProductServiceInterface::class, $this->createMock(CreateProductServiceInterface::class));
        $this->app->instance(UpdateProductServiceInterface::class, $this->createMock(UpdateProductServiceInterface::class));
        $this->app->instance(DeleteProductServiceInterface::class, $this->createMock(DeleteProductServiceInterface::class));

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient->method('getConfig')->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);

        $tenantConfigManager = $this->createMock(TenantConfigManagerInterface::class);
        $this->app->instance(TenantConfigManagerInterface::class, $tenantConfigManager);

        $user = new UserModel([
            'id' => 201,
            'tenant_id' => 9,
            'email' => 'product.test@example.com',
            'password' => 'secret',
            'first_name' => 'Product',
            'last_name' => 'Tester',
        ]);
        $user->setAttribute('id', 201);
        $user->setAttribute('tenant_id', 9);

        Passport::actingAs($user, [], 'api');
    }

    public function test_authenticated_index_returns_success_payload(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildProduct(id: 41)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findProductService
            ->expects($this->once())
            ->method('list')
            ->with(
                [
                    'tenant_id' => 9,
                    'type' => 'physical',
                    'name' => 'Widget',
                ],
                15,
                1,
                '-created_at'
            )
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/products?tenant_id=9&type=physical&name=Widget&sort=-created_at');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 41)
            ->assertJsonPath('data.0.name', 'Widget')
            ->assertJsonPath('data.0.type', 'physical');
    }

    public function test_authenticated_show_returns_success_payload(): void
    {
        $this->findProductService
            ->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($this->buildProduct(id: 42));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/products/42');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 42)
            ->assertJsonPath('data.tenant_id', 9)
            ->assertJsonPath('data.name', 'Widget');
    }

    public function test_authenticated_index_returns_forbidden_when_authorization_fails(): void
    {
        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(false);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $this->findProductService
            ->expects($this->never())
            ->method('list');

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/products');

        $response->assertStatus(HttpResponse::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'This action is unauthorized.');
    }

    private function buildProduct(int $id): Product
    {
        return new Product(
            id: $id,
            tenantId: 9,
            type: 'physical',
            name: 'Widget',
            slug: 'widget',
            sku: 'WGT-001',
            description: 'Sample product',
            baseUomId: 1,
            purchaseUomId: 1,
            salesUomId: 1,
            uomConversionFactor: '1',
            isBatchTracked: false,
            isLotTracked: false,
            isSerialTracked: false,
            valuationMethod: 'fifo',
            standardCost: '10.5000',
            incomeAccountId: 1,
            cogsAccountId: 2,
            inventoryAccountId: 3,
            expenseAccountId: 4,
            isActive: true,
            metadata: ['source' => 'feature-test'],
        );
    }
}
