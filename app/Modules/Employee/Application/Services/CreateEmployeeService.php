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
            tenantId: $dto->tenantId,
            orgUnitId: $dto->orgUnitId,
            requestedUserId: $dto->userId,
            userPayload: $dto->user,
        );

        $existingEmployee = $this->employeeRepository->findByTenantAndUserId($dto->tenantId, $resolvedUserId);
        if ($existingEmployee !== null) {
            throw new DomainException('The user is already linked to another employee.');
        }

        $employee = new Employee(
            tenantId: $dto->tenantId,
            userId: $resolvedUserId,
            employeeCode: $dto->employeeCode,
            orgUnitId: $dto->orgUnitId,
            jobTitle: $dto->jobTitle,
            hireDate: $dto->hireDate !== null ? new \DateTimeImmutable($dto->hireDate) : null,
            terminationDate: $dto->terminationDate !== null ? new \DateTimeImmutable($dto->terminationDate) : null,
            metadata: $dto->metadata,
        );

        return $this->employeeRepository->save($employee);
    }
}
