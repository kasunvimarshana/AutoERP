<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\UomConversion;
use Modules\Product\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\UomConversionModel;

class EloquentUomConversionRepository extends EloquentRepository implements UomConversionRepositoryInterface
{
    public function __construct(UomConversionModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (UomConversionModel $model): UomConversion => $this->mapModelToDomainEntity($model));
    }

    public function save(UomConversion $uomConversion): UomConversion
    {
        $data = [
            'from_uom_id' => $uomConversion->getFromUomId(),
            'to_uom_id' => $uomConversion->getToUomId(),
            'factor' => $uomConversion->getFactor(),
        ];

        if ($uomConversion->getId()) {
            $model = $this->update($uomConversion->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var UomConversionModel $model */

        return $this->toDomainEntity($model);
    }

    public function findByUomPair(int $fromUomId, int $toUomId): ?UomConversion
    {
        /** @var UomConversionModel|null $model */
        $model = $this->model->newQuery()
            ->where('from_uom_id', $fromUomId)
            ->where('to_uom_id', $toUomId)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function find(int|string $id, array $columns = ['*']): ?UomConversion
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(UomConversionModel $model): UomConversion
    {
        return new UomConversion(
            id: (int) $model->id,
            fromUomId: (int) $model->from_uom_id,
            toUomId: (int) $model->to_uom_id,
            factor: (string) $model->factor,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
