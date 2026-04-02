<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Biometric;

use Modules\HR\Application\Biometric\BiometricDeviceRegistryInterface;
use Modules\HR\Application\Biometric\BiometricEnrollmentServiceInterface;

/**
 * Enrolls (registers) an employee's biometric template on a specific device.
 *
 * The service delegates the actual template storage to the device adapter
 * so that the enrollment mechanism is fully device-specific and replaceable.
 */
class BiometricEnrollmentService implements BiometricEnrollmentServiceInterface
{
    public function __construct(
        private readonly BiometricDeviceRegistryInterface $registry,
    ) {}

    public function enroll(int $employeeId, string $deviceId, string $biometricTemplate): bool
    {
        $device = $this->registry->get($deviceId);

        return $device->enroll($employeeId, $biometricTemplate);
    }
}
