<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\Cache;
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
            'tenant_id' => $uomConversion->getTenantId(),
            'product_id' => $uomConversion->getProductId(),
            'from_uom_id' => $uomConversion->getFromUomId(),
            'to_uom_id' => $uomConversion->getToUomId(),
            'factor' => $uomConversion->getFactor(),
            'is_bidirectional' => $uomConversion->isBidirectional(),
            'is_active' => $uomConversion->isActive(),
        ];

        if ($uomConversion->getId()) {
            $model = $this->update($uomConversion->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var UomConversionModel $model */

        $this->bustCache($uomConversion->getTenantId());

        return $this->toDomainEntity($model);
    }

    public function findByUomPair(int $fromUomId, int $toUomId, ?int $tenantId = null, ?int $productId = null): ?UomConversion
    {
        $query = $this->model->newQuery()
            ->where('from_uom_id', $fromUomId)
            ->where('to_uom_id', $toUomId);

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        if ($productId !== null) {
            $query->where('product_id', $productId);
        } else {
            $query->whereNull('product_id');
        }

        /** @var UomConversionModel|null $model */
        $model = $query->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function listForResolution(int $tenantId, ?int $productId = null): array
    {
        $version  = (int) Cache::get($this->tenantVersionKey($tenantId), 0);
        $cacheKey = "uom_res:{$tenantId}:{$version}:".($productId ?? 'G');

        /** @var array<int, UomConversion>|null $cached */
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $query = $this->model->newQuery()
            ->where('is_active', true)
            ->where(function ($builder) use ($tenantId): void {
                $builder->where('tenant_id', $tenantId)
                    ->orWhereNull('tenant_id');
            });

        if ($productId !== null) {
            $query->where(function ($builder) use ($productId): void {
                $builder->where('product_id', $productId)
                    ->orWhereNull('product_id');
            });
        } else {
            $query->whereNull('product_id');
        }

        $query->orderByRaw('CASE WHEN product_id IS NULL THEN 0 ELSE 1 END ASC')
            ->orderBy('id', 'asc');

        /** @var array<int, UomConversionModel> $models */
        $models = $query->get()->all();

        $result = array_map(fn (UomConversionModel $model): UomConversion => $this->toDomainEntity($model), $models);

        Cache::put($cacheKey, $result, 3600);

        return $result;
    }

    public function delete(int|string $id): bool
    {
        $tenantId = $this->model->newQuery()
            ->where('id', $id)
            ->value('tenant_id');

        $result = parent::delete($id);

        $this->bustCache($tenantId !== null ? (int) $tenantId : null);

        return $result;
    }

    public function find(int|string $id, array $columns = ['*']): ?UomConversion
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(UomConversionModel $model): UomConversion
    {
        return new UomConversion(
            id: (int) $model->id,
            tenantId: $model->tenant_id !== null ? (int) $model->tenant_id : null,
            productId: $model->product_id !== null ? (int) $model->product_id : null,
            fromUomId: (int) $model->from_uom_id,
            toUomId: (int) $model->to_uom_id,
            factor: (string) $model->factor,
            isBidirectional: (bool) $model->is_bidirectional,
            isActive: (bool) $model->is_active,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }

    private function tenantVersionKey(int $tenantId): string
    {
        return "uom_ver:{$tenantId}";
    }

    /**
     * Bust the cached conversion graph for the given tenant by incrementing the
     * tenant-scoped version counter.  All previously cached keys for this tenant
     * become unreachable and will naturally expire from the store.
     *
     * When $tenantId is null (global/system-wide conversion), the call is a no-op
     * because we cannot enumerate every tenant; the TTL of 1 hour will expire
     * stale entries naturally.
     */
    private function bustCache(?int $tenantId): void
    {
        if ($tenantId === null) {
            return;
        }

        Cache::increment($this->tenantVersionKey($tenantId));
    }
}
