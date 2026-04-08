<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Email;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\Core\Domain\ValueObjects\PhoneNumber;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\HR\Domain\Entities\Employee;
use Modules\HR\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;

class EloquentEmployeeRepository extends EloquentRepository implements EmployeeRepositoryInterface
{
    public function __construct(EmployeeModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (EmployeeModel $model): Employee => $this->mapModelToDomainEntity($model));
    }

    public function save(Employee $employee): Employee
    {
        $savedModel = null;

        DB::transaction(function () use ($employee, &$savedModel) {
            $data = [
                'tenant_id'       => $employee->getTenantId(),
                'first_name'      => $employee->getFirstName()->value(),
                'last_name'       => $employee->getLastName()->value(),
                'email'           => $employee->getEmail()->value(),
                'phone'           => $employee->getPhone()?->value(),
                'date_of_birth'   => $employee->getDateOfBirth(),
                'gender'          => $employee->getGender(),
                'address'         => $employee->getAddress(),
                'employee_number' => $employee->getEmployeeNumber()->value(),
                'hire_date'       => $employee->getHireDate()->format('Y-m-d'),
                'employment_type' => $employee->getEmploymentType(),
                'status'          => $employee->getStatus(),
                'department_id'   => $employee->getDepartmentId(),
                'position_id'     => $employee->getPositionId(),
                'manager_id'      => $employee->getManagerId(),
                'salary'          => $employee->getSalary(),
                'currency'        => $employee->getCurrency(),
                'org_unit_id'     => $employee->getOrgUnitId(),
                'metadata'        => $employee->getMetadata()->toArray(),
                'is_active'       => $employee->isActive(),
                'user_id'         => $employee->getUserId(),
            ];

            if ($employee->getId()) {
                $savedModel = $this->update($employee->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof EmployeeModel) {
            throw new \RuntimeException('Failed to save employee.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function getByDepartment(int $departmentId): array
    {
        return $this->model->where('department_id', $departmentId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m))
            ->all();
    }

    public function getByManager(int $managerId): array
    {
        return $this->model->where('manager_id', $managerId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m))
            ->all();
    }

    public function findByUserId(int $userId): ?Employee
    {
        $model = $this->model->where('user_id', $userId)->first();

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    private function mapModelToDomainEntity(EmployeeModel $model): Employee
    {
        return new Employee(
            tenantId:       $model->tenant_id,
            firstName:      new Name($model->first_name),
            lastName:       new Name($model->last_name),
            email:          new Email($model->email),
            employeeNumber: new Code($model->employee_number),
            hireDate:       $model->hire_date ?? new \DateTimeImmutable(),
            employmentType: $model->employment_type,
            status:         $model->status,
            phone:          $model->phone !== null ? new PhoneNumber($model->phone) : null,
            dateOfBirth:    $model->date_of_birth?->format('Y-m-d'),
            gender:         $model->gender,
            address:        $model->address,
            departmentId:   $model->department_id,
            positionId:     $model->position_id,
            managerId:      $model->manager_id,
            salary:         isset($model->salary) ? (float) $model->salary : null,
            currency:       $model->currency ?? 'USD',
            orgUnitId:      $model->org_unit_id,
            metadata:       isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            isActive:       (bool) $model->is_active,
            id:             $model->id,
            createdAt:      $model->created_at,
            updatedAt:      $model->updated_at,
            userId:         $model->user_id,
        );
    }
}
