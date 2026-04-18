<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Passport\Passport;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Product\Application\Contracts\CreateProductBrandServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductBrandServiceInterface;
use Modules\Product\Application\Contracts\FindProductBrandServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductBrandServiceInterface;
use Modules\Product\Domain\Entities\ProductBrand;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ProductBrandEndpointsAuthenticatedTest extends TestCase
{
    /** @var FindProductBrandServiceInterface&MockObject */
    private FindProductBrandServiceInterface $findProductBrandService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->findProductBrandService = $this->createMock(FindProductBrandServiceInterface::class);
        $this->app->instance(FindProductBrandServiceInterface::class, $this->findProductBrandService);

        $this->app->instance(CreateProductBrandServiceInterface::class, $this->createMock(CreateProductBrandServiceInterface::class));
        $this->app->instance(UpdateProductBrandServiceInterface::class, $this->createMock(UpdateProductBrandServiceInterface::class));
        $this->app->instance(DeleteProductBrandServiceInterface::class, $this->createMock(DeleteProductBrandServiceInterface::class));

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient->method('getConfig')->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);

        $tenantConfigManager = $this->createMock(TenantConfigManagerInterface::class);
        $this->app->instance(TenantConfigManagerInterface::class, $tenantConfigManager);

        $user = new UserModel([
            'id' => 221,
            'tenant_id' => 9,
            'email' => 'product.brand.test@example.com',
            'password' => 'secret',
            'first_name' => 'Product',
            'last_name' => 'BrandTester',
        ]);
        $user->setAttribute('id', 221);
        $user->setAttribute('tenant_id', 9);

        Passport::actingAs($user, [], 'api');
    }

    public function test_authenticated_index_returns_success_payload(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildProductBrand(id: 71)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findProductBrandService
            ->expects($this->once())
            ->method('list')
            ->with(
                [
                    'tenant_id' => 9,
                    'name' => 'Acme',
                ],
                15,
                1,
                '-created_at'
            )
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/product-brands?tenant_id=9&name=Acme&sort=-created_at');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.0.id', 71)
            ->assertJsonPath('data.0.name', 'Acme');
    }

    public function test_authenticated_show_returns_success_payload(): void
    {
        $this->findProductBrandService
            ->expects($this->once())
            ->method('find')
            ->with(72)
            ->willReturn($this->buildProductBrand(id: 72));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/product-brands/72');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.id', 72)
            ->assertJsonPath('data.tenant_id', 9)
            ->assertJsonPath('data.name', 'Acme');
    }

    public function test_authenticated_index_returns_forbidden_when_authorization_fails(): void
    {
        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(false);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $this->findProductBrandService
            ->expects($this->never())
            ->method('list');

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/product-brands');

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'This action is unauthorized.');
    }

    private function buildProductBrand(int $id): ProductBrand
    {
        return new ProductBrand(
            id: $id,
            tenantId: 9,
            name: 'Acme',
            slug: 'acme',
            code: 'ACM',
            isActive: true,
            metadata: ['source' => 'feature-test'],
        );
    }
}
