<?php

declare(strict_types=1);

namespace Modules\UOM\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\UOM\Application\Repositories\UomConversionRepositoryInterface;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UomConversionModel;

final class EloquentUomConversionRepository extends EloquentRepository implements UomConversionRepositoryInterface
{
    public function __construct(UomConversionModel $model)
    {
        parent::__construct($model);
    }

    public function findConversionBetween(
        int|string $fromUomId,
        int|string $toUomId,
        int $tenantId,
        ?int $itemId,
    ): ?DataRecord {
        $query = $this->query()
            ->where('from_uom_id', $fromUomId)
            ->where('to_uom_id', $toUomId)
            ->where('tenant_id', $tenantId);

        if ($itemId === null) {
            $query->whereNull('item_id');
        } else {
            $query->where('item_id', $itemId);
        }

        $model = $query->first();

        return $model instanceof Model ? $this->toRecord($model) : null;
    }

    /** @return list<DataRecord> */
    public function findActiveConversionsForUom(int|string $uomId, int $tenantId): array
    {
        $models = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where(function (Builder $q) use ($uomId): void {
                $q->where('from_uom_id', $uomId)
                  ->orWhere('to_uom_id', $uomId);
            })
            ->get();

        $records = [];
        foreach ($models as $model) {
            if ($model instanceof Model) {
                $records[] = $this->toRecord($model);
            }
        }

        return $records;
    }

    protected function applyCriteria(Builder $query, array $criteria): Builder
    {
        unset($criteria['search']);

        return parent::applyCriteria($query, $criteria);
    }
}
