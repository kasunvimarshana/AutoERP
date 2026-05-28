<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Finance\Application\Repositories\FiscalPeriodRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\FiscalPeriodModel;

final class EloquentFiscalPeriodRepository extends FinanceRepository implements FiscalPeriodRepositoryInterface
{
    public function __construct(FiscalPeriodModel $model)
    {
        parent::__construct($model);
    }

    public function findOpenByDate(int $tenantId, string $date, ?int $organizationUnitId = null): ?DataRecord
    {
        $query = $this->query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'OPEN')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->orderBy('start_date');

        if ($organizationUnitId !== null) {
            $query->where(function ($q) use ($organizationUnitId): void {
                $q->whereNull('organization_unit_id')
                    ->orWhere('organization_unit_id', $organizationUnitId);
            });
        }

        $model = $query->first();

        return $model !== null ? $this->toRecord($model) : null;
    }
}
