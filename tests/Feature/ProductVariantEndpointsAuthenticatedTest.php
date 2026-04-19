<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Passport\Passport;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Product\Application\Contracts\CreateProductVariantServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductVariantServiceInterface;
use Modules\Product\Application\Contracts\FindProductVariantServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductVariantServiceInterface;
use Modules\Product\Domain\Entities\ProductVariant;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class ProductVariantEndpointsAuthenticatedTest extends TestCase
{
    /** @var FindProductVariantServiceInterface&MockObject */
    private FindProductVariantServiceInterface $findProductVariantService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->findProductVariantService = $this->createMock(FindProductVariantServiceInterface::class);
        $this->app->instance(FindProductVariantServiceInterface::class, $this->findProductVariantService);

        $this->app->instance(CreateProductVariantServiceInterface::class, $this->createMock(CreateProductVariantServiceInterface::class));
        $this->app->instance(UpdateProductVariantServiceInterface::class, $this->createMock(UpdateProductVariantServiceInterface::class));
        $this->app->instance(DeleteProductVariantServiceInterface::class, $this->createMock(DeleteProductVariantServiceInterface::class));

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient->method('getConfig')->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);

        $tenantConfigManager = $this->createMock(TenantConfigManagerInterface::class);
        $this->app->instance(TenantConfigManagerInterface::class, $tenantConfigManager);

        $user = new UserModel([
            'id' => 261,
            'tenant_id' => 9,
            'email' => 'product.variant.test@example.com',
            'password' => 'secret',
            'first_name' => 'Product',
            'last_name' => 'VariantTester',
        ]);
        $user->setAttribute('id', 261);
        $user->setAttribute('tenant_id', 9);

        Passport::actingAs($user, [], 'api');
    }

    public function test_authenticated_index_returns_success_payload(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildProductVariant(id: 101)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findProductVariantService
            ->expects($this->once())
            ->method('list')
            ->with(
                [
                    'product_id' => 41,
                    'name' => 'Red',
                ],
                15,
                1,
                '-created_at'
            )
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/product-variants?product_id=41&name=Red&sort=-created_at');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 101)
            ->assertJsonPath('data.0.name', 'Red Variant')
            ->assertJsonPath('data.0.product_id', 41);
    }

    public function test_authenticated_show_returns_success_payload(): void
    {
        $this->findProductVariantService
            ->expects($this->once())
            ->method('find')
            ->with(102)
            ->willReturn($this->buildProductVariant(id: 102));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/product-variants/102');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 102)
            ->assertJsonPath('data.product_id', 41)
            ->assertJsonPath('data.sku', 'RED-102');
    }

    public function test_authenticated_index_returns_forbidden_when_authorization_fails(): void
    {
        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(false);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $this->findProductVariantService
            ->expects($this->never())
            ->method('list');

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/product-variants');

        $response->assertStatus(HttpResponse::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'This action is unauthorized.');
    }

    private function buildProductVariant(int $id): ProductVariant
    {
        return new ProductVariant(
            id: $id,
            productId: 41,
            sku: 'RED-102',
            name: 'Red Variant',
            isDefault: false,
            isActive: true,
            metadata: ['color' => 'red'],
        );
    }
}
