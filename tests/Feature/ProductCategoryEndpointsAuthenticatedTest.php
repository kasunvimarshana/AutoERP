<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\PresenceVerifierInterface;
use Laravel\Passport\Passport;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
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
    /** @var CreateProductCategoryServiceInterface&MockObject */
    private CreateProductCategoryServiceInterface $createProductCategoryService;

    /** @var UpdateProductCategoryServiceInterface&MockObject */
    private UpdateProductCategoryServiceInterface $updateProductCategoryService;

    /** @var FindProductCategoryServiceInterface&MockObject */
    private FindProductCategoryServiceInterface $findProductCategoryService;

    /** @var FileStorageServiceInterface&MockObject */
    private FileStorageServiceInterface $fileStorageService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->findProductCategoryService = $this->createMock(FindProductCategoryServiceInterface::class);
        $this->createProductCategoryService = $this->createMock(CreateProductCategoryServiceInterface::class);
        $this->updateProductCategoryService = $this->createMock(UpdateProductCategoryServiceInterface::class);
        $this->fileStorageService = $this->createMock(FileStorageServiceInterface::class);

        $this->app->instance(FindProductCategoryServiceInterface::class, $this->findProductCategoryService);
        $this->app->instance(CreateProductCategoryServiceInterface::class, $this->createProductCategoryService);
        $this->app->instance(UpdateProductCategoryServiceInterface::class, $this->updateProductCategoryService);
        $this->app->instance(FileStorageServiceInterface::class, $this->fileStorageService);

        $this->app->instance(DeleteProductCategoryServiceInterface::class, $this->createMock(DeleteProductCategoryServiceInterface::class));

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
            'id' => 231,
            'tenant_id' => 9,
            'email' => 'product.category.test@example.com',
            'password' => 'secret',
            'first_name' => 'Product',
            'last_name' => 'CategoryTester',
        ]);
        $user->setAttribute('id', 231);
        $user->setAttribute('tenant_id', 9);

        $this->actingAs($user, 'api');
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

    public function test_authenticated_store_with_image_upload_passes_stored_path_to_service(): void
    {
        $this->fileStorageService
            ->expects($this->once())
            ->method('storeFile')
            ->willReturn('product-categories/9/category.jpg');

        $this->createProductCategoryService
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (array $payload): bool {
                return ($payload['image_path'] ?? null) === 'product-categories/9/category.jpg'
                    && (int) ($payload['tenant_id'] ?? 0) === 9
                    && ($payload['name'] ?? null) === 'Electronics';
            }))
            ->willReturn($this->buildProductCategory(id: 83));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->post('/api/product-categories', [
                'tenant_id' => 9,
                'name' => 'Electronics',
                'slug' => 'electronics',
                'image_path' => UploadedFile::fake()->create('category.jpg', 10, 'image/jpeg'),
            ]);

        $response->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 83);
    }

    public function test_authenticated_update_with_image_upload_passes_stored_path_to_service(): void
    {
        $this->findProductCategoryService
            ->expects($this->once())
            ->method('find')
            ->with(82)
            ->willReturn($this->buildProductCategory(id: 82, imagePath: 'product-categories/9/old.jpg'));

        $this->fileStorageService
            ->expects($this->once())
            ->method('storeFile')
            ->willReturn('product-categories/9/category-updated.jpg');

        $this->fileStorageService
            ->expects($this->once())
            ->method('exists')
            ->with('product-categories/9/old.jpg')
            ->willReturn(true);

        $this->fileStorageService
            ->expects($this->once())
            ->method('delete')
            ->with('product-categories/9/old.jpg')
            ->willReturn(true);

        $this->updateProductCategoryService
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (array $payload): bool {
                return ($payload['id'] ?? null) === 82
                    && ($payload['image_path'] ?? null) === 'product-categories/9/category-updated.jpg';
            }))
            ->willReturn($this->buildProductCategory(id: 82));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->put('/api/product-categories/82', [
                'tenant_id' => 9,
                'name' => 'Electronics',
                'slug' => 'electronics',
                'image_path' => UploadedFile::fake()->create('category-updated.jpg', 10, 'image/jpeg'),
            ]);

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 82);
    }

    private function buildProductCategory(int $id, ?string $imagePath = null): ProductCategory
    {
        return new ProductCategory(
            id: $id,
            tenantId: 9,
            name: 'Electronics',
            imagePath: $imagePath,
            slug: 'electronics',
            code: 'ELC',
            isActive: true,
            metadata: ['source' => 'feature-test'],
        );
    }
}
