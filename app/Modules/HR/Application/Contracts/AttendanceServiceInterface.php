<?php
declare(strict_types=1);
namespace Modules\HR\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Domain\Entities\AttendanceRecord;

interface AttendanceServiceInterface
{
    public function findById(int $id): AttendanceRecord;
    public function findByEmployee(int $employeeId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function findByTenantAndDateRange(int $tenantId, string $startDate, string $endDate, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function checkIn(int $employeeId, string $source = 'manual', ?string $deviceId = null, ?string $biometricData = null): AttendanceRecord;
    public function checkOut(int $id): AttendanceRecord;
    public function checkInViaBiometric(string $biometricData, string $deviceDriver = 'mock'): AttendanceRecord;
    public function create(array $data): AttendanceRecord;
    public function update(int $id, array $data): AttendanceRecord;
    public function delete(int $id): void;
}
