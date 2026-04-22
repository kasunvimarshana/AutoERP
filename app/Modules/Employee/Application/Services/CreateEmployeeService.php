<?php

declare(strict_types=1);

namespace Modules\Employee\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\Employee\Application\Contracts\CreateEmployeeServiceInterface;
use Modules\Employee\Application\DTOs\EmployeeData;
use Modules\Employee\Domain\Contracts\EmployeeUserSynchronizerInterface;
use Modules\Employee\Domain\Entities\Employee;
use Modules\Employee\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;

class CreateEmployeeService extends BaseService implements CreateEmployeeServiceInterface
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly EmployeeUserSynchronizerInterface $employeeUserSynchronizer,
    ) {
        parent::__construct($employeeRepository);
    }

    protected function handle(array $data): Employee
    {
        $dto = EmployeeData::fromArray($data);

        $resolvedUserId = $this->employeeUserSynchronizer->resolveUserIdForCreate(
            tenantId: $dto->tenant_id,
            orgUnitId: $dto->org_unit_id,
            requestedUserId: $dto->user_id,
            userPayload: $dto->user,
        );

        $existingEmployee = $this->employeeRepository->findByTenantAndUserId($dto->tenant_id, $resolvedUserId);
        if ($existingEmployee !== null) {
            throw new DomainException('The user is already linked to another employee.');
        }

        $employee = new Employee(
            tenantId: $dto->tenant_id,
            userId: $resolvedUserId,
            employeeCode: $dto->employee_code,
            orgUnitId: $dto->org_unit_id,
            jobTitle: $dto->job_title,
            hireDate: $dto->hire_date !== null ? new \DateTimeImmutable($dto->hire_date) : null,
            terminationDate: $dto->termination_date !== null ? new \DateTimeImmutable($dto->termination_date) : null,
            metadata: $dto->metadata,
        );

        return $this->employeeRepository->save($employee);
    }
}
