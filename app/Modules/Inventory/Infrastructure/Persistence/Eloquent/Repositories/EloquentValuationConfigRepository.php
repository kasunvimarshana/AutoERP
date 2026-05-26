<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Inventory\Domain\Constants\InventoryDimension;
use Modules\Inventory\Application\Repositories\ValuationConfigRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\ValuationConfigModel;

final class EloquentValuationConfigRepository extends EloquentRepository implements ValuationConfigRepositoryInterface
{
    public function __construct(ValuationConfigModel $model)
    {
        parent::__construct($model);
    }

    public function findActiveForDimensions(array $criteria): ?DataRecord
    {
        $tenantId = $criteria[InventoryDimension::TENANT_ID] ?? null;
        if (! is_int($tenantId) && ! is_string($tenantId)) {
            return null;
        }

        $dimensionColumns = [
            InventoryDimension::ORGANIZATION_UNIT_ID,
            InventoryDimension::WAREHOUSE_ID,
            InventoryDimension::LOCATION_ID,
            InventoryDimension::ITEM_ID,
            InventoryDimension::VARIANT_ID,
            InventoryDimension::BATCH_ID,
            InventoryDimension::SERIAL_ID,
            'transaction_type',
        ];

        $query = $this->query()
            ->where(InventoryDimension::TENANT_ID, $tenantId)
            ->where('is_active', true);

        foreach ($dimensionColumns as $column) {
            $value = $criteria[$column] ?? null;

            if ($value === null || $value === '') {
                $query->whereNull($column);

                continue;
            }

            $query->where(function ($builder) use ($column, $value): void {
                $builder->where($column, $value)->orWhereNull($column);
            });
        }

        $scoreExpression = implode(' + ', array_map(
            static fn (string $column): string => sprintf('CASE WHEN %s IS NULL THEN 0 ELSE 1 END', $column),
            $dimensionColumns,
        ));

        $model = $query
            ->orderByRaw($scoreExpression . ' DESC')
            ->orderByDesc('id')
            ->first();

        return $model === null ? null : $this->toRecord($model);
    }
}
