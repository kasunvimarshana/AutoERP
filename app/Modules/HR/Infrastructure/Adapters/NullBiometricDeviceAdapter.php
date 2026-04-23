<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Adapters;

use DateTimeInterface;
use Modules\HR\Domain\Contracts\BiometricDeviceAdapterInterface;
use Modules\HR\Domain\Entities\BiometricDevice;

class NullBiometricDeviceAdapter implements BiometricDeviceAdapterInterface
{
    public function connect(BiometricDevice $device): bool
    {
        return true;
    }

    public function disconnect(): void {}

    public function isConnected(): bool
    {
        return false;
    }

    public function syncAttendanceLogs(BiometricDevice $device, DateTimeInterface $since): array
    {
        return [];
    }

    public function registerEmployee(BiometricDevice $device, int $employeeId, array $biometricData): bool
    {
        return true;
    }

    public function removeEmployee(BiometricDevice $device, int $employeeId): bool
    {
        return true;
    }

    public function getDeviceInfo(BiometricDevice $device): array
    {
        return [];
    }
}
