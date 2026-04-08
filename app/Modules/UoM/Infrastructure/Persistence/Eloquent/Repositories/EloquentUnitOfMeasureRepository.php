<?php

declare(strict_types=1);

namespace Modules\UoM\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\UoM\Domain\Entities\UnitOfMeasure;
use Modules\UoM\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;

class EloquentUnitOfMeasureRepository extends EloquentRepository implements UnitOfMeasureRepositoryInterface
{
    public function __construct(UnitOfMeasureModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (UnitOfMeasureModel $model): UnitOfMeasure => $this->mapModelToDomainEntity($model));
    }

    public function save(UnitOfMeasure $unit): UnitOfMeasure
    {
        $savedModel = null;

        DB::transaction(function () use ($unit, &$savedModel) {
            if ($unit->getId()) {
                $data = [
                    'tenant_id'       => $unit->getTenantId(),
                    'uom_category_id' => $unit->getUomCategoryId(),
                    'name'            => $unit->getName(),
                    'code'            => $unit->getCode(),
                    'symbol'          => $unit->getSymbol(),
                    'is_base_unit'    => $unit->isBaseUnit(),
                    'factor'          => $unit->getFactor(),
                    'description'     => $unit->getDescription(),
                    'is_active'       => $unit->isActive(),
                ];
                $savedModel = $this->update($unit->getId(), $data);
            } else {
                $savedModel = $this->model->create([
                    'tenant_id'       => $unit->getTenantId(),
                    'uom_category_id' => $unit->getUomCategoryId(),
                    'name'            => $unit->getName(),
                    'code'            => $unit->getCode(),
                    'symbol'          => $unit->getSymbol(),
                    'is_base_unit'    => $unit->isBaseUnit(),
                    'factor'          => $unit->getFactor(),
                    'description'     => $unit->getDescription(),
                    'is_active'       => $unit->isActive(),
                ]);
            }
        });

        if (! $savedModel instanceof UnitOfMeasureModel) {
            throw new \RuntimeException('Failed to save UnitOfMeasure.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findByCode(int $tenantId, string $code): ?UnitOfMeasure
    {
        $model = $this->model->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    public function findByCategory(int $tenantId, int $categoryId): Collection
    {
        return $this->model->where('tenant_id', $tenantId)
            ->where('uom_category_id', $categoryId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    public function findBaseUnit(int $tenantId, int $categoryId): ?UnitOfMeasure
    {
        $model = $this->model->where('tenant_id', $tenantId)
            ->where('uom_category_id', $categoryId)
            ->where('is_base_unit', true)
            ->first();

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    private function mapModelToDomainEntity(UnitOfMeasureModel $model): UnitOfMeasure
    {
        return new UnitOfMeasure(
            tenantId:      $model->tenant_id,
            uomCategoryId: $model->uom_category_id,
            name:          $model->name,
            code:          $model->code,
            symbol:        $model->symbol,
            isBaseUnit:    (bool) $model->is_base_unit,
            factor:        (float) $model->factor,
            description:   $model->description,
            isActive:      (bool) $model->is_active,
            id:            $model->id,
            createdAt:     $model->created_at,
            updatedAt:     $model->updated_at
        );
    }
}
