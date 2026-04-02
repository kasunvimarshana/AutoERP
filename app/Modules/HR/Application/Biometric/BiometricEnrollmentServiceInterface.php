<?php

declare(strict_types=1);

namespace Modules\HR\Application\Biometric;

/**
 * Handles enrolling an employee's biometric template into a device.
 */
interface BiometricEnrollmentServiceInterface
{
    /**
     * Enroll a biometric template for the given employee on the given device.
     *
     * @param  int     $employeeId        Employee to enroll
     * @param  string  $deviceId          Target device
     * @param  string  $biometricTemplate Raw biometric template
     *
     * @return bool True on successful enrollment
     *
     * @throws \Modules\HR\Domain\Biometric\BiometricDeviceException  On enrollment failure
     */
    public function enroll(int $employeeId, string $deviceId, string $biometricTemplate): bool;
}
