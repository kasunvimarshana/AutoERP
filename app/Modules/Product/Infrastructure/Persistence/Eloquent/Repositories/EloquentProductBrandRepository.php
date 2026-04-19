<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\ProductBrand;
use Modules\Product\Domain\RepositoryInterfaces\ProductBrandRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductBrandModel;

class EloquentProductBrandRepository extends EloquentRepository implements ProductBrandRepositoryInterface
{
    public function __construct(ProductBrandModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (ProductBrandModel $model): ProductBrand => $this->mapModelToDomainEntity($model));
    }

    public function save(ProductBrand $productBrand): ProductBrand
    {
        $data = [
            'tenant_id' => $productBrand->getTenantId(),
            'parent_id' => $productBrand->getParentId(),
            'name' => $productBrand->getName(),
            'image_path' => $productBrand->getImagePath(),
            'slug' => $productBrand->getSlug(),
            'code' => $productBrand->getCode(),
            'path' => $productBrand->getPath(),
            'depth' => $productBrand->getDepth(),
            'is_active' => $productBrand->isActive(),
            'website' => $productBrand->getWebsite(),
            'description' => $productBrand->getDescription(),
            'attributes' => $productBrand->getAttributes(),
            'metadata' => $productBrand->getMetadata(),
        ];

        if ($productBrand->getId()) {
            $model = $this->update($productBrand->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var ProductBrandModel $model */

        return $this->toDomainEntity($model);
    }

    public function findByTenantAndCode(int $tenantId, string $code): ?ProductBrand
    {
        /** @var ProductBrandModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function find(int|string $id, array $columns = ['*']): ?ProductBrand
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(ProductBrandModel $model): ProductBrand
    {
        return new ProductBrand(
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
            website: $model->website,
            description: $model->description,
            attributes: is_array($model->attributes) ? $model->attributes : null,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
