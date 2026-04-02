<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Biometric;

use DateTimeImmutable;
use Modules\HR\Domain\Biometric\BiometricDeviceInterface;
use Modules\HR\Domain\Biometric\BiometricDeviceType;
use Modules\HR\Domain\Biometric\BiometricScanResult;

/**
 * Adapter for an optical or capacitive fingerprint scanner.
 *
 * In a production deployment this class wraps the vendor SDK (e.g. ZKTeco,
 * Suprema, DigitalPersona).  Here a stub implementation demonstrates the
 * contract; swap out the body of each method with the real SDK calls without
 * touching any application-layer code.
 *
 * The enrolled templates are kept in-memory for testing / demo purposes.
 * A production implementation would persist them to a dedicated biometric
 * template store (e.g. a separate encrypted database column or vendor cloud).
 */
class FingerprintDeviceAdapter implements BiometricDeviceInterface
{
    /** @var array<string, int>  template => employeeId */
    private array $enrolledTemplates = [];

    public function __construct(
        private readonly string $deviceId,
        private readonly bool $stubAvailable = true,
    ) {}

    public function getType(): string
    {
        return BiometricDeviceType::FINGERPRINT;
    }

    public function getDeviceId(): string
    {
        return $this->deviceId;
    }

    /**
     * Simulate a successful scan by returning a stub result.
     * Replace with real SDK call: $sdk->capture() or equivalent.
     */
    public function scan(): BiometricScanResult
    {
        if (! $this->isAvailable()) {
            return BiometricScanResult::failure(
                deviceType: $this->getType(),
                deviceId:   $this->getDeviceId(),
            );
        }

        // Stub: in production, call the SDK to capture a live fingerprint.
        $template = base64_encode(random_bytes(32));

        return BiometricScanResult::success(
            deviceType: $this->getType(),
            deviceId:   $this->getDeviceId(),
            template:   $template,
            confidence: 1.0,
            scannedAt:  new DateTimeImmutable,
        );
    }

    /**
     * Try to match a template against enrolled employees.
     * Returns the matching employee ID or null.
     */
    public function identify(string $template): ?int
    {
        return $this->enrolledTemplates[$template] ?? null;
    }

    /**
     * Register a biometric template for the given employee.
     */
    public function enroll(int $employeeId, string $template): bool
    {
        $this->enrolledTemplates[$template] = $employeeId;

        return true;
    }

    public function isAvailable(): bool
    {
        return $this->stubAvailable;
    }
}
