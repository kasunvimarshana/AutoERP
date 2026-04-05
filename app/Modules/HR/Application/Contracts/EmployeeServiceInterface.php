<?php
declare(strict_types=1);
namespace Modules\HR\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Domain\Entities\Employee;

interface EmployeeServiceInterface
{
    public function findById(int $id): Employee;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function findByDepartment(int $departmentId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function create(array $data): Employee;
    public function update(int $id, array $data): Employee;
    public function terminate(int $id, string $terminationDate): Employee;
    public function delete(int $id): void;
}
