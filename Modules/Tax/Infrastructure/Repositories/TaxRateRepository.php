<?php

namespace Modules\Tax\Infrastructure\Repositories;

use Modules\Tax\Domain\Contracts\TaxRateRepositoryInterface;
use Modules\Tax\Infrastructure\Models\TaxRateModel;

class TaxRateRepository implements TaxRateRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return TaxRateModel::find($id);
    }

    public function findByTenant(string $tenantId): iterable
    {
        return TaxRateModel::where('tenant_id', $tenantId)->get();
    }

    public function findActiveByTenant(string $tenantId): iterable
    {
        return TaxRateModel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();
    }

    public function create(array $data): object
    {
        return TaxRateModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = TaxRateModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        TaxRateModel::findOrFail($id)->delete();
    }
}
