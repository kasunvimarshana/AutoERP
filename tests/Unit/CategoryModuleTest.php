<?php

namespace Tests\Unit;

use Modules\Category\Application\Contracts\CreateCategoryServiceInterface;
use Modules\Category\Application\Contracts\DeleteCategoryImageServiceInterface;
use Modules\Category\Application\Contracts\DeleteCategoryServiceInterface;
use Modules\Category\Application\Contracts\UpdateCategoryServiceInterface;
use Modules\Category\Application\Contracts\UploadCategoryImageServiceInterface;
use Modules\Category\Application\DTOs\CategoryData;
use Modules\Category\Application\DTOs\CategoryImageData;
use Modules\Category\Application\Services\CreateCategoryService;
use Modules\Category\Application\Services\DeleteCategoryImageService;
use Modules\Category\Application\Services\DeleteCategoryService;
use Modules\Category\Application\Services\UpdateCategoryService;
use Modules\Category\Application\Services\UploadCategoryImageService;
use Modules\Category\Application\UseCases\CreateCategory;
use Modules\Category\Application\UseCases\DeleteCategory;
use Modules\Category\Application\UseCases\GetCategory;
use Modules\Category\Application\UseCases\ListCategories;
use Modules\Category\Application\UseCases\UpdateCategory;
use Modules\Category\Domain\Entities\Category;
use Modules\Category\Domain\Entities\CategoryImage;
use Modules\Category\Domain\Events\CategoryCreated;
use Modules\Category\Domain\Events\CategoryDeleted;
use Modules\Category\Domain\Events\CategoryUpdated;
use Modules\Category\Domain\Exceptions\CategoryImageNotFoundException;
use Modules\Category\Domain\Exceptions\CategoryNotFoundException;
use Modules\Category\Domain\RepositoryInterfaces\CategoryImageRepositoryInterface;
use Modules\Category\Domain\RepositoryInterfaces\CategoryRepositoryInterface;
use Modules\Category\Infrastructure\Http\Controllers\CategoryController;
use Modules\Category\Infrastructure\Http\Controllers\CategoryImageController;
use Modules\Category\Infrastructure\Http\Requests\StoreCategoryRequest;
use Modules\Category\Infrastructure\Http\Requests\UpdateCategoryRequest;
use Modules\Category\Infrastructure\Http\Requests\UploadCategoryImageRequest;
use Modules\Category\Infrastructure\Http\Resources\CategoryCollection;
use Modules\Category\Infrastructure\Http\Resources\CategoryImageResource;
use Modules\Category\Infrastructure\Http\Resources\CategoryResource;
use Modules\Category\Infrastructure\Persistence\Eloquent\Models\CategoryImageModel;
use Modules\Category\Infrastructure\Persistence\Eloquent\Models\CategoryModel;
use Modules\Category\Infrastructure\Persistence\Eloquent\Repositories\EloquentCategoryImageRepository;
use Modules\Category\Infrastructure\Persistence\Eloquent\Repositories\EloquentCategoryRepository;
use Modules\Category\Infrastructure\Providers\CategoryServiceProvider;
use PHPUnit\Framework\TestCase;

class CategoryModuleTest extends TestCase
{
    // ── Domain Entities ───────────────────────────────────────────────────────

    public function test_category_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(Category::class));
    }

    public function test_category_image_entity_class_exists(): void
    {
        $this->assertTrue(class_exists(CategoryImage::class));
    }

    public function test_category_entity_can_be_constructed(): void
    {
        $category = new Category(
            tenantId: 1,
            name: 'Electronics',
            slug: 'electronics',
        );

        $this->assertSame(1, $category->getTenantId());
        $this->assertSame('Electronics', $category->getName());
        $this->assertSame('electronics', $category->getSlug());
        $this->assertSame('active', $category->getStatus());
        $this->assertNull($category->getDescription());
        $this->assertNull($category->getParentId());
        $this->assertSame(0, $category->getDepth());
        $this->assertSame('', $category->getPath());
        $this->assertNull($category->getAttributes());
        $this->assertNull($category->getMetadata());
        $this->assertNull($category->getImage());
        $this->assertNull($category->getId());
    }

    public function test_category_entity_with_all_fields(): void
    {
        $category = new Category(
            tenantId: 2,
            name: 'Laptops',
            slug: 'laptops',
            description: 'Laptop computers',
            parentId: 1,
            depth: 1,
            path: 'electronics/laptops',
            status: 'inactive',
            attributes: ['type' => 'computing'],
            metadata: ['source' => 'import'],
            id: 42,
        );

        $this->assertSame(42, $category->getId());
        $this->assertSame(2, $category->getTenantId());
        $this->assertSame('Laptops', $category->getName());
        $this->assertSame('laptops', $category->getSlug());
        $this->assertSame('Laptop computers', $category->getDescription());
        $this->assertSame(1, $category->getParentId());
        $this->assertSame(1, $category->getDepth());
        $this->assertSame('electronics/laptops', $category->getPath());
        $this->assertSame('inactive', $category->getStatus());
        $this->assertSame(['type' => 'computing'], $category->getAttributes());
        $this->assertSame(['source' => 'import'], $category->getMetadata());
    }

    public function test_category_entity_is_root_when_no_parent(): void
    {
        $root = new Category(tenantId: 1, name: 'Root', slug: 'root');
        $this->assertTrue($root->isRoot());

        $child = new Category(tenantId: 1, name: 'Child', slug: 'child', parentId: 1);
        $this->assertFalse($child->isRoot());
    }

    public function test_category_entity_update_details(): void
    {
        $category = new Category(tenantId: 1, name: 'Old Name', slug: 'old-name');

        $category->updateDetails(
            name: 'New Name',
            slug: 'new-name',
            description: 'Updated description',
            parentId: 5,
            path: 'parent/new-name',
            depth: 1,
            attributes: ['size' => 'large'],
            metadata: ['updated' => true],
        );

        $this->assertSame('New Name', $category->getName());
        $this->assertSame('new-name', $category->getSlug());
        $this->assertSame('Updated description', $category->getDescription());
        $this->assertSame(5, $category->getParentId());
        $this->assertSame('parent/new-name', $category->getPath());
        $this->assertSame(1, $category->getDepth());
        $this->assertSame(['size' => 'large'], $category->getAttributes());
        $this->assertSame(['updated' => true], $category->getMetadata());
    }

    public function test_category_entity_activate_deactivate(): void
    {
        $category = new Category(tenantId: 1, name: 'Test', slug: 'test', status: 'inactive');
        $this->assertFalse($category->isActive());

        $category->activate();
        $this->assertTrue($category->isActive());
        $this->assertSame('active', $category->getStatus());

        $category->deactivate();
        $this->assertFalse($category->isActive());
        $this->assertSame('inactive', $category->getStatus());
    }

    public function test_category_entity_set_image(): void
    {
        $category = new Category(tenantId: 1, name: 'Test', slug: 'test');
        $this->assertNull($category->getImage());

        $image = new CategoryImage(
            tenantId: 1,
            categoryId: 1,
            uuid: 'test-uuid',
            name: 'electronics.png',
            filePath: 'categories/1/electronics.png',
            mimeType: 'image/png',
            size: 1024,
        );

        $category->setImage($image);
        $this->assertSame($image, $category->getImage());

        $category->setImage(null);
        $this->assertNull($category->getImage());
    }

    public function test_category_entity_children_collection(): void
    {
        $parent = new Category(tenantId: 1, name: 'Parent', slug: 'parent', id: 1);
        $this->assertFalse($parent->hasChildren());
        $this->assertTrue($parent->getChildren()->isEmpty());

        $child = new Category(tenantId: 1, name: 'Child', slug: 'child', parentId: 1);
        $parent->addChild($child);

        $this->assertTrue($parent->hasChildren());
        $this->assertCount(1, $parent->getChildren());
    }

    public function test_category_entity_set_children(): void
    {
        $parent = new Category(tenantId: 1, name: 'Parent', slug: 'parent');
        $child1 = new Category(tenantId: 1, name: 'Child1', slug: 'child1', parentId: 1);
        $child2 = new Category(tenantId: 1, name: 'Child2', slug: 'child2', parentId: 1);

        $parent->setChildren(collect([$child1, $child2]));

        $this->assertCount(2, $parent->getChildren());
    }

    public function test_category_image_entity_can_be_constructed(): void
    {
        $image = new CategoryImage(
            tenantId: 1,
            categoryId: 5,
            uuid: 'abc-uuid',
            name: 'image.png',
            filePath: 'categories/5/image.png',
            mimeType: 'image/png',
            size: 2048,
            metadata: ['alt' => 'Category image'],
            id: 10,
        );

        $this->assertSame(10, $image->getId());
        $this->assertSame(1, $image->getTenantId());
        $this->assertSame(5, $image->getCategoryId());
        $this->assertSame('abc-uuid', $image->getUuid());
        $this->assertSame('image.png', $image->getName());
        $this->assertSame('categories/5/image.png', $image->getFilePath());
        $this->assertSame('image/png', $image->getMimeType());
        $this->assertSame(2048, $image->getSize());
        $this->assertSame(['alt' => 'Category image'], $image->getMetadata());
    }

    // ── Domain Events ─────────────────────────────────────────────────────────

    public function test_all_category_event_classes_exist(): void
    {
        $this->assertTrue(class_exists(CategoryCreated::class));
        $this->assertTrue(class_exists(CategoryUpdated::class));
        $this->assertTrue(class_exists(CategoryDeleted::class));
    }

    public function test_category_created_event_can_be_instantiated(): void
    {
        $category = new Category(tenantId: 1, name: 'Test', slug: 'test', id: 1);
        $event = new CategoryCreated($category);

        $this->assertSame($category, $event->category);
        $this->assertSame(1, $event->tenantId);
    }

    public function test_category_updated_event_can_be_instantiated(): void
    {
        $category = new Category(tenantId: 2, name: 'Updated', slug: 'updated', id: 3);
        $event = new CategoryUpdated($category);

        $this->assertSame($category, $event->category);
        $this->assertSame(2, $event->tenantId);
    }

    public function test_category_deleted_event_can_be_instantiated(): void
    {
        $event = new CategoryDeleted(categoryId: 7, tenantId: 3);

        $this->assertSame(7, $event->categoryId);
        $this->assertSame(3, $event->tenantId);
    }

    public function test_category_created_event_broadcast_with(): void
    {
        $category = new Category(tenantId: 1, name: 'Electronics', slug: 'electronics', status: 'active', id: 1);
        $event = new CategoryCreated($category);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('id', $payload);
        $this->assertArrayHasKey('name', $payload);
        $this->assertArrayHasKey('slug', $payload);
        $this->assertArrayHasKey('parent_id', $payload);
        $this->assertArrayHasKey('status', $payload);
        $this->assertArrayHasKey('tenantId', $payload);
    }

    // ── Domain Exceptions ─────────────────────────────────────────────────────

    public function test_category_exception_classes_exist(): void
    {
        $this->assertTrue(class_exists(CategoryNotFoundException::class));
        $this->assertTrue(class_exists(CategoryImageNotFoundException::class));
    }

    public function test_category_not_found_exception_message(): void
    {
        $e = new CategoryNotFoundException(42);
        $this->assertStringContainsString('Category', $e->getMessage());
        $this->assertStringContainsString('42', $e->getMessage());
    }

    public function test_category_image_not_found_exception_message(): void
    {
        $e = new CategoryImageNotFoundException(99);
        $this->assertStringContainsString('CategoryImage', $e->getMessage());
        $this->assertStringContainsString('99', $e->getMessage());
    }

    // ── Domain Repository Interfaces ─────────────────────────────────────────

    public function test_category_repository_interfaces_exist(): void
    {
        $this->assertTrue(interface_exists(CategoryRepositoryInterface::class));
        $this->assertTrue(interface_exists(CategoryImageRepositoryInterface::class));
    }

    public function test_category_repository_interface_has_required_methods(): void
    {
        $reflection = new \ReflectionClass(CategoryRepositoryInterface::class);
        $this->assertTrue($reflection->hasMethod('findBySlug'));
        $this->assertTrue($reflection->hasMethod('findByTenant'));
        $this->assertTrue($reflection->hasMethod('findChildren'));
        $this->assertTrue($reflection->hasMethod('findRoots'));
        $this->assertTrue($reflection->hasMethod('getTree'));
        $this->assertTrue($reflection->hasMethod('getDescendants'));
        $this->assertTrue($reflection->hasMethod('save'));
    }

    public function test_category_image_repository_interface_has_required_methods(): void
    {
        $reflection = new \ReflectionClass(CategoryImageRepositoryInterface::class);
        $this->assertTrue($reflection->hasMethod('findByUuid'));
        $this->assertTrue($reflection->hasMethod('findByCategory'));
        $this->assertTrue($reflection->hasMethod('save'));
        $this->assertTrue($reflection->hasMethod('deleteByCategory'));
    }

    // ── Application DTOs ─────────────────────────────────────────────────────

    public function test_category_dto_classes_exist(): void
    {
        $this->assertTrue(class_exists(CategoryData::class));
        $this->assertTrue(class_exists(CategoryImageData::class));
    }

    public function test_category_data_dto_from_array(): void
    {
        $dto = CategoryData::fromArray([
            'tenant_id'   => 1,
            'name'        => 'Electronics',
            'slug'        => 'electronics',
            'description' => 'Electronic products',
            'parent_id'   => null,
            'status'      => 'active',
        ]);

        $this->assertSame(1, $dto->tenant_id);
        $this->assertSame('Electronics', $dto->name);
        $this->assertSame('electronics', $dto->slug);
        $this->assertSame('Electronic products', $dto->description);
        $this->assertNull($dto->parent_id);
        $this->assertSame('active', $dto->status);
    }

    public function test_category_data_dto_with_parent(): void
    {
        $dto = CategoryData::fromArray([
            'tenant_id' => 1,
            'name'      => 'Laptops',
            'slug'      => 'laptops',
            'parent_id' => 5,
            'status'    => 'active',
        ]);

        $this->assertSame(5, $dto->parent_id);
    }

    public function test_category_data_dto_defaults(): void
    {
        $dto = new CategoryData;
        $this->assertSame('active', $dto->status);
    }

    public function test_category_data_dto_to_array(): void
    {
        $dto = CategoryData::fromArray([
            'tenant_id' => 1,
            'name'      => 'Test',
            'slug'      => 'test',
        ]);

        $array = $dto->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('tenant_id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('slug', $array);
        $this->assertArrayHasKey('parent_id', $array);
        $this->assertArrayHasKey('status', $array);
    }

    // ── Application Service Contracts ─────────────────────────────────────────

    public function test_all_category_service_interfaces_exist(): void
    {
        $this->assertTrue(interface_exists(CreateCategoryServiceInterface::class));
        $this->assertTrue(interface_exists(UpdateCategoryServiceInterface::class));
        $this->assertTrue(interface_exists(DeleteCategoryServiceInterface::class));
        $this->assertTrue(interface_exists(UploadCategoryImageServiceInterface::class));
        $this->assertTrue(interface_exists(DeleteCategoryImageServiceInterface::class));
    }

    // ── Application Services ──────────────────────────────────────────────────

    public function test_all_category_service_implementations_exist(): void
    {
        $this->assertTrue(class_exists(CreateCategoryService::class));
        $this->assertTrue(class_exists(UpdateCategoryService::class));
        $this->assertTrue(class_exists(DeleteCategoryService::class));
        $this->assertTrue(class_exists(UploadCategoryImageService::class));
        $this->assertTrue(class_exists(DeleteCategoryImageService::class));
    }

    public function test_category_service_implementations_implement_their_interfaces(): void
    {
        $this->assertTrue(
            is_subclass_of(CreateCategoryService::class, CreateCategoryServiceInterface::class),
            'CreateCategoryService must implement CreateCategoryServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(UpdateCategoryService::class, UpdateCategoryServiceInterface::class),
            'UpdateCategoryService must implement UpdateCategoryServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(DeleteCategoryService::class, DeleteCategoryServiceInterface::class),
            'DeleteCategoryService must implement DeleteCategoryServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(UploadCategoryImageService::class, UploadCategoryImageServiceInterface::class),
            'UploadCategoryImageService must implement UploadCategoryImageServiceInterface.'
        );
        $this->assertTrue(
            is_subclass_of(DeleteCategoryImageService::class, DeleteCategoryImageServiceInterface::class),
            'DeleteCategoryImageService must implement DeleteCategoryImageServiceInterface.'
        );
    }

    // ── Application Use Cases ─────────────────────────────────────────────────

    public function test_all_category_use_case_classes_exist(): void
    {
        $this->assertTrue(class_exists(CreateCategory::class));
        $this->assertTrue(class_exists(UpdateCategory::class));
        $this->assertTrue(class_exists(DeleteCategory::class));
        $this->assertTrue(class_exists(GetCategory::class));
        $this->assertTrue(class_exists(ListCategories::class));
    }

    // ── Infrastructure – Models ───────────────────────────────────────────────

    public function test_category_eloquent_model_classes_exist(): void
    {
        $this->assertTrue(class_exists(CategoryModel::class));
        $this->assertTrue(class_exists(CategoryImageModel::class));
    }

    public function test_category_model_has_expected_fillable(): void
    {
        $model = new CategoryModel;
        $fillable = $model->getFillable();

        $this->assertContains('tenant_id', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('slug', $fillable);
        $this->assertContains('parent_id', $fillable);
        $this->assertContains('depth', $fillable);
        $this->assertContains('path', $fillable);
        $this->assertContains('status', $fillable);
    }

    public function test_category_image_model_has_expected_fillable(): void
    {
        $model = new CategoryImageModel;
        $fillable = $model->getFillable();

        $this->assertContains('tenant_id', $fillable);
        $this->assertContains('category_id', $fillable);
        $this->assertContains('uuid', $fillable);
        $this->assertContains('file_path', $fillable);
        $this->assertContains('mime_type', $fillable);
    }

    // ── Infrastructure – Repositories ─────────────────────────────────────────

    public function test_category_eloquent_repositories_exist(): void
    {
        $this->assertTrue(class_exists(EloquentCategoryRepository::class));
        $this->assertTrue(class_exists(EloquentCategoryImageRepository::class));
    }

    public function test_category_eloquent_repositories_implement_interfaces(): void
    {
        $this->assertTrue(
            is_subclass_of(EloquentCategoryRepository::class, CategoryRepositoryInterface::class),
            'EloquentCategoryRepository must implement CategoryRepositoryInterface.'
        );
        $this->assertTrue(
            is_subclass_of(EloquentCategoryImageRepository::class, CategoryImageRepositoryInterface::class),
            'EloquentCategoryImageRepository must implement CategoryImageRepositoryInterface.'
        );
    }

    // ── Infrastructure – HTTP ─────────────────────────────────────────────────

    public function test_category_controller_classes_exist(): void
    {
        $this->assertTrue(class_exists(CategoryController::class));
        $this->assertTrue(class_exists(CategoryImageController::class));
    }

    public function test_category_form_request_classes_exist(): void
    {
        $this->assertTrue(class_exists(StoreCategoryRequest::class));
        $this->assertTrue(class_exists(UpdateCategoryRequest::class));
        $this->assertTrue(class_exists(UploadCategoryImageRequest::class));
    }

    public function test_category_resource_classes_exist(): void
    {
        $this->assertTrue(class_exists(CategoryResource::class));
        $this->assertTrue(class_exists(CategoryCollection::class));
        $this->assertTrue(class_exists(CategoryImageResource::class));
    }

    // ── Infrastructure – Provider ─────────────────────────────────────────────

    public function test_category_service_provider_class_exists(): void
    {
        $this->assertTrue(class_exists(CategoryServiceProvider::class));
    }

    // ── Domain behaviour: timestamps ─────────────────────────────────────────

    public function test_category_entity_timestamps_are_set_on_construction(): void
    {
        $before = new \DateTimeImmutable;
        $category = new Category(tenantId: 1, name: 'Test', slug: 'test');
        $after = new \DateTimeImmutable;

        $this->assertGreaterThanOrEqual($before->getTimestamp(), $category->getCreatedAt()->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $category->getCreatedAt()->getTimestamp());
    }

    public function test_category_entity_updated_at_changes_on_update_details(): void
    {
        $category = new Category(tenantId: 1, name: 'Old', slug: 'old');
        $originalUpdatedAt = $category->getUpdatedAt();

        usleep(1000);

        $category->updateDetails('New', 'new', null, null, 'new', 0, null, null);

        $this->assertGreaterThanOrEqual(
            $originalUpdatedAt->getTimestamp(),
            $category->getUpdatedAt()->getTimestamp()
        );
    }

    public function test_category_entity_updated_at_changes_on_activate(): void
    {
        $category = new Category(tenantId: 1, name: 'Test', slug: 'test', status: 'inactive');
        $originalUpdatedAt = $category->getUpdatedAt();

        usleep(1000);
        $category->activate();

        $this->assertGreaterThanOrEqual(
            $originalUpdatedAt->getTimestamp(),
            $category->getUpdatedAt()->getTimestamp()
        );
    }

    // ── Store request rules ────────────────────────────────────────────────────

    public function test_store_category_request_has_required_rules(): void
    {
        $request = new StoreCategoryRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('tenant_id', $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('slug', $rules);
        $this->assertArrayHasKey('parent_id', $rules);
        $this->assertArrayHasKey('status', $rules);
    }

    public function test_update_category_request_has_required_rules(): void
    {
        $request = new UpdateCategoryRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('slug', $rules);
        $this->assertArrayHasKey('parent_id', $rules);
        $this->assertArrayHasKey('status', $rules);
    }

    public function test_upload_category_image_request_has_required_rules(): void
    {
        $request = new UploadCategoryImageRequest;
        $rules = $request->rules();

        $this->assertArrayHasKey('file', $rules);
    }

    // ── Hierarchical behaviour ─────────────────────────────────────────────────

    public function test_category_depth_zero_for_root(): void
    {
        $root = new Category(
            tenantId: 1,
            name: 'Root',
            slug: 'root',
            parentId: null,
            depth: 0,
            path: 'root',
        );

        $this->assertSame(0, $root->getDepth());
        $this->assertTrue($root->isRoot());
    }

    public function test_category_depth_increments_for_child(): void
    {
        $child = new Category(
            tenantId: 1,
            name: 'Child',
            slug: 'child',
            parentId: 1,
            depth: 1,
            path: 'root/child',
        );

        $this->assertSame(1, $child->getDepth());
        $this->assertFalse($child->isRoot());
        $this->assertSame('root/child', $child->getPath());
    }

    public function test_category_path_builds_hierarchy(): void
    {
        $grandchild = new Category(
            tenantId: 1,
            name: 'Grandchild',
            slug: 'grandchild',
            parentId: 2,
            depth: 2,
            path: 'root/child/grandchild',
        );

        $this->assertSame(2, $grandchild->getDepth());
        $this->assertSame('root/child/grandchild', $grandchild->getPath());
    }

    public function test_category_image_optional(): void
    {
        // Category can exist without an image
        $category = new Category(tenantId: 1, name: 'No Image', slug: 'no-image');
        $this->assertNull($category->getImage());

        // Category can also have an image
        $image = new CategoryImage(
            tenantId: 1,
            categoryId: 1,
            uuid: 'uuid-123',
            name: 'img.jpg',
            filePath: 'categories/1/img.jpg',
            mimeType: 'image/jpeg',
            size: 512,
        );
        $category->setImage($image);
        $this->assertNotNull($category->getImage());
        $this->assertSame('uuid-123', $category->getImage()->getUuid());
    }
}
