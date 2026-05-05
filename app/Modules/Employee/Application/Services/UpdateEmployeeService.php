<?php

declare(strict_types=1);

namespace Modules\Employee\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\ConcurrentModificationException;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\Employee\Application\Contracts\UpdateEmployeeServiceInterface;
use Modules\Employee\Application\DTOs\EmployeeData;
use Modules\Employee\Domain\Contracts\EmployeeUserSynchronizerInterface;
use Modules\Employee\Domain\Entities\Employee;
use Modules\Employee\Domain\Exceptions\EmployeeNotFoundException;
use Modules\Employee\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;

class UpdateEmployeeService extends BaseService implements UpdateEmployeeServiceInterface
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly EmployeeUserSynchronizerInterface $employeeUserSynchronizer,
    ) {
        parent::__construct($employeeRepository);
    }

    protected function handle(array $data): Employee
    {
        $id = (int) ($data['id'] ?? 0);
        $employee = $this->employeeRepository->find($id);

        if (! $employee) {
            throw new EmployeeNotFoundException($id);
        }

        $dto = EmployeeData::fromArray($data);

        if ($employee->getTenantId() !== $dto->tenant_id) {
            throw new EmployeeNotFoundException($id);
        }

        if ($dto->row_version !== $employee->getRowVersion()) {
            throw new ConcurrentModificationException('Employee', $id);
        }

        if ($dto->user_id !== null && $dto->user_id !== $employee->getUserId()) {
            throw new DomainException('Changing employee user association is not allowed.');
        }

        $employee->update(
            userId: $employee->getUserId(),
            employeeCode: $dto->employee_code,
            orgUnitId: $dto->org_unit_id,
            jobTitle: $dto->job_title,
            hireDate: $dto->hire_date !== null ? new \DateTimeImmutable($dto->hire_date) : null,
            terminationDate: $dto->termination_date !== null ? new \DateTimeImmutable($dto->termination_date) : null,
            metadata: $dto->metadata,
        );

        $saved = $this->employeeRepository->save($employee);

        $this->employeeUserSynchronizer->synchronizeForEmployeeUpdate(
            tenantId: $saved->getTenantId(),
            userId: $saved->getUserId(),
            orgUnitId: $saved->getOrgUnitId(),
            userPayload: $dto->user,
        );

        return $saved;
    }
}
