<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Biometric;

/**
 * Abstraction for biometric device integration.
 * Implement this interface to support any biometric hardware
 * (fingerprint scanners, face recognition, retina scanners, etc.)
 * without impacting core HR functionality.
 */
interface BiometricDeviceInterface
{
    /**
     * Get unique identifier for this device.
     */
    public function getDeviceId(): string;

    /**
     * Get the type/model name of the device.
     */
    public function getDeviceType(): string;

    /**
     * Capture a biometric sample from the device.
     * Returns raw biometric data or a reference hash.
     */
    public function captureSample(): ?string;

    /**
     * Verify a biometric sample against a stored template.
     *
     * @param string $sample    Captured biometric data
     * @param string $template  Stored biometric template for the employee
     * @return bool True if the sample matches the template
     */
    public function verify(string $sample, string $template): bool;

    /**
     * Enroll a new biometric template for an employee.
     *
     * @param int    $employeeId The employee ID to enroll
     * @param string $sample     Captured biometric data
     * @return string The stored template reference
     */
    public function enroll(int $employeeId, string $sample): string;

    /**
     * Record an attendance event (check-in or check-out) from biometric data.
     *
     * @param string $biometricData The captured biometric sample or reference
     * @param string $eventType     'check_in' or 'check_out'
     * @return array{employee_id: int|null, timestamp: string, verified: bool, raw_data: string}
     */
    public function recordAttendanceEvent(string $biometricData, string $eventType): array;

    /**
     * Test connectivity to the physical device.
     */
    public function ping(): bool;

    /**
     * Return driver-specific configuration options.
     */
    public function getConfig(): array;
}
