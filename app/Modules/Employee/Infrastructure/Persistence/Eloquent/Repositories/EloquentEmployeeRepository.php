<?php

declare(strict_types=1);

namespace Modules\Employee\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Employee\Domain\Entities\Employee;
use Modules\Employee\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;
use Modules\Employee\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;

class EloquentEmployeeRepository extends EloquentRepository implements EmployeeRepositoryInterface
{
    public function __construct(EmployeeModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (EmployeeModel $model): Employee => $this->mapModelToDomainEntity($model));
    }

    public function save(Employee $employee): Employee
    {
        $data = [
            'tenant_id' => $employee->getTenantId(),
            'user_id' => $employee->getUserId(),
            'employee_code' => $employee->getEmployeeCode(),
            'org_unit_id' => $employee->getOrgUnitId(),
            'job_title' => $employee->getJobTitle(),
            'hire_date' => $employee->getHireDate()?->format('Y-m-d'),
            'termination_date' => $employee->getTerminationDate()?->format('Y-m-d'),
            'metadata' => $employee->getMetadata(),
            'row_version' => $employee->getRowVersion(),
        ];

        if ($employee->getId()) {
            $model = $this->update($employee->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var EmployeeModel $model */

        return $this->toDomainEntity($model);
    }

    public function findByTenantAndUserId(int $tenantId, int $userId): ?Employee
    {
        /** @var EmployeeModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByTenantAndEmployeeCode(int $tenantId, string $employeeCode): ?Employee
    {
        /** @var EmployeeModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('employee_code', $employeeCode)
            ->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function find(int|string $id, array $columns = ['*']): ?Employee
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(EmployeeModel $model): Employee
    {
        return new Employee(
            id: (int) $model->id,
            tenantId: (int) $model->tenant_id,
            userId: (int) $model->user_id,
            employeeCode: $model->employee_code,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            jobTitle: $model->job_title,
            hireDate: $model->hire_date,
            terminationDate: $model->termination_date,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            rowVersion: (int) ($model->row_version ?? 1),
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
