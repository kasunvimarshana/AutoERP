<?php

declare(strict_types=1);

namespace Modules\Category\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Category\Domain\Entities\CategoryImage;
use Modules\Category\Domain\RepositoryInterfaces\CategoryImageRepositoryInterface;
use Modules\Category\Infrastructure\Persistence\Eloquent\Models\CategoryImageModel;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;

class EloquentCategoryImageRepository extends EloquentRepository implements CategoryImageRepositoryInterface
{
    public function __construct(CategoryImageModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (CategoryImageModel $model): CategoryImage => $this->mapModelToDomainEntity($model));
    }

    public function findByUuid(string $uuid): ?CategoryImage
    {
        $model = $this->model->where('uuid', $uuid)->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByCategory(int $categoryId): ?CategoryImage
    {
        $model = $this->model->where('category_id', $categoryId)->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function save(CategoryImage $image): CategoryImage
    {
        $data = [
            'tenant_id'   => $image->getTenantId(),
            'category_id' => $image->getCategoryId(),
            'uuid'        => $image->getUuid(),
            'name'        => $image->getName(),
            'file_path'   => $image->getFilePath(),
            'mime_type'   => $image->getMimeType(),
            'size'        => $image->getSize(),
            'metadata'    => $image->getMetadata(),
        ];

        if ($image->getId()) {
            $model = $this->update($image->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        return $this->toDomainEntity($model);
    }

    public function deleteByCategory(int $categoryId): bool
    {
        return (bool) $this->model->where('category_id', $categoryId)->delete();
    }

    private function mapModelToDomainEntity(CategoryImageModel $model): CategoryImage
    {
        return new CategoryImage(
            tenantId: $model->tenant_id,
            categoryId: $model->category_id,
            uuid: $model->uuid,
            name: $model->name,
            filePath: $model->file_path,
            mimeType: $model->mime_type,
            size: $model->size,
            metadata: $model->metadata,
            id: $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
