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

    public function query(array $with = []): Builder
    {
        return parent::query($with === [] ? ['fromUom', 'toUom'] : $with);
    }

    public function findByIdInTenant(int|string $id, int $tenantId): ?DataRecord
    {
        $model = $this->query()
            ->where('tenant_id', $tenantId)
            ->whereKey($id)
            ->first();

        return $model instanceof Model ? $this->toRecord($model) : null;
    }

    public function create(array $attributes): DataRecord
    {
        $model = $this->model->newQuery()->create($attributes);
        $model->load(['fromUom', 'toUom']);

        return $this->toRecord($model);
    }

    public function update(int|string $id, array $attributes): DataRecord
    {
        $model = $this->resolveModel($id);
        $model->fill($attributes);
        $model->save();
        $model->load(['fromUom', 'toUom']);

        return $this->toRecord($model);
    }

    public function findConversionBetween(
        int|string $fromUomId,
        int|string $toUomId,
        int $tenantId,
    ): ?DataRecord {
        $model = $this->query()
            ->where('from_uom_id', $fromUomId)
            ->where('to_uom_id', $toUomId)
            ->where('tenant_id', $tenantId)
            ->first();

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
        if (array_key_exists('search', $criteria)) {
            $search = trim((string) $criteria['search']);
            unset($criteria['search']);

            if ($search !== '') {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->whereHas('fromUom', function (Builder $uom) use ($search): void {
                            $uom->where('code', 'like', '%'.$search.'%')
                                ->orWhere('name', 'like', '%'.$search.'%')
                                ->orWhere('symbol', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('toUom', function (Builder $uom) use ($search): void {
                            $uom->where('code', 'like', '%'.$search.'%')
                                ->orWhere('name', 'like', '%'.$search.'%')
                                ->orWhere('symbol', 'like', '%'.$search.'%');
                        });
                });
            }
        }

        return parent::applyCriteria($query, $criteria);
    }

    protected function toRecord(Model $model): DataRecord
    {
        /** @var array<string,mixed> $payload */
        $payload = $model->toArray();

        return new DataRecord($payload);
    }
}
