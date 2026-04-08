<?php

namespace Tests\Unit;

use Modules\Brand\Application\Contracts\CreateBrandServiceInterface;
use Modules\Brand\Application\Contracts\DeleteBrandLogoServiceInterface;
use Modules\Brand\Application\Contracts\DeleteBrandServiceInterface;
use Modules\Brand\Application\Contracts\UpdateBrandServiceInterface;
use Modules\Brand\Application\Contracts\UploadBrandLogoServiceInterface;
use Modules\Brand\Application\DTOs\BrandData;
use Modules\Brand\Application\DTOs\BrandLogoData;
use Modules\Brand\Application\Services\CreateBrandService;
use Modules\Brand\Application\Services\DeleteBrandLogoService;
use Modules\Brand\Application\Services\DeleteBrandService;
use Modules\Brand\Application\Services\UpdateBrandService;
use Modules\Brand\Application\Services\UploadBrandLogoService;
use Modules\Brand\Application\UseCases\CreateBrand;
use Modules\Brand\Application\UseCases\DeleteBrand;
use Modules\Brand\Application\UseCases\GetBrand;
use Modules\Brand\Application\UseCases\ListBrands;
use Modules\Brand\Application\UseCases\UpdateBrand;
use Modules\Brand\Domain\Entities\Brand;
use Modules\Brand\Domain\Entities\BrandLogo;
use Modules\Brand\Domain\Events\BrandCreated;
use Modules\Brand\Domain\Events\BrandDeleted;
use Modules\Brand\Domain\Events\BrandUpdated;
use Modules\Brand\Domain\Exceptions\BrandLogoNotFoundException;
use Modules\Brand\Domain\Exceptions\BrandNotFoundException;
use Modules\Brand\Domain\RepositoryInterfaces\BrandLogoRepositoryInterface;
use Modules\Brand\Domain\RepositoryInterfaces\BrandRepositoryInterface;
use Modules\Brand\Infrastructure\Http\Controllers\BrandController;
use Modules\Brand\Infrastructure\Http\Controllers\BrandLogoController;
use Modules\Brand\Infrastructure\Http\Requests\StoreBrandRequest;
use Modules\Brand\Infrastructure\Http\Requests\UpdateBrandRequest;
use Modules\Brand\Infrastructure\Http\Requests\UploadBrandLogoRequest;
use Modules\Brand\Infrastructure\Http\Resources\BrandCollection;
use Modules\Brand\Infrastructure\Http\Resources\BrandLogoResource;
use Modules\Brand\Infrastructure\Http\Resources\BrandResource;
use Modules\Brand\Infrastructure\Persistence\Eloquent\Models\BrandLogoModel;
use Modules\Brand\Infrastructure\Persistence\Eloquent\Models\BrandModel;
use Modules\Brand\Infrastructure\Persistence\Eloquent\Repositories\EloquentBrandLogoRepository;
use Modules\Brand\Infrastructure\Persistence\Eloquent\Repositories\EloquentBrandRepository;
use Modules\Brand\Infrastructure\Providers\BrandServiceProvider;
use PHPUnit\Framework\TestCase;

class BrandModuleTest extends TestCase
{
    // ── Domain Entities ───────────────────────────────────────────────────────

    public function test_brand_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(Brand::class));
    }

    public function test_brand_logo_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(BrandLogo::class));
    }

    public function test_brand_entity_can_be_constructed(): void
    {
        $brand = new Brand(
            tenantId: 1,
            name: 'Acme Brand',
            slug: 'acme-brand',
        );

        $this->assertSame(1, $brand->getTenantId());
        $this->assertSame('Acme Brand', $brand->getName());
        $this->assertSame('acme-brand', $brand->getSlug());
        $this->assertSame('active', $brand->getStatus());
        $this->assertNull($brand->getDescription());
        $this->assertNull($brand->getWebsite());
        $this->assertNull($brand->getAttributes());
        $this->assertNull($brand->getMetadata());
        $this->assertNull($brand->getLogo());
        $this->assertNull($brand->getId());
    }

    public function test_brand_entity_with_all_fields(): void
    {
        $brand = new Brand(
            tenantId: 2,
            name: 'Test Brand',
            slug: 'test-brand',
            description: 'A test brand',
            website: 'https://test.example.com',
            status: 'inactive',
            attributes: ['color' => 'red'],
            metadata: ['source' => 'import'],
            id: 42,
        );

        $this->assertSame(42, $brand->getId());
        $this->assertSame(2, $brand->getTenantId());
        $this->assertSame('Test Brand', $brand->getName());
        $this->assertSame('test-brand', $brand->getSlug());
        $this->assertSame('A test brand', $brand->getDescription());
        $this->assertSame('https://test.example.com', $brand->getWebsite());
        $this->assertSame('inactive', $brand->getStatus());
        $this->assertSame(['color' => 'red'], $brand->getAttributes());
        $this->assertSame(['source' => 'import'], $brand->getMetadata());
    }

    public function test_brand_entity_update_details(): void
    {
        $brand = new Brand(tenantId: 1, name: 'Old Name', slug: 'old-name');

        $brand->updateDetails(
            name: 'New Name',
            slug: 'new-name',
            description: 'Updated description',
            website: 'https://new.example.com',
            attributes: ['size' => 'large'],
            metadata: ['updated' => true],
        );

        $this->assertSame('New Name', $brand->getName());
        $this->assertSame('new-name', $brand->getSlug());
        $this->assertSame('Updated description', $brand->getDescription());
        $this->assertSame('https://new.example.com', $brand->getWebsite());
        $this->assertSame(['size' => 'large'], $brand->getAttributes());
        $this->assertSame(['updated' => true], $brand->getMetadata());
    }

    public function test_brand_entity_activate_deactivate(): void
    {
        $brand = new Brand(tenantId: 1, name: 'Test', slug: 'test', status: 'inactive');
        $this->assertFalse($brand->isActive());

        $brand->activate();
        $this->assertTrue($brand->isActive());
        $this->assertSame('active', $brand->getStatus());

        $brand->deactivate();
        $this->assertFalse($brand->isActive());
        $this->assertSame('inactive', $brand->getStatus());
    }

    public function test_brand_entity_set_logo(): void
    {
        $brand = new Brand(tenantId: 1, name: 'Test', slug: 'test');
        $this->assertNull($brand->getLogo());

        $logo = new BrandLogo(
            tenantId: 1,
            brandId: 1,
            uuid: 'test-uuid',
            name: 'logo.png',
            filePath: 'brands/1/logo.png',
            mimeType: 'image/png',
            size: 1024,
        );

        $brand->setLogo($logo);
        $this->assertSame($logo, $brand->getLogo());

        $brand->setLogo(null);
        $this->assertNull($brand->getLogo());
    }

    public function test_brand_logo_entity_can_be_constructed(): void
    {
        $logo = new BrandLogo(
            tenantId: 1,
            brandId: 5,
            uuid: 'abc-uuid',
            name: 'logo.png',
            filePath: 'brands/5/logo.png',
            mimeType: 'image/png',
            size: 2048,
            metadata: ['alt' => 'Brand logo'],
            id: 10,
        );

        $this->assertSame(10, $logo->getId());
        $this->assertSame(1, $logo->getTenantId());
        $this->assertSame(5, $logo->getBrandId());
        $this->assertSame('abc-uuid', $logo->getUuid());
        $this->assertSame('logo.png', $logo->getName());
        $this->assertSame('brands/5/logo.png', $logo->getFilePath());
        $this->assertSame('image/png', $logo->getMimeType());
        $this->assertSame(2048, $logo->getSize());
        $this->assertSame(['alt' => 'Brand logo'], $logo->getMetadata());
    }

    // ── Domain Events ─────────────────────────────────────────────────────────

    public function test_all_brand_event_classes_exist(): void
    {
        $this->assertTrue(class_exists(BrandCreated::class));
        $this->assertTrue(class_exists(BrandUpdated::class));
        $this->assertTrue(class_exists(BrandDeleted::class));
    }

    public function test_brand_created_event_can_be_instantiated(): void
    {
        $brand = new Brand(tenantId: 1, name: 'Test', slug: 'test', id: 1);
        $event = new BrandCreated($brand);

        $this->assertSame($brand, $event->brand);
        $this->assertSame(1, $event->tenantId);
    }

    public function test_brand_updated_event_can_be_instantiated(): void
    {
        $brand = new Brand(tenantId: 2, name: 'Updated', slug: 'updated', id: 3);
        $event = new BrandUpdated($brand);

        $this->assertSame($brand, $event->brand);
        $this->assertSame(2, $event->tenantId);
    }

    public function test_brand_deleted_event_can_be_instantiated(): void
    {
        $event = new BrandDeleted(brandId: 7, tenantId: 3);

        $this->assertSame(7, $event->brandId);
        $this->assertSame(3, $event->tenantId);
    }

    public function test_brand_created_event_broadcast_with(): void
    {
        $brand = new Brand(tenantId: 1, name: 'My Brand', slug: 'my-brand', status: 'active', id: 1);
        $event = new BrandCreated($brand);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('name', $payload);
        $this->assertArrayHasKey('slug', $payload);
        $this->assertArrayHasKey('status', $payload);
        $this->assertArrayHasKey('tenantId', $payload);
    }

    // ── Domain Exceptions ─────────────────────────────────────────────────────

    public function test_brand_exception_classes_exist(): void
    {
        $this->assertTrue(class_exists(BrandNotFoundException::class));
        $this->assertTrue(class_exists(BrandLogoNotFoundException::class));
    }

    public function test_brand_not_found_exception_message(): void
    {
        $e = new BrandNotFoundException(42);
        $this->assertStringContainsString('Brand', $e->getMessage());
        $this->assertStringContainsString('42', $e->getMessage());
    }

    public function test_brand_logo_not_found_exception_message(): void
    {
        $e = new BrandLogoNotFoundException(99);
        $this->assertStringContainsString('BrandLogo', $e->getMessage());
        $this->assertStringContainsString('99', $e->getMessage());
    }

    // ── Domain Repository Interfaces ─────────────────────────────────────────

    public function test_brand_repository_interfaces_exist(): void
    {
        $this->assertTrue(interface_exists(BrandRepositoryInterface::class));
        $this->assertTrue(interface_exists(BrandLogoRepositoryInterface::class));
    }

    public function test_brand_repository_interface_has_required_methods(): void
    {
        $reflection = new \ReflectionClass(BrandRepositoryInterface::class);
        $this->assertTrue($reflection->hasMethod('findBySlug'));
        $this->assertTrue($reflection->hasMethod('findByTenant'));
        $this->assertTrue($reflection->hasMethod('save'));
    }

    public function test_brand_logo_repository_interface_has_required_methods(): void
    {
        $reflection = new \ReflectionClass(BrandLogoRepositoryInterface::class);
        $this->assertTrue($reflection->hasMethod('findByUuid'));
        $this->assertTrue($reflection->hasMethod('findByBrand'));
        $this->assertTrue($reflection->hasMethod('save'));
        $this->assertTrue($reflection->hasMethod('deleteByBrand'));
    }

    // ── Application DTOs ─────────────────────────────────────────────────────

    public function test_brand_dto_classes_exist(): void
    {
        $this->assertTrue(class_exists(BrandData::class));
        $this->assertTrue(class_exists(BrandLogoData::class));
    }

    public function test_brand_data_dto_from_array(): void
    {
        $dto = BrandData::fromArray([
            'tenant_id'   => 1,
            'name'        => 'Test Brand',
            'slug'        => 'test-brand',
            'description' => 'A description',
            'website'     => 'https://test.example.com',
            'status'      => 'active',
        ]);

        $this->assertSame(1, $dto->tenant_id);
        $this->assertSame('Test Brand', $dto->name);
        $this->assertSame('test-brand', $dto->slug);
        $this->assertSame('A description', $dto->description);
        $this->assertSame('https://test.example.com', $dto->website);
        $this->assertSame('active', $dto->status);
    }

    public function test_brand_data_dto_defaults(): void
    {
        $dto = new BrandData;
        $this->assertSame('active', $dto->status);
    }

    public function test_brand_data_dto_to_array(): void
    {
        $dto = BrandData::fromArray([
            'tenant_id' => 1,
            'name'      => 'Test',
            'slug'      => 'test',
        ]);

        $array = $dto->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('tenant_id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('slug', $array);
    }

    // ── Application Service Contracts ─────────────────────────────────────────

    public function test_all_brand_service_interfaces_exist(): void
    {
        $this->assertTrue(interface_exists(CreateBrandServiceInterface::class));
        $this->assertTrue(interface_exists(UpdateBrandServiceInterface::class));
        $this->assertTrue(interface_exists(DeleteBrandServiceInterface::class));
        $this->assertTrue(interface_exists(UploadBrandLogoServiceInterface::class));
        $this->assertTrue(interface_exists(DeleteBrandLogoServiceInterface::class));
    }

    // ── Application Services ──────────────────────────────────────────────────

    public function test_all_brand_service_implementations_exist(): void
    {
        $this->assertTrue(class_exists(CreateBrandService::class));
        $this->assertTrue(class_exists(UpdateBrandService::class));
        $this->assertTrue(class_exists(DeleteBrandService::class));
        $this->assertTrue(class_exists(UploadBrandLogoService::class));
        $this->assertTrue(class_exists(DeleteBrandLogoService::class));
    }

    public function test_brand_service_implementations_implement_their_interfaces(): void
    {
        $this->assertTrue(
            is_subclass_of(CreateBrandService::class, CreateBrandServiceInterface::class),
            'CreateBrandService must implement CreateBrandServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(UpdateBrandService::class, UpdateBrandServiceInterface::class),
            'UpdateBrandService must implement UpdateBrandServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(DeleteBrandService::class, DeleteBrandServiceInterface::class),
            'DeleteBrandService must implement DeleteBrandServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(UploadBrandLogoService::class, UploadBrandLogoServiceInterface::class),
            'UploadBrandLogoService must implement UploadBrandLogoServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(DeleteBrandLogoService::class, DeleteBrandLogoServiceInterface::class),
            'DeleteBrandLogoService must implement DeleteBrandLogoServiceInterface.'
        );
    }

    // ── Application Use Cases ─────────────────────────────────────────────────

    public function test_all_brand_use_case_classes_exist(): void
    {
        $this->assertTrue(class_exists(CreateBrand::class));
        $this->assertTrue(class_exists(UpdateBrand::class));
        $this->assertTrue(class_exists(DeleteBrand::class));
        $this->assertTrue(class_exists(GetBrand::class));
        $this->assertTrue(class_exists(ListBrands::class));
    }

    // ── Infrastructure – Models ───────────────────────────────────────────────

    public function test_brand_eloquent_model_classes_exist(): void
    {
        $this->assertTrue(class_exists(BrandModel::class));
        $this->assertTrue(class_exists(BrandLogoModel::class));
    }

    public function test_brand_model_has_expected_fillable(): void
    {
        $model = new BrandModel;
        $fillable = $model->getFillable();

        $this->assertContains('tenant_id', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('slug', $fillable);
        $this->assertContains('status', $fillable);
    }

    public function test_brand_logo_model_has_expected_fillable(): void
    {
        $model = new BrandLogoModel;
        $fillable = $model->getFillable();

        $this->assertContains('tenant_id', $fillable);
        $this->assertContains('brand_id', $fillable);
        $this->assertContains('uuid', $fillable);
        $this->assertContains('file_path', $fillable);
        $this->assertContains('mime_type', $fillable);
    }

    // ── Infrastructure – Repositories ─────────────────────────────────────────

    public function test_brand_eloquent_repositories_exist(): void
    {
        $this->assertTrue(class_exists(EloquentBrandRepository::class));
        $this->assertTrue(class_exists(EloquentBrandLogoRepository::class));
    }

    public function test_brand_eloquent_repositories_implement_interfaces(): void
    {
        $this->assertTrue(
            is_subclass_of(EloquentBrandRepository::class, BrandRepositoryInterface::class),
            'EloquentBrandRepository must implement BrandRepositoryInterface.'
        );
        $this->assertTrue(
            is_subclass_of(EloquentBrandLogoRepository::class, BrandLogoRepositoryInterface::class),
            'EloquentBrandLogoRepository must implement BrandLogoRepositoryInterface.'
        );
    }

    // ── Infrastructure – HTTP ─────────────────────────────────────────────────

    public function test_brand_controller_classes_exist(): void
    {
        $this->assertTrue(class_exists(BrandController::class));
        $this->assertTrue(class_exists(BrandLogoController::class));
    }

    public function test_brand_form_request_classes_exist(): void
    {
        $this->assertTrue(class_exists(StoreBrandRequest::class));
        $this->assertTrue(class_exists(UpdateBrandRequest::class));
        $this->assertTrue(class_exists(UploadBrandLogoRequest::class));
    }

    public function test_brand_resource_classes_exist(): void
    {
        $this->assertTrue(class_exists(BrandResource::class));
        $this->assertTrue(class_exists(BrandCollection::class));
        $this->assertTrue(class_exists(BrandLogoResource::class));
    }

    // ── Infrastructure – Provider ─────────────────────────────────────────────

    public function test_brand_service_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(BrandServiceProvider::class));
    }

    // ── Domain behaviour: timestamps ─────────────────────────────────────────

    public function test_brand_entity_timestamps_are_set_on_construction(): void
    {
        $before = new \DateTimeImmutable;
        $brand = new Brand(tenantId: 1, name: 'Test', slug: 'test');
        $after = new \DateTimeImmutable;

        $this->assertGreaterThanOrEqual($before->getTimestamp(), $brand->getCreatedAt()->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $brand->getCreatedAt()->getTimestamp());
    }

    public function test_brand_entity_updated_at_changes_on_update_details(): void
    {
        $brand = new Brand(tenantId: 1, name: 'Old', slug: 'old');
        $originalUpdatedAt = $brand->getUpdatedAt();

        // Small sleep to ensure different timestamp
        usleep(1000);

        $brand->updateDetails('New', 'new', null, null, null, null);

        $this->assertGreaterThanOrEqual(
            $originalUpdatedAt->getTimestamp(),
            $brand->getUpdatedAt()->getTimestamp()
        );
    }

    // ── Store request rules ────────────────────────────────────────────────────

    public function test_store_brand_request_has_required_rules(): void
    {
        $request = new StoreBrandRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('tenant_id', $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('slug', $rules);
        $this->assertArrayHasKey('status', $rules);
    }

    public function test_update_brand_request_has_required_rules(): void
    {
        $request = new UpdateBrandRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('slug', $rules);
        $this->assertArrayHasKey('status', $rules);
    }

    public function test_upload_brand_logo_request_has_required_rules(): void
    {
        $request = new UploadBrandLogoRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('file', $rules);
    }
}
