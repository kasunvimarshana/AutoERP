<?php

declare(strict_types=1);

namespace Modules\HR\Application\Biometric;

use Modules\HR\Domain\Entities\Attendance;

/**
 * Processes attendance check-in and check-out events that originate
 * from a biometric device.
 */
interface BiometricAttendanceServiceInterface
{
    /**
     * Record a check-in for the employee identified by the biometric template.
     *
     * @param  string  $deviceId          ID of the scanning device
     * @param  string  $biometricTemplate Raw biometric template from the device
     * @param  int     $tenantId          Active tenant
     *
     * @throws \Modules\HR\Domain\Biometric\BiometricDeviceException  On device / identity failure
     */
    public function checkIn(string $deviceId, string $biometricTemplate, int $tenantId): Attendance;

    /**
     * Record a check-out for the employee identified by the biometric template.
     *
     * @param  string  $deviceId          ID of the scanning device
     * @param  string  $biometricTemplate Raw biometric template from the device
     * @param  int     $tenantId          Active tenant
     *
     * @throws \Modules\HR\Domain\Biometric\BiometricDeviceException  On device / identity failure
     */
    public function checkOut(string $deviceId, string $biometricTemplate, int $tenantId): Attendance;
}
