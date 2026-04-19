<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\ProductCategory;
use Modules\Product\Domain\RepositoryInterfaces\ProductCategoryRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductCategoryModel;

class EloquentProductCategoryRepository extends EloquentRepository implements ProductCategoryRepositoryInterface
{
    public function __construct(ProductCategoryModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (ProductCategoryModel $model): ProductCategory => $this->mapModelToDomainEntity($model));
    }

    public function save(ProductCategory $productCategory): ProductCategory
    {
        $data = [
            'tenant_id' => $productCategory->getTenantId(),
            'parent_id' => $productCategory->getParentId(),
            'name' => $productCategory->getName(),
            'image_path' => $productCategory->getImagePath(),
            'slug' => $productCategory->getSlug(),
            'code' => $productCategory->getCode(),
            'path' => $productCategory->getPath(),
            'depth' => $productCategory->getDepth(),
            'is_active' => $productCategory->isActive(),
            'description' => $productCategory->getDescription(),
            'attributes' => $productCategory->getAttributes(),
            'metadata' => $productCategory->getMetadata(),
        ];

        if ($productCategory->getId()) {
            $model = $this->update($productCategory->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var ProductCategoryModel $model */

        return $this->toDomainEntity($model);
    }

    public function findByTenantAndCode(int $tenantId, string $code): ?ProductCategory
    {
        /** @var ProductCategoryModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function find(int|string $id, array $columns = ['*']): ?ProductCategory
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(ProductCategoryModel $model): ProductCategory
    {
        return new ProductCategory(
            id: (int) $model->id,
            tenantId: (int) $model->tenant_id,
            parentId: $model->parent_id !== null ? (int) $model->parent_id : null,
            name: (string) $model->name,
            imagePath: $model->image_path,
            slug: (string) $model->slug,
            code: $model->code,
            path: $model->path,
            depth: (int) $model->depth,
            isActive: (bool) $model->is_active,
            description: $model->description,
            attributes: is_array($model->attributes) ? $model->attributes : null,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
