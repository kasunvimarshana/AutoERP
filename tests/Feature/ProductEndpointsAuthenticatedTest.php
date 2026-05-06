<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\PresenceVerifierInterface;
use Laravel\Passport\Passport;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
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
    /** @var CreateProductServiceInterface&MockObject */
    private CreateProductServiceInterface $createProductService;

    /** @var UpdateProductServiceInterface&MockObject */
    private UpdateProductServiceInterface $updateProductService;

    /** @var FindProductServiceInterface&MockObject */
    private FindProductServiceInterface $findProductService;

    /** @var FileStorageServiceInterface&MockObject */
    private FileStorageServiceInterface $fileStorageService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->findProductService = $this->createMock(FindProductServiceInterface::class);
        $this->createProductService = $this->createMock(CreateProductServiceInterface::class);
        $this->updateProductService = $this->createMock(UpdateProductServiceInterface::class);
        $this->fileStorageService = $this->createMock(FileStorageServiceInterface::class);

        $this->app->instance(FindProductServiceInterface::class, $this->findProductService);
        $this->app->instance(CreateProductServiceInterface::class, $this->createProductService);
        $this->app->instance(UpdateProductServiceInterface::class, $this->updateProductService);
        $this->app->instance(FileStorageServiceInterface::class, $this->fileStorageService);

        $this->app->instance(DeleteProductServiceInterface::class, $this->createMock(DeleteProductServiceInterface::class));

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient->method('getConfig')->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);

        $tenantConfigManager = $this->createMock(TenantConfigManagerInterface::class);
        $this->app->instance(TenantConfigManagerInterface::class, $tenantConfigManager);

        $presenceVerifier = $this->createMock(PresenceVerifierInterface::class);
        $presenceVerifier->method('getCount')->willReturnCallback(
            static function (
                string $collection,
                string $column,
                mixed $value,
                mixed $excludeId = null,
                mixed $idColumn = null,
                array $extra = []
            ): int {
                if ($collection === 'products' && in_array($column, ['slug', 'sku'], true)) {
                    return 0;
                }

                return 1;
            }
        );
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app->instance(PresenceVerifierInterface::class, $presenceVerifier);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

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

        $this->actingAs($user, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));
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
                '-created_at',
                null
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

    public function test_authenticated_store_with_image_upload_passes_stored_path_to_service(): void
    {
        $this->fileStorageService
            ->expects($this->once())
            ->method('storeFile')
            ->willReturn('products/9/sample.jpg');

        $this->createProductService
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (array $payload): bool {
                return ($payload['image_path'] ?? null) === 'products/9/sample.jpg'
                    && (int) ($payload['tenant_id'] ?? 0) === 9
                    && ($payload['name'] ?? null) === 'Widget';
            }))
            ->willReturn($this->buildProduct(id: 91));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->post('/api/products', [
                'tenant_id' => 9,
                'type' => 'physical',
                'name' => 'Widget',
                'slug' => 'widget',
                'base_uom_id' => 1,
                'image_path' => UploadedFile::fake()->create('sample.jpg', 10, 'image/jpeg'),
            ]);

        $response->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 91);
    }

    public function test_authenticated_update_with_image_upload_passes_stored_path_to_service(): void
    {
        $this->findProductService
            ->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($this->buildProduct(id: 42, imagePath: 'products/9/old.jpg'));

        $this->fileStorageService
            ->expects($this->once())
            ->method('storeFile')
            ->willReturn('products/9/updated.jpg');

        $this->fileStorageService
            ->expects($this->once())
            ->method('exists')
            ->with('products/9/old.jpg')
            ->willReturn(true);

        $this->fileStorageService
            ->expects($this->once())
            ->method('delete')
            ->with('products/9/old.jpg')
            ->willReturn(true);

        $this->updateProductService
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (array $payload): bool {
                return ($payload['id'] ?? null) === 42
                    && ($payload['image_path'] ?? null) === 'products/9/updated.jpg';
            }))
            ->willReturn($this->buildProduct(id: 42));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->put('/api/products/42', [
                'tenant_id' => 9,
                'type' => 'physical',
                'name' => 'Widget',
                'slug' => 'widget',
                'base_uom_id' => 1,
                'row_version' => 1,
                'image_path' => UploadedFile::fake()->create('updated.jpg', 10, 'image/jpeg'),
            ]);

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 42);
    }

    public function test_authenticated_store_rejects_serial_tracked_with_batch_or_lot(): void
    {
        $this->createProductService
            ->expects($this->never())
            ->method('execute');

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/products', [
                'tenant_id' => 9,
                'type' => 'physical',
                'name' => 'Widget',
                'slug' => 'widget',
                'base_uom_id' => 1,
                'is_serial_tracked' => true,
                'is_batch_tracked' => true,
            ]);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['is_serial_tracked']);
    }

    public function test_authenticated_store_requires_standard_cost_for_standard_valuation(): void
    {
        $this->createProductService
            ->expects($this->never())
            ->method('execute');

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/products', [
                'tenant_id' => 9,
                'type' => 'physical',
                'name' => 'Widget',
                'slug' => 'widget',
                'base_uom_id' => 1,
                'valuation_method' => 'standard',
            ]);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['standard_cost']);
    }

    public function test_authenticated_update_rejects_serial_tracked_with_lot_tracking(): void
    {
        $this->findProductService
            ->expects($this->never())
            ->method('find');

        $this->updateProductService
            ->expects($this->never())
            ->method('execute');

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->putJson('/api/products/42', [
                'tenant_id' => 9,
                'type' => 'physical',
                'name' => 'Widget',
                'slug' => 'widget',
                'base_uom_id' => 1,
                'is_serial_tracked' => true,
                'is_lot_tracked' => true,
            ]);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['is_serial_tracked']);
    }

    private function buildProduct(int $id, ?string $imagePath = null): Product
    {
        return new Product(
            id: $id,
            tenantId: 9,
            type: 'physical',
            name: 'Widget',
            imagePath: $imagePath,
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
