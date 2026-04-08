<?php

declare(strict_types=1);

namespace Modules\HR\Application\Biometric;

use Modules\HR\Domain\Biometric\BiometricDeviceInterface;

/**
 * Registry that holds all available biometric device adapters.
 *
 * Application services depend on this interface rather than on concrete
 * adapters, keeping the application layer independent of any specific
 * hardware or SDK.
 */
interface BiometricDeviceRegistryInterface
{
    /**
     * Register a device adapter.
     * Replaces any existing registration with the same device ID.
     */
    public function register(BiometricDeviceInterface $device): void;

    /**
     * Retrieve a device by its unique device ID.
     *
     * @throws \Modules\HR\Domain\Biometric\BiometricDeviceException  When the device is unknown
     */
    public function get(string $deviceId): BiometricDeviceInterface;

    /**
     * Return true when a device with the given ID is registered.
     */
    public function has(string $deviceId): bool;

    /**
     * Return all registered devices, keyed by device ID.
     *
     * @return array<string, BiometricDeviceInterface>
     */
    public function all(): array;
}
