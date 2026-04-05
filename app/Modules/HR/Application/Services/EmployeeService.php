<?php
declare(strict_types=1);
namespace Modules\HR\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Application\Contracts\EmployeeServiceInterface;
use Modules\HR\Domain\Entities\Employee;
use Modules\HR\Domain\Events\EmployeeCreated;
use Modules\HR\Domain\Events\EmployeeTerminated;
use Modules\HR\Domain\Events\EmployeeUpdated;
use Modules\HR\Domain\Exceptions\EmployeeNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;

class EmployeeService implements EmployeeServiceInterface
{
    public function __construct(private readonly EmployeeRepositoryInterface $repository) {}

    public function findById(int $id): Employee
    {
        $employee = $this->repository->findById($id);
        if ($employee === null) {
            throw new EmployeeNotFoundException($id);
        }
        return $employee;
    }

    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->repository->findByTenant($tenantId, $perPage, $page);
    }

    public function findByDepartment(int $departmentId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->repository->findByDepartment($departmentId, $perPage, $page);
    }

    public function create(array $data): Employee
    {
        $employee = $this->repository->create($data);
        event(new EmployeeCreated($employee->getId(), $employee->getTenantId()));
        return $employee;
    }

    public function update(int $id, array $data): Employee
    {
        $this->findById($id);
        $updated = $this->repository->update($id, $data);
        $employee = $updated ?? $this->findById($id);
        event(new EmployeeUpdated($employee->getId(), $employee->getTenantId()));
        return $employee;
    }

    public function terminate(int $id, string $terminationDate): Employee
    {
        $employee = $this->findById($id);
        $updated = $this->repository->update($id, [
            'status'           => Employee::STATUS_TERMINATED,
            'termination_date' => $terminationDate,
        ]);
        $employee = $updated ?? $employee;
        event(new EmployeeTerminated($employee->getId(), $employee->getTenantId(), $terminationDate));
        return $employee;
    }

    public function delete(int $id): void
    {
        $this->findById($id);
        $this->repository->delete($id);
    }
}
