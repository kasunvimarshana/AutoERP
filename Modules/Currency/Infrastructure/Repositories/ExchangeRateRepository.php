<?php

namespace Modules\Currency\Infrastructure\Repositories;

use Modules\Currency\Domain\Contracts\ExchangeRateRepositoryInterface;
use Modules\Currency\Infrastructure\Models\ExchangeRateModel;

class ExchangeRateRepository implements ExchangeRateRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return ExchangeRateModel::find($id);
    }

    public function findLatest(string $tenantId, string $fromCode, string $toCode): ?object
    {
        return ExchangeRateModel::where('tenant_id', $tenantId)
            ->where('from_currency_code', strtoupper($fromCode))
            ->where('to_currency_code', strtoupper($toCode))
            ->orderByDesc('effective_date')
            ->orderByDesc('created_at')
            ->first();
    }

    public function findByTenant(string $tenantId, int $page = 1, int $perPage = 15): object
    {
        return ExchangeRateModel::where('tenant_id', $tenantId)
            ->orderByDesc('effective_date')
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function create(array $data): object
    {
        return ExchangeRateModel::create($data);
    }

    public function delete(string $id): void
    {
        ExchangeRateModel::findOrFail($id)->delete();
    }
}
