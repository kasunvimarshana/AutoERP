<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

class AttendanceLogData
{
    /**
     * @param  array<string, mixed>  $rawData
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly int $employeeId,
        public readonly ?int $biometricDeviceId = null,
        public readonly string $punchTime = '',
        public readonly string $punchType = '',
        public readonly string $source = 'biometric',
        public readonly array $rawData = [],
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new static(
            tenantId: (int) $data['tenant_id'],
            employeeId: (int) $data['employee_id'],
            biometricDeviceId: isset($data['biometric_device_id']) ? (int) $data['biometric_device_id'] : null,
            punchTime: (string) $data['punch_time'],
            punchType: (string) $data['punch_type'],
            source: isset($data['source']) ? (string) $data['source'] : 'biometric',
            rawData: isset($data['raw_data']) && is_array($data['raw_data']) ? $data['raw_data'] : [],
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'employee_id' => $this->employeeId,
            'biometric_device_id' => $this->biometricDeviceId,
            'punch_time' => $this->punchTime,
            'punch_type' => $this->punchType,
            'source' => $this->source,
            'raw_data' => $this->rawData,
        ];
    }
}
