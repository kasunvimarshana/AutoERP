<?php

declare(strict_types=1);

namespace Modules\UoM\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\UoM\Domain\Entities\UomCategory;
use Modules\UoM\Domain\RepositoryInterfaces\UomCategoryRepositoryInterface;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Models\UomCategoryModel;

class EloquentUomCategoryRepository extends EloquentRepository implements UomCategoryRepositoryInterface
{
    public function __construct(UomCategoryModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (UomCategoryModel $model): UomCategory => $this->mapModelToDomainEntity($model));
    }

    public function save(UomCategory $category): UomCategory
    {
        $savedModel = null;

        DB::transaction(function () use ($category, &$savedModel) {
            if ($category->getId()) {
                $data = [
                    'tenant_id'   => $category->getTenantId(),
                    'name'        => $category->getName(),
                    'code'        => $category->getCode(),
                    'description' => $category->getDescription(),
                    'is_active'   => $category->isActive(),
                ];
                $savedModel = $this->update($category->getId(), $data);
            } else {
                $savedModel = $this->model->create([
                    'tenant_id'   => $category->getTenantId(),
                    'name'        => $category->getName(),
                    'code'        => $category->getCode(),
                    'description' => $category->getDescription(),
                    'is_active'   => $category->isActive(),
                ]);
            }
        });

        if (! $savedModel instanceof UomCategoryModel) {
            throw new \RuntimeException('Failed to save UomCategory.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    private function mapModelToDomainEntity(UomCategoryModel $model): UomCategory
    {
        return new UomCategory(
            tenantId:    $model->tenant_id,
            name:        $model->name,
            code:        $model->code,
            description: $model->description,
            isActive:    (bool) $model->is_active,
            id:          $model->id,
            createdAt:   $model->created_at,
            updatedAt:   $model->updated_at
        );
    }
}
