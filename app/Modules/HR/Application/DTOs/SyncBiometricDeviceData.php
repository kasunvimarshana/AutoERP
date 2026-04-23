<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

class SyncBiometricDeviceData
{
    public function __construct(
        public readonly int $deviceId,
        public readonly int $tenantId,
        public readonly ?string $syncFrom = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new static(
            deviceId: (int) $data['device_id'],
            tenantId: (int) $data['tenant_id'],
            syncFrom: isset($data['sync_from']) ? (string) $data['sync_from'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'device_id' => $this->deviceId,
            'tenant_id' => $this->tenantId,
            'sync_from' => $this->syncFrom,
        ];
    }
}
