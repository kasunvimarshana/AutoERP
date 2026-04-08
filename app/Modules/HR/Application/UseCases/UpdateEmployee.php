<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Email;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\Core\Domain\ValueObjects\PhoneNumber;
use Modules\HR\Application\DTOs\UpdateEmployeeData;
use Modules\HR\Domain\Entities\Employee;
use Modules\HR\Domain\Events\EmployeeUpdated;
use Modules\HR\Domain\Exceptions\EmployeeNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;

class UpdateEmployee
{
    public function __construct(private readonly EmployeeRepositoryInterface $repo) {}

    public function execute(UpdateEmployeeData $data): Employee
    {
        $id       = (int) ($data->id ?? 0);
        $employee = $this->repo->find($id);
        if (! $employee) {
            throw new EmployeeNotFoundException($id);
        }

        $firstName      = $data->isProvided('first_name') ? new Name((string) $data->first_name) : $employee->getFirstName();
        $lastName       = $data->isProvided('last_name') ? new Name((string) $data->last_name) : $employee->getLastName();
        $email          = $data->isProvided('email') ? new Email((string) $data->email) : $employee->getEmail();
        $phone          = $data->isProvided('phone') ? ($data->phone !== null ? new PhoneNumber($data->phone) : null) : $employee->getPhone();
        $dateOfBirth    = $data->isProvided('date_of_birth') ? $data->date_of_birth : $employee->getDateOfBirth();
        $gender         = $data->isProvided('gender') ? $data->gender : $employee->getGender();
        $address        = $data->isProvided('address') ? $data->address : $employee->getAddress();
        $employeeNumber = $data->isProvided('employee_number') ? new Code((string) $data->employee_number) : $employee->getEmployeeNumber();
        $hireDate       = $data->isProvided('hire_date') ? new \DateTimeImmutable((string) $data->hire_date) : $employee->getHireDate();
        $employmentType = $data->isProvided('employment_type') ? (string) $data->employment_type : $employee->getEmploymentType();
        $status         = $data->isProvided('status') ? (string) $data->status : $employee->getStatus();
        $departmentId   = $data->isProvided('department_id') ? $data->department_id : $employee->getDepartmentId();
        $positionId     = $data->isProvided('position_id') ? $data->position_id : $employee->getPositionId();
        $managerId      = $data->isProvided('manager_id') ? $data->manager_id : $employee->getManagerId();
        $salary         = $data->isProvided('salary') ? $data->salary : $employee->getSalary();
        $currency       = $data->isProvided('currency') ? (string) $data->currency : $employee->getCurrency();
        $orgUnitId      = $data->isProvided('org_unit_id') ? $data->org_unit_id : $employee->getOrgUnitId();
        $metadata       = $data->isProvided('metadata') ? ($data->metadata !== null ? new Metadata($data->metadata) : null) : $employee->getMetadata();
        $isActive       = $data->isProvided('is_active') ? (bool) $data->is_active : $employee->isActive();
        $userId         = $data->isProvided('user_id') ? $data->user_id : $employee->getUserId();

        $employee->updateDetails(
            $firstName, $lastName, $email, $phone, $dateOfBirth, $gender, $address,
            $employeeNumber, $hireDate, $employmentType, $status,
            $departmentId, $positionId, $managerId, $salary, $currency, $orgUnitId,
            $metadata, $isActive, $userId
        );

        $saved = $this->repo->save($employee);
        EmployeeUpdated::dispatch($saved);

        return $saved;
    }
}
