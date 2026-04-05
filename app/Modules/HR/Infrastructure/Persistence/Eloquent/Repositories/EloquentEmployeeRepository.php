<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Domain\Entities\Employee;
use Modules\HR\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmployeeModel;

class EloquentEmployeeRepository implements EmployeeRepositoryInterface
{
    public function __construct(private readonly EmployeeModel $model) {}

    private function toEntity(EmployeeModel $m): Employee
    {
        return new Employee(
            $m->id,
            $m->tenant_id,
            $m->user_id,
            $m->department_id,
            $m->position_id,
            $m->employee_code,
            $m->first_name,
            $m->last_name,
            $m->email,
            $m->phone,
            $m->gender,
            $m->date_of_birth,
            $m->hire_date,
            $m->termination_date,
            $m->status,
            $m->base_salary !== null ? (float) $m->base_salary : null,
            $m->bank_account,
            $m->tax_id,
            $m->address,
            $m->emergency_contact_name,
            $m->emergency_contact_phone,
            $m->metadata,
            $m->created_at,
            $m->updated_at,
        );
    }

    public function findById(int $id): ?Employee
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByCode(int $tenantId, string $code): ?Employee
    {
        $m = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('employee_code', $code)
            ->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findByEmail(int $tenantId, string $email): ?Employee
    {
        $m = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('email', $email)
            ->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findByUserId(int $userId): ?Employee
    {
        $m = $this->model->newQuery()->where('user_id', $userId)->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn($m) => $this->toEntity($m));
    }

    public function findByDepartment(int $departmentId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('department_id', $departmentId)
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn($m) => $this->toEntity($m));
    }

    public function create(array $data): Employee
    {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }

    public function update(int $id, array $data): ?Employee
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) {
            return null;
        }
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function delete(int $id): bool
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? (bool) $m->delete() : false;
    }
}
