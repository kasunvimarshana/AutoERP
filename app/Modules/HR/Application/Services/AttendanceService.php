<?php
declare(strict_types=1);
namespace Modules\HR\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Application\Contracts\AttendanceServiceInterface;
use Modules\HR\Domain\Entities\AttendanceRecord;
use Modules\HR\Domain\Events\AttendanceRecorded;
use Modules\HR\Domain\Exceptions\AttendanceRecordNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\EmployeeRepositoryInterface;
use Modules\HR\Infrastructure\Biometric\BiometricDeviceManager;

class AttendanceService implements AttendanceServiceInterface
{
    public function __construct(
        private readonly AttendanceRepositoryInterface $repository,
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly BiometricDeviceManager $biometricManager,
    ) {}

    public function findById(int $id): AttendanceRecord
    {
        $record = $this->repository->findById($id);
        if ($record === null) {
            throw new AttendanceRecordNotFoundException($id);
        }
        return $record;
    }

    public function findByEmployee(int $employeeId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->repository->findByEmployee($employeeId, $perPage, $page);
    }

    public function findByTenantAndDateRange(int $tenantId, string $startDate, string $endDate, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->repository->findByTenantAndDateRange($tenantId, $startDate, $endDate, $perPage, $page);
    }

    public function checkIn(int $employeeId, string $source = 'manual', ?string $deviceId = null, ?string $biometricData = null): AttendanceRecord
    {
        $today = date('Y-m-d');
        $record = $this->repository->create([
            'employee_id'     => $employeeId,
            'tenant_id'       => $this->getEmployeeTenantId($employeeId),
            'attendance_date' => $today,
            'check_in'        => date('Y-m-d H:i:s'),
            'source'          => $source,
            'device_id'       => $deviceId,
            'biometric_data'  => $biometricData,
            'is_approved'     => false,
        ]);
        event(new AttendanceRecorded($record->getId(), $employeeId, $source));
        return $record;
    }

    public function checkOut(int $id): AttendanceRecord
    {
        $record = $this->findById($id);
        $checkOutTime = new \DateTime();
        $updated = $this->repository->update($id, [
            'check_out'    => $checkOutTime->format('Y-m-d H:i:s'),
            'worked_hours' => $record->getCheckIn()
                ? round(($checkOutTime->getTimestamp() - $record->getCheckIn()->getTimestamp()) / 3600, 2)
                : null,
        ]);
        return $updated ?? $record;
    }

    public function checkInViaBiometric(string $biometricData, string $deviceDriver = 'mock'): AttendanceRecord
    {
        $device = $this->biometricManager->driver($deviceDriver);
        $event  = $device->recordAttendanceEvent($biometricData, AttendanceRecord::TYPE_CHECK_IN);

        $employeeId = $event['employee_id'] ?? null;
        if ($employeeId === null) {
            // Fallback: biometric data could not be resolved to an employee yet.
            // In production the device would return the matched employee ID.
            // Here we require it to be passed; default to a placeholder.
            throw new \InvalidArgumentException('Biometric device could not identify employee.');
        }

        return $this->checkIn(
            $employeeId,
            AttendanceRecord::SOURCE_BIOMETRIC,
            $device->getDeviceId(),
            $biometricData,
        );
    }

    public function create(array $data): AttendanceRecord
    {
        $record = $this->repository->create($data);
        event(new AttendanceRecorded($record->getId(), $record->getEmployeeId(), $record->getSource()));
        return $record;
    }

    public function update(int $id, array $data): AttendanceRecord
    {
        $this->findById($id);
        $updated = $this->repository->update($id, $data);
        return $updated ?? $this->findById($id);
    }

    public function delete(int $id): void
    {
        $this->findById($id);
        $this->repository->delete($id);
    }

    private function getEmployeeTenantId(int $employeeId): int
    {
        $employee = $this->employeeRepository->findById($employeeId);
        return $employee?->getTenantId() ?? 0;
    }
}
