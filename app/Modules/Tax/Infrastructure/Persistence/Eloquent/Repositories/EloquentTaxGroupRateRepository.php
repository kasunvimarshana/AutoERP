<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Tax\Domain\Entities\TaxGroupRate;
use Modules\Tax\Domain\RepositoryInterfaces\TaxGroupRateRepositoryInterface;
use Modules\Tax\Infrastructure\Persistence\Eloquent\Models\TaxGroupRateModel;

class EloquentTaxGroupRateRepository implements TaxGroupRateRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?TaxGroupRate
    {
        $model = TaxGroupRateModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findByTaxGroup(string $tenantId, string $taxGroupId): array
    {
        return TaxGroupRateModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('tax_group_id', $taxGroupId)
            ->orderBy('sequence')
            ->get()
            ->map(fn(TaxGroupRateModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function save(TaxGroupRate $rate): void
    {
        /** @var TaxGroupRateModel $model */
        $model = TaxGroupRateModel::withoutGlobalScopes()->findOrNew($rate->id);

        $model->fill([
            'tenant_id'    => $rate->tenantId,
            'tax_group_id' => $rate->taxGroupId,
            'name'         => $rate->name,
            'rate'         => $rate->rate,
            'type'         => $rate->type,
            'sequence'     => $rate->sequence,
            'is_active'    => $rate->isActive,
        ]);

        if (! $model->exists) {
            $model->id = $rate->id;
        }

        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        TaxGroupRateModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)
            ?->delete();
    }

    private function mapToEntity(TaxGroupRateModel $model): TaxGroupRate
    {
        return new TaxGroupRate(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            taxGroupId: (string) $model->tax_group_id,
            name: (string) $model->name,
            rate: (float) $model->rate,
            type: (string) $model->type,
            sequence: (int) $model->sequence,
            isActive: (bool) $model->is_active,
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
