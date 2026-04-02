<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Email;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\Core\Domain\ValueObjects\PhoneNumber;
use Modules\HR\Application\DTOs\EmployeeData;
use Modules\HR\Domain\Entities\Employee;
use Modules\HR\Domain\Events\EmployeeCreated;
use Modules\HR\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;

class CreateEmployee
{
    public function __construct(private readonly EmployeeRepositoryInterface $repo) {}

    public function execute(EmployeeData $data): Employee
    {
        $employee = new Employee(
            tenantId:       $data->tenant_id,
            firstName:      new Name($data->first_name),
            lastName:       new Name($data->last_name),
            email:          new Email($data->email),
            employeeNumber: new Code($data->employee_number),
            hireDate:       new \DateTimeImmutable($data->hire_date),
            employmentType: $data->employment_type,
            status:         $data->status,
            phone:          $data->phone !== null ? new PhoneNumber($data->phone) : null,
            dateOfBirth:    $data->date_of_birth,
            gender:         $data->gender,
            address:        $data->address,
            departmentId:   $data->department_id,
            positionId:     $data->position_id,
            managerId:      $data->manager_id,
            salary:         $data->salary,
            currency:       $data->currency ?? 'USD',
            orgUnitId:      $data->org_unit_id,
            metadata:       $data->metadata !== null ? new Metadata($data->metadata) : null,
            isActive:       $data->is_active,
            userId:         $data->user_id,
        );

        $saved = $this->repo->save($employee);
        EmployeeCreated::dispatch($saved);

        return $saved;
    }
}
