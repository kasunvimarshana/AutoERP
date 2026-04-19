<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Passport\Passport;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Product\Application\Contracts\CreateProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\FindProductCategoryServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductCategoryServiceInterface;
use Modules\Product\Domain\Entities\ProductCategory;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class ProductCategoryEndpointsAuthenticatedTest extends TestCase
{
    /** @var FindProductCategoryServiceInterface&MockObject */
    private FindProductCategoryServiceInterface $findProductCategoryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->findProductCategoryService = $this->createMock(FindProductCategoryServiceInterface::class);
        $this->app->instance(FindProductCategoryServiceInterface::class, $this->findProductCategoryService);

        $this->app->instance(CreateProductCategoryServiceInterface::class, $this->createMock(CreateProductCategoryServiceInterface::class));
        $this->app->instance(UpdateProductCategoryServiceInterface::class, $this->createMock(UpdateProductCategoryServiceInterface::class));
        $this->app->instance(DeleteProductCategoryServiceInterface::class, $this->createMock(DeleteProductCategoryServiceInterface::class));

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient->method('getConfig')->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);

        $tenantConfigManager = $this->createMock(TenantConfigManagerInterface::class);
        $this->app->instance(TenantConfigManagerInterface::class, $tenantConfigManager);

        $user = new UserModel([
            'id' => 231,
            'tenant_id' => 9,
            'email' => 'product.category.test@example.com',
            'password' => 'secret',
            'first_name' => 'Product',
            'last_name' => 'CategoryTester',
        ]);
        $user->setAttribute('id', 231);
        $user->setAttribute('tenant_id', 9);

        Passport::actingAs($user, [], 'api');
    }

    public function test_authenticated_index_returns_success_payload(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildProductCategory(id: 81)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findProductCategoryService
            ->expects($this->once())
            ->method('list')
            ->with(
                [
                    'tenant_id' => 9,
                    'name' => 'Electronics',
                ],
                15,
                1,
                '-created_at'
            )
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/product-categories?tenant_id=9&name=Electronics&sort=-created_at');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 81)
            ->assertJsonPath('data.0.name', 'Electronics');
    }

    public function test_authenticated_show_returns_success_payload(): void
    {
        $this->findProductCategoryService
            ->expects($this->once())
            ->method('find')
            ->with(82)
            ->willReturn($this->buildProductCategory(id: 82));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/product-categories/82');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 82)
            ->assertJsonPath('data.tenant_id', 9)
            ->assertJsonPath('data.name', 'Electronics');
    }

    public function test_authenticated_index_returns_forbidden_when_authorization_fails(): void
    {
        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(false);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $this->findProductCategoryService
            ->expects($this->never())
            ->method('list');

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/product-categories');

        $response->assertStatus(HttpResponse::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'This action is unauthorized.');
    }

    private function buildProductCategory(int $id): ProductCategory
    {
        return new ProductCategory(
            id: $id,
            tenantId: 9,
            name: 'Electronics',
            slug: 'electronics',
            code: 'ELC',
            isActive: true,
            metadata: ['source' => 'feature-test'],
        );
    }
}
