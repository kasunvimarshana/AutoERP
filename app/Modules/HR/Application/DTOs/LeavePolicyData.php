<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

class LeavePolicyData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly int $leaveTypeId,
        public readonly string $name,
        public readonly string $accrualType = 'annual',
        public readonly float $accrualAmount = 21.0,
        public readonly ?int $orgUnitId = null,
        public readonly bool $isActive = true,
        public readonly array $metadata = [],
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new static(
            tenantId: (int) $data['tenant_id'],
            leaveTypeId: (int) $data['leave_type_id'],
            name: (string) $data['name'],
            accrualType: isset($data['accrual_type']) ? (string) $data['accrual_type'] : 'annual',
            accrualAmount: isset($data['accrual_amount']) ? (float) $data['accrual_amount'] : 21.0,
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : true,
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
            'leave_type_id' => $this->leaveTypeId,
            'name' => $this->name,
            'accrual_type' => $this->accrualType,
            'accrual_amount' => $this->accrualAmount,
            'org_unit_id' => $this->orgUnitId,
            'is_active' => $this->isActive,
            'metadata' => $this->metadata,
        ];
    }
}
