<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Biometric;

use DateTimeImmutable;

/**
 * Immutable value object that represents the outcome of a single biometric scan.
 */
final class BiometricScanResult
{
    public function __construct(
        /** Whether the scan was technically successful (hardware captured data) */
        private readonly bool $success,
        /** Device-type string (e.g. "fingerprint", "face") */
        private readonly string $deviceType,
        /** Logical device ID of the scanner that produced this result */
        private readonly string $deviceId,
        /** Raw or base-64 encoded biometric template data; empty string on failure */
        private readonly string $template,
        /** Match confidence score between 0.0 and 1.0 */
        private readonly float $confidence,
        /** Employee ID resolved by identification; null if unidentified */
        private readonly ?int $employeeId,
        /** Moment the scan was performed */
        private readonly DateTimeImmutable $scannedAt,
    ) {}

    public static function success(
        string $deviceType,
        string $deviceId,
        string $template,
        float $confidence,
        ?int $employeeId = null,
        ?DateTimeImmutable $scannedAt = null,
    ): self {
        return new self(
            success:    true,
            deviceType: $deviceType,
            deviceId:   $deviceId,
            template:   $template,
            confidence: $confidence,
            employeeId: $employeeId,
            scannedAt:  $scannedAt ?? new DateTimeImmutable,
        );
    }

    public static function failure(
        string $deviceType,
        string $deviceId,
        ?DateTimeImmutable $scannedAt = null,
    ): self {
        return new self(
            success:    false,
            deviceType: $deviceType,
            deviceId:   $deviceId,
            template:   '',
            confidence: 0.0,
            employeeId: null,
            scannedAt:  $scannedAt ?? new DateTimeImmutable,
        );
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getDeviceType(): string
    {
        return $this->deviceType;
    }

    public function getDeviceId(): string
    {
        return $this->deviceId;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getConfidence(): float
    {
        return $this->confidence;
    }

    public function getEmployeeId(): ?int
    {
        return $this->employeeId;
    }

    public function getScannedAt(): DateTimeImmutable
    {
        return $this->scannedAt;
    }
}
