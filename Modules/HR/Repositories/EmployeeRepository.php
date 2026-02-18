<?php

declare(strict_types=1);

namespace Modules\HR\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\HR\Entities\Employee;
use Modules\HR\Repositories\Contracts\EmployeeRepositoryInterface;

/**
 * Employee Repository Implementation
 *
 * Handles all employee data access operations.
 */
class EmployeeRepository extends BaseRepository implements EmployeeRepositoryInterface
{
    /**
     * EmployeeRepository constructor.
     */
    public function __construct(Employee $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByEmail(string $email): ?Employee
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findByEmployeeNumber(string $employeeNumber): ?Employee
    {
        return $this->model->where('employee_number', $employeeNumber)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getByDepartment(int $departmentId): Collection
    {
        return $this->model->where('department_id', $departmentId)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByPosition(int $positionId): Collection
    {
        return $this->model->where('position_id', $positionId)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveEmployees(): Collection
    {
        return $this->model->where('status', 'active')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query): Collection
    {
        return $this->model
            ->where('first_name', 'LIKE', "%{$query}%")
            ->orWhere('last_name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->orWhere('employee_number', 'LIKE', "%{$query}%")
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getSubordinates(int $managerId): Collection
    {
        return $this->model->where('reports_to', $managerId)->get();
    }
}
