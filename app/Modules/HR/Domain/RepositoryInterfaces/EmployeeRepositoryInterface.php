<?php
declare(strict_types=1);
namespace Modules\HR\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Domain\Entities\Employee;

interface EmployeeRepositoryInterface
{
    public function findById(int $id): ?Employee;
    public function findByCode(int $tenantId, string $code): ?Employee;
    public function findByEmail(int $tenantId, string $email): ?Employee;
    public function findByUserId(int $userId): ?Employee;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function findByDepartment(int $departmentId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function create(array $data): Employee;
    public function update(int $id, array $data): ?Employee;
    public function delete(int $id): bool;
}
