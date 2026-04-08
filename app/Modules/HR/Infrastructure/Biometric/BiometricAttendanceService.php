<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Biometric;

use DateTimeImmutable;
use Modules\HR\Application\Biometric\BiometricAttendanceServiceInterface;
use Modules\HR\Application\Biometric\BiometricDeviceRegistryInterface;
use Modules\HR\Domain\Biometric\BiometricDeviceException;
use Modules\HR\Domain\Entities\Attendance;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceRepositoryInterface;

/**
 * Processes attendance check-in / check-out events originating from
 * a biometric device.
 *
 * Flow:
 *  1. Resolve the device from the registry.
 *  2. Use the device to identify which employee the template belongs to.
 *  3. Create or update the attendance record in the repository.
 */
class BiometricAttendanceService implements BiometricAttendanceServiceInterface
{
    public function __construct(
        private readonly BiometricDeviceRegistryInterface $registry,
        private readonly AttendanceRepositoryInterface $attendanceRepository,
    ) {}

    public function checkIn(string $deviceId, string $biometricTemplate, int $tenantId): Attendance
    {
        $device     = $this->registry->get($deviceId);
        $employeeId = $device->identify($biometricTemplate);

        if ($employeeId === null) {
            throw new BiometricDeviceException(
                "Biometric template could not be matched to any enrolled employee on device [{$deviceId}]."
            );
        }

        $now        = new DateTimeImmutable;
        $attendance = new Attendance(
            tenantId:    $tenantId,
            employeeId:  $employeeId,
            date:        $now->format('Y-m-d'),
            checkInTime: $now,
            status:      'present',
        );

        return $this->attendanceRepository->save($attendance);
    }

    public function checkOut(string $deviceId, string $biometricTemplate, int $tenantId): Attendance
    {
        $device     = $this->registry->get($deviceId);
        $employeeId = $device->identify($biometricTemplate);

        if ($employeeId === null) {
            throw new BiometricDeviceException(
                "Biometric template could not be matched to any enrolled employee on device [{$deviceId}]."
            );
        }

        $today   = (new DateTimeImmutable)->format('Y-m-d');
        $records = $this->attendanceRepository->getByEmployee($employeeId);

        // Find today's open attendance record (no check-out yet)
        $attendance = null;
        foreach ($records as $record) {
            if ($record->getDate() === $today && $record->getCheckOutTime() === null) {
                $attendance = $record;
                break;
            }
        }

        if ($attendance === null) {
            throw new BiometricDeviceException(
                "No open check-in record found for employee [{$employeeId}] today."
            );
        }

        $now          = new DateTimeImmutable;
        $checkInTime  = $attendance->getCheckInTime();
        $hoursWorked  = ($now->getTimestamp() - $checkInTime->getTimestamp()) / 3600;

        $attendance->checkOut($now, round($hoursWorked, 2));

        return $this->attendanceRepository->save($attendance);
    }
}
