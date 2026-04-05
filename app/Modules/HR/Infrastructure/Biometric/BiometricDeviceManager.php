<?php
declare(strict_types=1);
namespace Modules\HR\Infrastructure\Biometric;

use Modules\HR\Infrastructure\Biometric\Drivers\FingerprintScannerDriver;
use Modules\HR\Infrastructure\Biometric\Drivers\MockBiometricDriver;

/**
 * BiometricDeviceManager
 *
 * Factory / registry for biometric device drivers.
 * Resolves the appropriate driver by driver name, making it trivial
 * to add new device types (retina scanner, face-recognition, etc.)
 * without touching existing code.
 *
 * Usage:
 *   $manager = new BiometricDeviceManager($config);
 *   $device  = $manager->driver('fingerprint');
 *   $sample  = $device->captureSample();
 */
class BiometricDeviceManager
{
    /** @var array<string, BiometricDeviceInterface> */
    private array $resolved = [];

    public function __construct(private readonly array $config = []) {}

    /**
     * Resolve a driver by name.
     * Driver names are defined in the config under 'biometric.drivers'.
     */
    public function driver(string $name = 'default'): BiometricDeviceInterface
    {
        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        $driverConfig = $this->config['drivers'][$name] ?? [];
        $driverClass  = $driverConfig['driver'] ?? null;

        $instance = match ($driverClass) {
            FingerprintScannerDriver::class => new FingerprintScannerDriver($driverConfig),
            MockBiometricDriver::class      => new MockBiometricDriver($driverConfig),
            null                            => $this->resolveByDriverName($name, $driverConfig),
            default                         => new $driverClass($driverConfig),
        };

        $this->resolved[$name] = $instance;
        return $instance;
    }

    /**
     * Register a custom driver instance (useful for testing).
     */
    public function extend(string $name, BiometricDeviceInterface $driver): void
    {
        $this->resolved[$name] = $driver;
    }

    /**
     * List registered driver names.
     *
     * @return string[]
     */
    public function availableDrivers(): array
    {
        return array_keys($this->config['drivers'] ?? []);
    }

    private function resolveByDriverName(string $name, array $cfg): BiometricDeviceInterface
    {
        return match ($name) {
            'fingerprint' => new FingerprintScannerDriver($cfg),
            'mock'        => new MockBiometricDriver($cfg),
            default       => new MockBiometricDriver($cfg),
        };
    }
}
