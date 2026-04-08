<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Email;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\Core\Domain\ValueObjects\PhoneNumber;
use Modules\HR\Application\Contracts\UpdateEmployeeServiceInterface;
use Modules\HR\Application\DTOs\UpdateEmployeeData;
use Modules\HR\Domain\Entities\Employee;
use Modules\HR\Domain\Events\EmployeeUpdated;
use Modules\HR\Domain\Exceptions\EmployeeNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;

class UpdateEmployeeService extends BaseService implements UpdateEmployeeServiceInterface
{
    public function __construct(private readonly EmployeeRepositoryInterface $employeeRepository)
    {
        parent::__construct($employeeRepository);
    }

    protected function handle(array $data): Employee
    {
        $dto      = UpdateEmployeeData::fromArray($data);
        $id       = (int) ($dto->id ?? 0);
        $employee = $this->employeeRepository->find($id);
        if (! $employee) {
            throw new EmployeeNotFoundException($id);
        }

        $firstName = $dto->isProvided('first_name')
            ? new Name((string) $dto->first_name)
            : $employee->getFirstName();

        $lastName = $dto->isProvided('last_name')
            ? new Name((string) $dto->last_name)
            : $employee->getLastName();

        $email = $dto->isProvided('email')
            ? new Email((string) $dto->email)
            : $employee->getEmail();

        $phone = $dto->isProvided('phone')
            ? ($dto->phone !== null ? new PhoneNumber($dto->phone) : null)
            : $employee->getPhone();

        $dateOfBirth = $dto->isProvided('date_of_birth')
            ? $dto->date_of_birth
            : $employee->getDateOfBirth();

        $gender = $dto->isProvided('gender')
            ? $dto->gender
            : $employee->getGender();

        $address = $dto->isProvided('address')
            ? $dto->address
            : $employee->getAddress();

        $employeeNumber = $dto->isProvided('employee_number')
            ? new Code((string) $dto->employee_number)
            : $employee->getEmployeeNumber();

        $hireDate = $dto->isProvided('hire_date')
            ? new \DateTimeImmutable((string) $dto->hire_date)
            : $employee->getHireDate();

        $employmentType = $dto->isProvided('employment_type')
            ? (string) $dto->employment_type
            : $employee->getEmploymentType();

        $status = $dto->isProvided('status')
            ? (string) $dto->status
            : $employee->getStatus();

        $departmentId = $dto->isProvided('department_id')
            ? $dto->department_id
            : $employee->getDepartmentId();

        $positionId = $dto->isProvided('position_id')
            ? $dto->position_id
            : $employee->getPositionId();

        $managerId = $dto->isProvided('manager_id')
            ? $dto->manager_id
            : $employee->getManagerId();

        $salary = $dto->isProvided('salary')
            ? $dto->salary
            : $employee->getSalary();

        $currency = $dto->isProvided('currency')
            ? (string) $dto->currency
            : $employee->getCurrency();

        $orgUnitId = $dto->isProvided('org_unit_id')
            ? $dto->org_unit_id
            : $employee->getOrgUnitId();

        $metadata = $dto->isProvided('metadata')
            ? ($dto->metadata !== null ? new Metadata($dto->metadata) : null)
            : $employee->getMetadata();

        $isActive = $dto->isProvided('is_active')
            ? (bool) $dto->is_active
            : $employee->isActive();

        $userId = $dto->isProvided('user_id')
            ? $dto->user_id
            : $employee->getUserId();

        $employee->updateDetails(
            $firstName, $lastName, $email, $phone, $dateOfBirth, $gender, $address,
            $employeeNumber, $hireDate, $employmentType, $status,
            $departmentId, $positionId, $managerId, $salary, $currency, $orgUnitId,
            $metadata, $isActive, $userId
        );

        $saved = $this->employeeRepository->save($employee);
        $this->addEvent(new EmployeeUpdated($saved));

        return $saved;
    }
}
