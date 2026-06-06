<?php

declare(strict_types=1);

namespace Modules\UOM\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Repositories\EloquentRepository;
use Modules\UOM\Models\UomConversionModel;

final class EloquentUomConversionRepository extends EloquentRepository implements UomConversionRepositoryInterface
{
    public function __construct(UomConversionModel $model)
    {
        parent::__construct($model);
    }

    public function findByIdInTenant(int|string $id, int $tenantId): ?DataRecord
    {
        $model = $this->query()
            ->where('tenant_id', $tenantId)
            ->whereKey($id)
            ->first();

        return $model instanceof Model ? $this->toRecord($model) : null;
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
