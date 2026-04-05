<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Biometric\Drivers;

use Modules\HR\Infrastructure\Biometric\BiometricDeviceInterface;

/**
 * Mock Biometric Driver.
 *
 * Used in tests and development environments where no physical device
 * is available.  Returns deterministic results so services can be
 * exercised end-to-end without real hardware.
 */
class MockBiometricDriver implements BiometricDeviceInterface
{
    private bool $verifyResult;

    public function __construct(
        private readonly array $config = [],
        bool $verifyResult = true,
    ) {
        $this->verifyResult = $verifyResult;
    }

    public function getDeviceId(): string
    {
        return $this->config['device_id'] ?? 'mock_device_001';
    }

    public function getDeviceType(): string
    {
        return 'mock_biometric';
    }

    public function captureSample(): ?string
    {
        return 'mock_sample_' . time();
    }

    public function verify(string $sample, string $template): bool
    {
        return $this->verifyResult;
    }

    public function enroll(int $employeeId, string $sample): string
    {
        return 'mock_template_' . $employeeId . '_' . md5($sample);
    }

    public function recordAttendanceEvent(string $biometricData, string $eventType): array
    {
        return [
            'employee_id' => null,
            'timestamp'   => date('Y-m-d H:i:s'),
            'verified'    => true,
            'raw_data'    => $biometricData,
            'event_type'  => $eventType,
            'device_id'   => $this->getDeviceId(),
        ];
    }

    public function ping(): bool
    {
        return true;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    /** Allow tests to force a failed verification. */
    public function setVerifyResult(bool $result): void
    {
        $this->verifyResult = $result;
    }
}
