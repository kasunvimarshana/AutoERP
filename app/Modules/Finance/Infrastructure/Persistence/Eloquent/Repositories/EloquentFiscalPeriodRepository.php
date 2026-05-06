<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Finance\Domain\Entities\FiscalPeriod;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalPeriodRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\FiscalPeriodModel;

class EloquentFiscalPeriodRepository extends EloquentRepository implements FiscalPeriodRepositoryInterface
{
    public function __construct(FiscalPeriodModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (FiscalPeriodModel $model): FiscalPeriod => $this->mapModelToDomainEntity($model));
    }

    public function save(FiscalPeriod $fiscalPeriod): FiscalPeriod
    {
        $data = [
            'tenant_id' => $fiscalPeriod->getTenantId(),
            'fiscal_year_id' => $fiscalPeriod->getFiscalYearId(),
            'period_number' => $fiscalPeriod->getPeriodNumber(),
            'name' => $fiscalPeriod->getName(),
            'start_date' => $fiscalPeriod->getStartDate()->format('Y-m-d'),
            'end_date' => $fiscalPeriod->getEndDate()->format('Y-m-d'),
            'status' => $fiscalPeriod->getStatus(),
        ];

        if ($fiscalPeriod->getId()) {
            $model = $this->update($fiscalPeriod->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var FiscalPeriodModel $model */

        return $this->toDomainEntity($model);
    }

    public function findOpenPeriodForDate(int $tenantId, \DateTimeInterface $date): ?FiscalPeriod
    {
        /** @var FiscalPeriodModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('status', 'open')
            ->whereDate('start_date', '<=', $date->format('Y-m-d'))
            ->whereDate('end_date', '>=', $date->format('Y-m-d'))
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function find(int|string $id, array $columns = ['*']): ?FiscalPeriod
    {
        return parent::find($id, $columns);
    }

    public function findByTenantAndYearAndPeriodNumber(int $tenantId, int $fiscalYearId, int $periodNumber): ?FiscalPeriod
    {
        /** @var FiscalPeriodModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->where('period_number', $periodNumber)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    private function mapModelToDomainEntity(FiscalPeriodModel $model): FiscalPeriod
    {
        return new FiscalPeriod(
            tenantId: (int) $model->tenant_id,
            fiscalYearId: (int) $model->fiscal_year_id,
            periodNumber: (int) $model->period_number,
            name: (string) $model->name,
            startDate: $model->start_date,
            endDate: $model->end_date,
            status: (string) $model->status,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
