<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Entities\UnitOfMeasure;
use Modules\Product\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;

class EloquentUnitOfMeasureRepository extends EloquentRepository implements UnitOfMeasureRepositoryInterface
{
    public function __construct(UnitOfMeasureModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (UnitOfMeasureModel $model): UnitOfMeasure => $this->mapModelToDomainEntity($model));
    }

    public function save(UnitOfMeasure $unitOfMeasure): UnitOfMeasure
    {
        $data = [
            'tenant_id' => $unitOfMeasure->getTenantId(),
            'name' => $unitOfMeasure->getName(),
            'symbol' => $unitOfMeasure->getSymbol(),
            'type' => $unitOfMeasure->getType(),
            'is_base' => $unitOfMeasure->isBase(),
        ];

        if ($unitOfMeasure->getId()) {
            $model = $this->update($unitOfMeasure->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var UnitOfMeasureModel $model */

        return $this->toDomainEntity($model);
    }

    public function findByTenantAndSymbol(int $tenantId, string $symbol): ?UnitOfMeasure
    {
        /** @var UnitOfMeasureModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('symbol', $symbol)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function find(int|string $id, array $columns = ['*']): ?UnitOfMeasure
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(UnitOfMeasureModel $model): UnitOfMeasure
    {
        return new UnitOfMeasure(
            id: (int) $model->id,
            tenantId: (int) $model->tenant_id,
            name: (string) $model->name,
            symbol: (string) $model->symbol,
            type: (string) $model->type,
            isBase: (bool) $model->is_base,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
