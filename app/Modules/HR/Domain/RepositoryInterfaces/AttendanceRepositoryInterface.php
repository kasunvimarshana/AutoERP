<?php
declare(strict_types=1);
namespace Modules\HR\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Domain\Entities\AttendanceRecord;

interface AttendanceRepositoryInterface
{
    public function findById(int $id): ?AttendanceRecord;
    public function findByEmployee(int $employeeId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function findByEmployeeAndDate(int $employeeId, string $date): ?AttendanceRecord;
    public function findByTenantAndDateRange(int $tenantId, string $startDate, string $endDate, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function create(array $data): AttendanceRecord;
    public function update(int $id, array $data): ?AttendanceRecord;
    public function delete(int $id): bool;
}
