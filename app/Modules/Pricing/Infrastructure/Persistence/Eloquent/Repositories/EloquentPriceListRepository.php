<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Pricing\Domain\Entities\PriceList;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListModel;

class EloquentPriceListRepository implements PriceListRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?PriceList
    {
        $model = PriceListModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);
        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findAll(string $tenantId): array
    {
        return PriceListModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn(PriceListModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function findDefault(string $tenantId): ?PriceList
    {
        $model = PriceListModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_default', true)
            ->first();
        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function save(PriceList $priceList): void
    {
        $model = PriceListModel::withoutGlobalScopes()->findOrNew($priceList->id);
        $model->fill([
            'tenant_id'  => $priceList->tenantId,
            'name'       => $priceList->name,
            'currency'   => $priceList->currency,
            'is_default' => $priceList->isDefault,
            'is_active'  => $priceList->isActive,
        ]);
        if (!$model->exists) {
            $model->id = $priceList->id;
        }
        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        PriceListModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)?->delete();
    }

    private function mapToEntity(PriceListModel $model): PriceList
    {
        return new PriceList(
            id: $model->id,
            tenantId: $model->tenant_id,
            name: $model->name,
            currency: $model->currency,
            isDefault: (bool) $model->is_default,
            isActive: (bool) $model->is_active,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
