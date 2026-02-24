<?php

namespace Modules\Accounting\Infrastructure\Repositories;

use Modules\Accounting\Domain\Contracts\AccountingPeriodRepositoryInterface;
use Modules\Accounting\Infrastructure\Models\AccountingPeriodModel;

class AccountingPeriodRepository implements AccountingPeriodRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return AccountingPeriodModel::find($id);
    }

    public function findByDate(string $tenantId, string $date): ?object
    {
        return AccountingPeriodModel::where('tenant_id', $tenantId)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->whereIn('status', ['open', 'closed', 'locked'])
            ->orderBy('start_date', 'desc')
            ->first();
    }

    public function hasOverlap(string $tenantId, string $startDate, string $endDate, ?string $excludeId = null): bool
    {
        $query = AccountingPeriodModel::where('tenant_id', $tenantId)
            ->where('start_date', '<', $endDate)
            ->where('end_date', '>', $startDate)
            ->whereNotIn('status', ['draft']);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function paginate(string $tenantId, int $perPage = 15): object
    {
        return AccountingPeriodModel::where('tenant_id', $tenantId)
            ->orderBy('start_date', 'desc')
            ->paginate($perPage);
    }

    public function create(array $data): object
    {
        return AccountingPeriodModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $period = AccountingPeriodModel::findOrFail($id);
        $period->update($data);

        return $period->fresh();
    }
}
