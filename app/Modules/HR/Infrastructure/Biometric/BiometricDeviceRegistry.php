<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Biometric;

use Modules\HR\Application\Biometric\BiometricDeviceRegistryInterface;
use Modules\HR\Domain\Biometric\BiometricDeviceException;
use Modules\HR\Domain\Biometric\BiometricDeviceInterface;

/**
 * In-memory registry of all registered biometric device adapters.
 *
 * Services and controllers depend on BiometricDeviceRegistryInterface.
 * Concrete adapters are registered in HRServiceProvider::boot() so that
 * the application is fully configured before any request is handled.
 */
class BiometricDeviceRegistry implements BiometricDeviceRegistryInterface
{
    /** @var array<string, BiometricDeviceInterface> */
    private array $devices = [];

    public function register(BiometricDeviceInterface $device): void
    {
        $this->devices[$device->getDeviceId()] = $device;
    }

    public function get(string $deviceId): BiometricDeviceInterface
    {
        if (! isset($this->devices[$deviceId])) {
            throw new BiometricDeviceException(
                "Biometric device [{$deviceId}] is not registered."
            );
        }

        return $this->devices[$deviceId];
    }

    public function has(string $deviceId): bool
    {
        return isset($this->devices[$deviceId]);
    }

    /**
     * @return array<string, BiometricDeviceInterface>
     */
    public function all(): array
    {
        return $this->devices;
    }
}
