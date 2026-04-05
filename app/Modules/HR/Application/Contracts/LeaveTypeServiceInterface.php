<?php
declare(strict_types=1);
namespace Modules\HR\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Domain\Entities\LeaveType;

interface LeaveTypeServiceInterface
{
    public function findById(int $id): LeaveType;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function findAllActiveByTenant(int $tenantId): array;
    public function create(array $data): LeaveType;
    public function update(int $id, array $data): LeaveType;
    public function delete(int $id): void;
}
