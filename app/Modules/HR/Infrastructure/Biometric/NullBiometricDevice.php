<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Biometric;

use DateTimeImmutable;
use Modules\HR\Domain\Biometric\BiometricDeviceInterface;
use Modules\HR\Domain\Biometric\BiometricScanResult;

/**
 * Null-object implementation of BiometricDeviceInterface.
 *
 * Used in contexts where no physical device is wired (e.g. local dev, CI
 * environments, or as a safe default when no concrete adapter is available).
 * All operations succeed silently and return predictable, harmless results so
 * that the rest of the system continues to function without real hardware.
 */
class NullBiometricDevice implements BiometricDeviceInterface
{
    public function __construct(
        private readonly string $deviceId   = 'null-device',
        private readonly string $deviceType = 'null',
    ) {}

    public function getType(): string
    {
        return $this->deviceType;
    }

    public function getDeviceId(): string
    {
        return $this->deviceId;
    }

    public function scan(): BiometricScanResult
    {
        return BiometricScanResult::success(
            deviceType: $this->deviceType,
            deviceId:   $this->deviceId,
            template:   '',
            confidence: 0.0,
            employeeId: null,
            scannedAt:  new DateTimeImmutable,
        );
    }

    public function identify(string $template): ?int
    {
        return null;
    }

    public function enroll(int $employeeId, string $template): bool
    {
        return true;
    }

    public function isAvailable(): bool
    {
        return false;
    }
}
