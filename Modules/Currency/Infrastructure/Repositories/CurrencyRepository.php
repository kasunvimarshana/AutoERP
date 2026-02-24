<?php

namespace Modules\Currency\Infrastructure\Repositories;

use Modules\Currency\Domain\Contracts\CurrencyRepositoryInterface;
use Modules\Currency\Infrastructure\Models\CurrencyModel;

class CurrencyRepository implements CurrencyRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return CurrencyModel::find($id);
    }

    public function findByCode(string $tenantId, string $code): ?object
    {
        return CurrencyModel::where('tenant_id', $tenantId)
            ->where('code', strtoupper($code))
            ->first();
    }

    public function findByTenant(string $tenantId, int $page = 1, int $perPage = 15): object
    {
        return CurrencyModel::where('tenant_id', $tenantId)
            ->orderBy('code')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findActiveByTenant(string $tenantId): iterable
    {
        return CurrencyModel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    public function create(array $data): object
    {
        return CurrencyModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $model = CurrencyModel::findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(string $id): void
    {
        CurrencyModel::findOrFail($id)->delete();
    }
}
