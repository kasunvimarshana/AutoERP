<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\DTOs;

use Modules\Hr\Enums\BiometricDeviceStatus;
use Modules\Hr\Enums\BiometricProtocol;

final readonly class BiometricDeviceData
{
    /**
     * @param  array<string, mixed>|null  $settings
     */
    public function __construct(
        public string $deviceCode,
        public string $name,
        public BiometricProtocol $protocol,
        public ?string $ipAddress = null,
        public ?int $port = null,
        public ?string $serialNumber = null,
        public ?string $commKey = null,
        public ?string $authToken = null,
        public BiometricDeviceStatus $status = BiometricDeviceStatus::Active,
        public bool $isEnabled = true,
        public bool $autoMapUsers = true,
        public ?int $organizationUnitId = null,
        public ?array $settings = null,
    ) {}
}
