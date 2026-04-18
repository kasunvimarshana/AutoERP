<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Passport\Passport;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Product\Application\Contracts\CreateProductIdentifierServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductIdentifierServiceInterface;
use Modules\Product\Application\Contracts\FindProductIdentifierServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductIdentifierServiceInterface;
use Modules\Product\Domain\Entities\ProductIdentifier;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ProductIdentifierEndpointsAuthenticatedTest extends TestCase
{
    /** @var FindProductIdentifierServiceInterface&MockObject */
    private FindProductIdentifierServiceInterface $findProductIdentifierService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->findProductIdentifierService = $this->createMock(FindProductIdentifierServiceInterface::class);
        $this->app->instance(FindProductIdentifierServiceInterface::class, $this->findProductIdentifierService);

        $this->app->instance(CreateProductIdentifierServiceInterface::class, $this->createMock(CreateProductIdentifierServiceInterface::class));
        $this->app->instance(UpdateProductIdentifierServiceInterface::class, $this->createMock(UpdateProductIdentifierServiceInterface::class));
        $this->app->instance(DeleteProductIdentifierServiceInterface::class, $this->createMock(DeleteProductIdentifierServiceInterface::class));

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient->method('getConfig')->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);

        $tenantConfigManager = $this->createMock(TenantConfigManagerInterface::class);
        $this->app->instance(TenantConfigManagerInterface::class, $tenantConfigManager);

        $user = new UserModel([
            'id' => 281,
            'tenant_id' => 9,
            'email' => 'product.identifier.test@example.com',
            'password' => 'secret',
            'first_name' => 'Product',
            'last_name' => 'IdentifierTester',
        ]);
        $user->setAttribute('id', 281);
        $user->setAttribute('tenant_id', 9);

        Passport::actingAs($user, [], 'api');
    }

    public function test_authenticated_index_returns_success_payload(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildProductIdentifier(id: 201)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findProductIdentifierService
            ->expects($this->once())
            ->method('list')
            ->with(
                [
                    'tenant_id' => 9,
                    'technology' => 'barcode_1d',
                    'value' => 'ABC-123',
                ],
                15,
                1,
                '-created_at'
            )
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/product-identifiers?tenant_id=9&technology=barcode_1d&value=ABC-123&sort=-created_at');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.0.id', 201)
            ->assertJsonPath('data.0.technology', 'barcode_1d')
            ->assertJsonPath('data.0.value', 'ABC-123');
    }

    public function test_authenticated_show_returns_success_payload(): void
    {
        $this->findProductIdentifierService
            ->expects($this->once())
            ->method('find')
            ->with(202)
            ->willReturn($this->buildProductIdentifier(id: 202));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/product-identifiers/202');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.id', 202)
            ->assertJsonPath('data.tenant_id', 9)
            ->assertJsonPath('data.product_id', 41)
            ->assertJsonPath('data.value', 'ABC-123');
    }

    public function test_authenticated_index_returns_forbidden_when_authorization_fails(): void
    {
        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(false);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $this->findProductIdentifierService
            ->expects($this->never())
            ->method('list');

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/product-identifiers');

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'This action is unauthorized.');
    }

    private function buildProductIdentifier(int $id): ProductIdentifier
    {
        return new ProductIdentifier(
            id: $id,
            tenantId: 9,
            productId: 41,
            technology: 'barcode_1d',
            value: 'ABC-123',
            variantId: null,
            isPrimary: true,
            isActive: true,
            metadata: ['source' => 'feature-test'],
        );
    }
}
