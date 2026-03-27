<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\ProductImage;
use Modules\Product\Domain\RepositoryInterfaces\ProductImageRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\ProductImageModel;

class EloquentProductImageRepository extends EloquentRepository implements ProductImageRepositoryInterface
{
    public function __construct(ProductImageModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (ProductImageModel $model): ProductImage => $this->mapModelToDomainEntity($model));
    }

    public function findByUuid(string $uuid): ?ProductImage
    {
        $model = $this->model->where('uuid', $uuid)->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function getByProduct(int $productId): Collection
    {
        return $this->toDomainCollection(
            $this->model->where('product_id', $productId)->orderBy('sort_order')->get()
        );
    }

    public function save(ProductImage $image): ProductImage
    {
        $data = [
            'tenant_id'  => $image->getTenantId(),
            'product_id' => $image->getProductId(),
            'uuid'       => $image->getUuid(),
            'name'       => $image->getName(),
            'file_path'  => $image->getFilePath(),
            'mime_type'  => $image->getMimeType(),
            'size'       => $image->getSize(),
            'sort_order' => $image->getSortOrder(),
            'is_primary' => $image->isPrimary(),
            'metadata'   => $image->getMetadata(),
        ];

        if ($image->getId()) {
            $model = $this->update($image->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var ProductImageModel $model */

        return $this->mapModelToDomainEntity($model);
    }

    private function mapModelToDomainEntity(ProductImageModel $model): ProductImage
    {
        return new ProductImage(
            tenantId: $model->tenant_id,
            productId: $model->product_id,
            uuid: $model->uuid,
            name: $model->name,
            filePath: $model->file_path,
            mimeType: $model->mime_type,
            size: $model->size,
            sortOrder: $model->sort_order,
            isPrimary: (bool) $model->is_primary,
            metadata: $model->metadata,
            id: $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
