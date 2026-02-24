<?php

namespace Modules\HR\Infrastructure\Repositories;

use Modules\HR\Domain\Contracts\PayrollRunRepositoryInterface;
use Modules\HR\Infrastructure\Models\PayrollRunModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class PayrollRunRepository extends BaseEloquentRepository implements PayrollRunRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new PayrollRunModel());
    }

    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = PayrollRunModel::query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('period_start', 'desc')->paginate($perPage);
    }

    public function findActiveRunForPeriod(string $tenantId, string $start, string $end): ?object
    {
        return PayrollRunModel::where('tenant_id', $tenantId)
            ->where('period_start', $start)
            ->where('period_end', $end)
            ->whereNotIn('status', ['cancelled'])
            ->first();
    }

    public function chunkDraftRuns(int $chunkSize, callable $callback, ?string $tenantId = null): void
    {
        $query = PayrollRunModel::where('status', 'draft');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        // orderBy required for chunk() to produce stable, non-overlapping pages.
        $query->orderBy('id')->chunk($chunkSize, $callback);
    }
}
