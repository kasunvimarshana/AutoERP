<?php
declare(strict_types=1);
namespace Modules\HR\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Domain\Entities\LeaveRequest;

interface LeaveRequestServiceInterface
{
    public function findById(int $id): LeaveRequest;
    public function findByEmployee(int $employeeId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function findPendingByTenant(int $tenantId): array;
    public function create(array $data): LeaveRequest;
    public function approve(int $id, int $approverId): LeaveRequest;
    public function reject(int $id, int $approverId, string $reason): LeaveRequest;
    public function cancel(int $id): LeaveRequest;
    public function delete(int $id): void;
}
