<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

class BiometricDeviceData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $code,
        public readonly string $deviceType,
        public readonly string $ipAddress,
        public readonly int $port = 23,
        public readonly string $location = '',
        public readonly ?int $orgUnitId = null,
        public readonly string $status = 'active',
        public readonly array $metadata = [],
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new static(
            tenantId: (int) $data['tenant_id'],
            name: (string) $data['name'],
            code: (string) $data['code'],
            deviceType: (string) $data['device_type'],
            ipAddress: (string) $data['ip_address'],
            port: isset($data['port']) ? (int) $data['port'] : 23,
            location: isset($data['location']) ? (string) $data['location'] : '',
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            status: isset($data['status']) ? (string) $data['status'] : 'active',
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : [],
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'name' => $this->name,
            'code' => $this->code,
            'device_type' => $this->deviceType,
            'ip_address' => $this->ipAddress,
            'port' => $this->port,
            'location' => $this->location,
            'org_unit_id' => $this->orgUnitId,
            'status' => $this->status,
            'metadata' => $this->metadata,
        ];
    }
}
