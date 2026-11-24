<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Biometric\Services;

use InvalidArgumentException;
use Modules\Hr\Biometric\Adapters\DahuaCgiAdapter;
use Modules\Hr\Biometric\Adapters\GenericWebhookAdapter;
use Modules\Hr\Biometric\Adapters\HikvisionIsapiAdapter;
use Modules\Hr\Biometric\Adapters\UsbImportAdapter;
use Modules\Hr\Biometric\Adapters\ZkTecoPushAdapter;
use Modules\Hr\Biometric\Adapters\ZkTecoSocketAdapter;
use Modules\Hr\Biometric\Contracts\BiometricDeviceAdapterInterface;
use Modules\Hr\Enums\BiometricProtocol;
use Modules\Hr\Models\HrBiometricDevice;

final class BiometricAdapterManager
{
    /** @var array<string, class-string<BiometricDeviceAdapterInterface>> */
    private array $customAdapters = [];

    public function resolve(HrBiometricDevice $device): BiometricDeviceAdapterInterface
    {
        return $this->resolveByProtocol($device->protocol);
    }

    public function resolveByProtocol(BiometricProtocol $protocol): BiometricDeviceAdapterInterface
    {
        if (isset($this->customAdapters[$protocol->value])) {
            $class = $this->customAdapters[$protocol->value];

            return app($class);
        }

        return match ($protocol) {
            BiometricProtocol::ZkTecoPush => app(ZkTecoPushAdapter::class),
            BiometricProtocol::ZkTecoSocket => app(ZkTecoSocketAdapter::class),
            BiometricProtocol::HikvisionIsapi => app(HikvisionIsapiAdapter::class),
            BiometricProtocol::DahuaCgi => app(DahuaCgiAdapter::class),
            BiometricProtocol::GenericWebhook => app(GenericWebhookAdapter::class),
            BiometricProtocol::UsbImport => app(UsbImportAdapter::class),
        };
    }

    /**
     * @param  class-string<BiometricDeviceAdapterInterface>  $adapterClass
     */
    public function registerAdapter(BiometricProtocol|string $protocol, string $adapterClass): void
    {
        $key = $protocol instanceof BiometricProtocol ? $protocol->value : $protocol;

        if (! is_subclass_of($adapterClass, BiometricDeviceAdapterInterface::class)) {
            throw new InvalidArgumentException("Class {$adapterClass} must implement ".BiometricDeviceAdapterInterface::class);
        }

        $this->customAdapters[$key] = $adapterClass;
    }
}
