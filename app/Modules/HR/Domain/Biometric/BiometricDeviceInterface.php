<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Biometric;

/**
 * Core abstraction for any biometric device (fingerprint scanner, face recognition,
 * iris scanner, RFID reader, etc.).
 *
 * Implementations wrap vendor-specific SDKs or hardware drivers behind this
 * interface so the application layer never depends on a concrete device type.
 * New device types can be added by implementing this interface and registering
 * the adapter in the BiometricDeviceRegistry – zero changes to application code.
 */
interface BiometricDeviceInterface
{
    /**
     * Return the device-type identifier (e.g. "fingerprint", "face", "iris", "rfid").
     */
    public function getType(): string;

    /**
     * Return the unique hardware/logical device identifier.
     */
    public function getDeviceId(): string;

    /**
     * Capture a biometric sample from the device and return the raw result.
     * Returns a successful BiometricScanResult on success or one with
     * success=false when the scan could not be completed.
     */
    public function scan(): BiometricScanResult;

    /**
     * Try to identify a previously captured template against enrolled employees.
     * Returns the enrolled employee ID on a positive match, or null if no match.
     *
     * @param  string  $template  Raw biometric template (binary or base-64 encoded)
     */
    public function identify(string $template): ?int;

    /**
     * Enroll (register) a biometric template for the given employee.
     *
     * @param  int     $employeeId  The employee to associate with this template
     * @param  string  $template    Raw biometric template
     *
     * @return bool True on successful enrollment
     */
    public function enroll(int $employeeId, string $template): bool;

    /**
     * Return true when the device is reachable and ready to scan.
     */
    public function isAvailable(): bool;
}
