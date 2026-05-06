<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Finance\Domain\Entities\FiscalYear;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalYearRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\FiscalYearModel;

class EloquentFiscalYearRepository extends EloquentRepository implements FiscalYearRepositoryInterface
{
    public function __construct(FiscalYearModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (FiscalYearModel $model): FiscalYear => $this->mapModelToDomainEntity($model));
    }

    public function save(FiscalYear $fiscalYear): FiscalYear
    {
        $data = [
            'tenant_id' => $fiscalYear->getTenantId(),
            'name' => $fiscalYear->getName(),
            'start_date' => $fiscalYear->getStartDate()->format('Y-m-d'),
            'end_date' => $fiscalYear->getEndDate()->format('Y-m-d'),
            'status' => $fiscalYear->getStatus(),
        ];

        if ($fiscalYear->getId()) {
            $model = $this->update($fiscalYear->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var FiscalYearModel $model */

        return $this->toDomainEntity($model);
    }

    public function findByTenantAndName(int $tenantId, string $name): ?FiscalYear
    {
        /** @var FiscalYearModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('name', $name)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function find(int|string $id, array $columns = ['*']): ?FiscalYear
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(FiscalYearModel $model): FiscalYear
    {
        return new FiscalYear(
            tenantId: (int) $model->tenant_id,
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
