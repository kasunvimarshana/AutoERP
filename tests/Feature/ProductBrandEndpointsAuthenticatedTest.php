<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\PresenceVerifierInterface;
use Laravel\Passport\Passport;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Product\Application\Contracts\CreateProductBrandServiceInterface;
use Modules\Product\Application\Contracts\DeleteProductBrandServiceInterface;
use Modules\Product\Application\Contracts\FindProductBrandServiceInterface;
use Modules\Product\Application\Contracts\UpdateProductBrandServiceInterface;
use Modules\Product\Domain\Entities\ProductBrand;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class ProductBrandEndpointsAuthenticatedTest extends TestCase
{
    /** @var CreateProductBrandServiceInterface&MockObject */
    private CreateProductBrandServiceInterface $createProductBrandService;

    /** @var UpdateProductBrandServiceInterface&MockObject */
    private UpdateProductBrandServiceInterface $updateProductBrandService;

    /** @var FindProductBrandServiceInterface&MockObject */
    private FindProductBrandServiceInterface $findProductBrandService;

    /** @var FileStorageServiceInterface&MockObject */
    private FileStorageServiceInterface $fileStorageService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->findProductBrandService = $this->createMock(FindProductBrandServiceInterface::class);
        $this->createProductBrandService = $this->createMock(CreateProductBrandServiceInterface::class);
        $this->updateProductBrandService = $this->createMock(UpdateProductBrandServiceInterface::class);
        $this->fileStorageService = $this->createMock(FileStorageServiceInterface::class);

        $this->app->instance(FindProductBrandServiceInterface::class, $this->findProductBrandService);
        $this->app->instance(CreateProductBrandServiceInterface::class, $this->createProductBrandService);
        $this->app->instance(UpdateProductBrandServiceInterface::class, $this->updateProductBrandService);
        $this->app->instance(FileStorageServiceInterface::class, $this->fileStorageService);

        $this->app->instance(DeleteProductBrandServiceInterface::class, $this->createMock(DeleteProductBrandServiceInterface::class));

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
            'id' => 221,
            'tenant_id' => 9,
            'email' => 'product.brand.test@example.com',
            'password' => 'secret',
            'first_name' => 'Product',
            'last_name' => 'BrandTester',
        ]);
        $user->setAttribute('id', 221);
        $user->setAttribute('tenant_id', 9);

        $this->actingAs($user, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));
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

        $response->assertStatus(HttpResponse::HTTP_OK)
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

        $response->assertStatus(HttpResponse::HTTP_OK)
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

        $response->assertStatus(HttpResponse::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'This action is unauthorized.');
    }

    public function test_authenticated_store_with_image_upload_passes_stored_path_to_service(): void
    {
        $this->fileStorageService
            ->expects($this->once())
            ->method('storeFile')
            ->willReturn('product-brands/9/brand.jpg');

        $this->createProductBrandService
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (array $payload): bool {
                return ($payload['image_path'] ?? null) === 'product-brands/9/brand.jpg'
                    && (int) ($payload['tenant_id'] ?? 0) === 9
                    && ($payload['name'] ?? null) === 'Acme';
            }))
            ->willReturn($this->buildProductBrand(id: 73));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->post('/api/product-brands', [
                'tenant_id' => 9,
                'name' => 'Acme',
                'slug' => 'acme',
                'image_path' => UploadedFile::fake()->create('brand.jpg', 10, 'image/jpeg'),
            ]);

        $response->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 73);
    }

    public function test_authenticated_update_with_image_upload_passes_stored_path_to_service(): void
    {
        $this->findProductBrandService
            ->expects($this->once())
            ->method('find')
            ->with(72)
            ->willReturn($this->buildProductBrand(id: 72, imagePath: 'product-brands/9/old.jpg'));

        $this->fileStorageService
            ->expects($this->once())
            ->method('storeFile')
            ->willReturn('product-brands/9/brand-updated.jpg');

        $this->fileStorageService
            ->expects($this->once())
            ->method('exists')
            ->with('product-brands/9/old.jpg')
            ->willReturn(true);

        $this->fileStorageService
            ->expects($this->once())
            ->method('delete')
            ->with('product-brands/9/old.jpg')
            ->willReturn(true);

        $this->updateProductBrandService
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (array $payload): bool {
                return ($payload['id'] ?? null) === 72
                    && ($payload['image_path'] ?? null) === 'product-brands/9/brand-updated.jpg';
            }))
            ->willReturn($this->buildProductBrand(id: 72));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->put('/api/product-brands/72', [
                'tenant_id' => 9,
                'name' => 'Acme',
                'slug' => 'acme',
                'image_path' => UploadedFile::fake()->create('brand-updated.jpg', 10, 'image/jpeg'),
            ]);

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 72);
    }

    private function buildProductBrand(int $id, ?string $imagePath = null): ProductBrand
    {
        return new ProductBrand(
            id: $id,
            tenantId: 9,
            name: 'Acme',
            imagePath: $imagePath,
            slug: 'acme',
            code: 'ACM',
            isActive: true,
            metadata: ['source' => 'feature-test'],
        );
    }
}
