<?php declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Product\Application\Contracts\ManageProductCategoryServiceInterface;
use Modules\Product\Domain\Entities\ProductCategory;
use Modules\Product\Domain\RepositoryInterfaces\ProductCategoryRepositoryInterface;

class ManageProductCategoryService implements ManageProductCategoryServiceInterface
{
    public function __construct(
        private readonly ProductCategoryRepositoryInterface $categories,
    ) {}

    public function create(array $data): ProductCategory
    {
        return DB::transaction(function () use ($data): ProductCategory {
            $slug = $data['slug'] ?? Str::slug($data['name']);

            $category = new ProductCategory(
                tenantId: (int) $data['tenant_id'],
                name: $data['name'],
                slug: $slug,
                imagePath: $data['image_path'] ?? null,
                parentId: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
                code: $data['code'] ?? null,
                isActive: (bool) ($data['is_active'] ?? true),
                description: $data['description'] ?? null,
            );

            $this->categories->create($category);
            return $category;
        });
    }

    public function find(int $tenantId, string $id): ProductCategory
    {
        $category = $this->categories->findById((int) $id);
        if (!$category || $category->getTenantId() !== $tenantId) {
            throw new \Exception('Product category not found');
        }
        return $category;
    }

    public function list(int $tenantId, array $filters = []): array
    {
        return $this->categories->findByTenant($tenantId, $filters);
    }

    public function update(int $tenantId, string $id, array $data): ProductCategory
    {
        return DB::transaction(function () use ($tenantId, $id, $data): ProductCategory {
            $category = $this->find($tenantId, $id);

            $category->update(
                name: $data['name'] ?? $category->getName(),
                slug: $data['slug'] ?? $category->getSlug(),
                imagePath: $data['image_path'] ?? $category->getImagePath(),
                parentId: $data['parent_id'] ?? $category->getParentId(),
                code: $data['code'] ?? $category->getCode(),
                isActive: (bool) ($data['is_active'] ?? $category->isActive()),
                description: $data['description'] ?? $category->getDescription(),
            );

            $this->categories->update($category);
            return $category;
        });
    }

    public function delete(int $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->find($tenantId, $id);
            $this->categories->delete((int) $id);
        });
    }
}
