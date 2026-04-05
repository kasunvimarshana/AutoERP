<?php
declare(strict_types=1);
namespace Modules\HR\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Domain\Entities\LeaveRequest;

interface LeaveRequestRepositoryInterface
{
    public function findById(int $id): ?LeaveRequest;
    public function findByEmployee(int $employeeId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function findPendingByTenant(int $tenantId): array;
    public function create(array $data): LeaveRequest;
    public function update(int $id, array $data): ?LeaveRequest;
    public function delete(int $id): bool;
}
