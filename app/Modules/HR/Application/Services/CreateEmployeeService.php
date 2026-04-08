<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Email;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\Core\Domain\ValueObjects\PhoneNumber;
use Modules\HR\Application\Contracts\CreateEmployeeServiceInterface;
use Modules\HR\Application\DTOs\EmployeeData;
use Modules\HR\Domain\Entities\Employee;
use Modules\HR\Domain\Events\EmployeeCreated;
use Modules\HR\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;

class CreateEmployeeService extends BaseService implements CreateEmployeeServiceInterface
{
    public function __construct(private readonly EmployeeRepositoryInterface $employeeRepository)
    {
        parent::__construct($employeeRepository);
    }

    protected function handle(array $data): Employee
    {
        $dto = EmployeeData::fromArray($data);

        $employee = new Employee(
            tenantId:       $dto->tenant_id,
            firstName:      new Name($dto->first_name),
            lastName:       new Name($dto->last_name),
            email:          new Email($dto->email),
            employeeNumber: new Code($dto->employee_number),
            hireDate:       new \DateTimeImmutable($dto->hire_date),
            employmentType: $dto->employment_type,
            status:         $dto->status,
            phone:          $dto->phone !== null ? new PhoneNumber($dto->phone) : null,
            dateOfBirth:    $dto->date_of_birth,
            gender:         $dto->gender,
            address:        $dto->address,
            departmentId:   $dto->department_id,
            positionId:     $dto->position_id,
            managerId:      $dto->manager_id,
            salary:         $dto->salary,
            currency:       $dto->currency ?? 'USD',
            orgUnitId:      $dto->org_unit_id,
            metadata:       $dto->metadata !== null ? new Metadata($dto->metadata) : null,
            isActive:       $dto->is_active,
            userId:         $dto->user_id,
        );

        $saved = $this->employeeRepository->save($employee);
        $this->addEvent(new EmployeeCreated($saved));

        return $saved;
    }
}
