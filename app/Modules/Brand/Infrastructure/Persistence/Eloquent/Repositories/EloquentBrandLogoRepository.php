<?php

declare(strict_types=1);

namespace Modules\Brand\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Brand\Domain\Entities\BrandLogo;
use Modules\Brand\Domain\RepositoryInterfaces\BrandLogoRepositoryInterface;
use Modules\Brand\Infrastructure\Persistence\Eloquent\Models\BrandLogoModel;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;

class EloquentBrandLogoRepository extends EloquentRepository implements BrandLogoRepositoryInterface
{
    public function __construct(BrandLogoModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (BrandLogoModel $model): BrandLogo => $this->mapModelToDomainEntity($model));
    }

    public function findByUuid(string $uuid): ?BrandLogo
    {
        $model = $this->model->where('uuid', $uuid)->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByBrand(int $brandId): ?BrandLogo
    {
        $model = $this->model->where('brand_id', $brandId)->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function save(BrandLogo $logo): BrandLogo
    {
        $data = [
            'tenant_id' => $logo->getTenantId(),
            'brand_id'  => $logo->getBrandId(),
            'uuid'      => $logo->getUuid(),
            'name'      => $logo->getName(),
            'file_path' => $logo->getFilePath(),
            'mime_type' => $logo->getMimeType(),
            'size'      => $logo->getSize(),
            'metadata'  => $logo->getMetadata(),
        ];

        if ($logo->getId()) {
            $model = $this->update($logo->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var BrandLogoModel $model */

        return $this->mapModelToDomainEntity($model);
    }

    public function deleteByBrand(int $brandId): bool
    {
        return (bool) $this->model->where('brand_id', $brandId)->delete();
    }

    private function mapModelToDomainEntity(BrandLogoModel $model): BrandLogo
    {
        return new BrandLogo(
            tenantId: $model->tenant_id,
            brandId: $model->brand_id,
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
