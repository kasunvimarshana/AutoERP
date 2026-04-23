<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Contracts;

use Modules\HR\Domain\Entities\BiometricDevice;

interface BiometricDeviceAdapterInterface
{
    public function connect(BiometricDevice $device): bool;

    public function disconnect(): void;

    public function isConnected(): bool;

    /** @return array<int, array<string, mixed>> */
    public function syncAttendanceLogs(BiometricDevice $device, \DateTimeInterface $since): array;

    public function registerEmployee(BiometricDevice $device, int $employeeId, array $biometricData): bool;

    public function removeEmployee(BiometricDevice $device, int $employeeId): bool;

    /** @return array<string, mixed> */
    public function getDeviceInfo(BiometricDevice $device): array;
}
