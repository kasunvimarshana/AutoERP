<?php

declare(strict_types=1);

namespace Modules\HR\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Services\BaseService;
use Modules\HR\Entities\Employee;
use Modules\HR\Events\EmployeeHired;
use Modules\HR\Repositories\Contracts\EmployeeRepositoryInterface;

/**
 * Employee Service
 *
 * Handles business logic for employee management operations.
 * Includes onboarding, termination, and employee lifecycle management.
 */
class EmployeeService extends BaseService
{
    /**
     * EmployeeService constructor.
     */
    public function __construct(
        protected EmployeeRepositoryInterface $employeeRepository
    ) {}

    /**
     * Get paginated employees.
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->employeeRepository->paginate($perPage);
    }

    /**
     * Create a new employee.
     */
    public function create(array $data): Employee
    {
        return $this->executeInTransaction(function () use ($data) {
            // Generate employee number if not provided
            if (empty($data['employee_number'])) {
                $data['employee_number'] = $this->generateEmployeeNumber();
            }

            $employee = $this->employeeRepository->create($data);

            event(new EmployeeHired($employee));

            $this->logInfo('Employee created', [
                'employee_id' => $employee->id,
                'employee_number' => $employee->employee_number,
            ]);

            return $employee;
        });
    }

    /**
     * Update an existing employee.
     */
    public function update(int $id, array $data): Employee
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $employee = $this->employeeRepository->update($id, $data);

            $this->logInfo('Employee updated', [
                'employee_id' => $employee->id,
            ]);

            return $employee;
        });
    }

    /**
     * Delete an employee (soft delete).
     */
    public function delete(int $id): bool
    {
        $result = $this->employeeRepository->delete($id);

        if ($result) {
            $this->logInfo('Employee deleted', ['employee_id' => $id]);
        }

        return $result;
    }

    /**
     * Get employee by ID.
     */
    public function findById(int $id): ?Employee
    {
        return $this->employeeRepository->find($id);
    }

    /**
     * Get employees by department.
     */
    public function getByDepartment(int $departmentId): Collection
    {
        return $this->employeeRepository->getByDepartment($departmentId);
    }

    /**
     * Search employees.
     */
    public function search(string $query): Collection
    {
        return $this->employeeRepository->search($query);
    }

    /**
     * Terminate an employee.
     */
    public function terminate(int $id, array $data): Employee
    {
        return $this->executeInTransaction(function () use ($id, $data) {
            $terminationDate = $data['termination_date'] ?? Carbon::now()->toDateString();

            $employee = $this->employeeRepository->update($id, [
                'status' => 'terminated',
                'termination_date' => $terminationDate,
            ]);

            $this->logInfo('Employee terminated', [
                'employee_id' => $employee->id,
                'termination_date' => $terminationDate,
            ]);

            return $employee;
        });
    }

    /**
     * Get active employees.
     */
    public function getActiveEmployees(): Collection
    {
        return $this->employeeRepository->getActiveEmployees();
    }

    /**
     * Get employee's subordinates.
     */
    public function getSubordinates(int $managerId): Collection
    {
        return $this->employeeRepository->getSubordinates($managerId);
    }

    /**
     * Generate unique employee number.
     */
    protected function generateEmployeeNumber(): string
    {
        $prefix = 'EMP';
        $year = Carbon::now()->format('Y');
        $lastEmployee = $this->employeeRepository->all()->last();
        $number = $lastEmployee ? (int) substr($lastEmployee->employee_number, -6) + 1 : 1;

        return sprintf('%s-%s-%06d', $prefix, $year, $number);
    }
}
