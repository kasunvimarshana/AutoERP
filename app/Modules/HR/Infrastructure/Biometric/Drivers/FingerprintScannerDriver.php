<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Biometric\Drivers;

use Modules\HR\Infrastructure\Biometric\BiometricDeviceInterface;

/**
 * Fingerprint Scanner Driver.
 *
 * Concrete implementation for fingerprint-based biometric devices.
 * Configure via the $config array (host, port, device_id, api_key, etc.)
 * to point at the physical scanner's HTTP/TCP API.
 */
class FingerprintScannerDriver implements BiometricDeviceInterface
{
    public function __construct(private readonly array $config = []) {}

    public function getDeviceId(): string
    {
        return $this->config['device_id'] ?? 'fingerprint_default';
    }

    public function getDeviceType(): string
    {
        return 'fingerprint_scanner';
    }

    public function captureSample(): ?string
    {
        // In production: open a socket/HTTP connection to the scanner,
        // trigger capture, and return the raw minutiae template.
        // Here we return a placeholder to keep the code fully compilable.
        return $this->callDevice('capture', []);
    }

    public function verify(string $sample, string $template): bool
    {
        $result = $this->callDevice('verify', ['sample' => $sample, 'template' => $template]);
        return $result !== null && $result !== 'false' && $result !== '';
    }

    public function enroll(int $employeeId, string $sample): string
    {
        $result = $this->callDevice('enroll', ['employee_id' => $employeeId, 'sample' => $sample]);
        return $result ?? hash('sha256', $sample . $employeeId);
    }

    public function recordAttendanceEvent(string $biometricData, string $eventType): array
    {
        return [
            'employee_id' => null,           // resolved after verification
            'timestamp'   => date('Y-m-d H:i:s'),
            'verified'    => false,
            'raw_data'    => $biometricData,
            'event_type'  => $eventType,
            'device_id'   => $this->getDeviceId(),
        ];
    }

    public function ping(): bool
    {
        $host = $this->config['host'] ?? null;
        if ($host === null) {
            return false;
        }
        $port = $this->config['port'] ?? 4370;
        $socket = @fsockopen($host, $port, $errno, $errstr, 2);
        if ($socket) {
            fclose($socket);
            return true;
        }
        return false;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Internal helper to dispatch commands to the physical device.
     * Replace this stub with real TCP/HTTP calls in production.
     */
    private function callDevice(string $command, array $params): ?string
    {
        // Production implementation would:
        //   1. Open a connection to $this->config['host']:$this->config['port']
        //   2. Send a JSON/binary command with $params
        //   3. Read and return the response
        // This stub returns null so the driver compiles without real hardware.
        return null;
    }
}
