<?php

declare(strict_types=1);

namespace Modules\Brand\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Brand\Domain\Entities\Brand;
use Modules\Brand\Domain\Entities\BrandLogo;
use Modules\Brand\Domain\RepositoryInterfaces\BrandRepositoryInterface;
use Modules\Brand\Infrastructure\Persistence\Eloquent\Models\BrandLogoModel;
use Modules\Brand\Infrastructure\Persistence\Eloquent\Models\BrandModel;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;

class EloquentBrandRepository extends EloquentRepository implements BrandRepositoryInterface
{
    public function __construct(BrandModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (BrandModel $model): Brand => $this->mapModelToDomainEntity($model));
    }

    public function findBySlug(int $tenantId, string $slug): ?Brand
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('slug', $slug)->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByTenant(int $tenantId): Collection
    {
        return $this->toDomainCollection($this->model->where('tenant_id', $tenantId)->get());
    }

    public function save(Brand $brand): Brand
    {
        $data = [
            'tenant_id'   => $brand->getTenantId(),
            'name'        => $brand->getName(),
            'slug'        => $brand->getSlug(),
            'description' => $brand->getDescription(),
            'website'     => $brand->getWebsite(),
            'status'      => $brand->getStatus(),
            'attributes'  => $brand->getAttributes(),
            'metadata'    => $brand->getMetadata(),
        ];

        if ($brand->getId()) {
            $model = $this->update($brand->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var BrandModel $model */
        $model->load('logo');

        return $this->toDomainEntity($model);
    }

    public function find($id, array $columns = ['*']): ?Brand
    {
        $this->with(['logo']);

        return parent::find($id, $columns);
    }

    public function paginate(?int $perPage = null, array $columns = ['*'], ?string $pageName = null, ?int $page = null): LengthAwarePaginator
    {
        $this->with(['logo']);

        return parent::paginate($perPage, $columns, $pageName, $page);
    }

    private function mapModelToDomainEntity(BrandModel $model): Brand
    {
        $brand = new Brand(
            tenantId: $model->tenant_id,
            name: $model->name,
            slug: $model->slug,
            description: $model->description,
            website: $model->website,
            status: $model->status,
            attributes: $model->attributes,
            metadata: $model->metadata,
            id: $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );

        if ($model->relationLoaded('logo') && $model->logo !== null) {
            $brand->setLogo($this->mapLogoModelToDomainEntity($model->logo));
        }

        return $brand;
    }

    private function mapLogoModelToDomainEntity(BrandLogoModel $model): BrandLogo
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
