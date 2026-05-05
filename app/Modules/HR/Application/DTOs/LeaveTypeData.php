<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

class LeaveTypeData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $code,
        public readonly string $description = '',
        public readonly float $maxDaysPerYear = 21.0,
        public readonly float $carryForwardDays = 0.0,
        public readonly bool $isPaid = true,
        public readonly bool $requiresApproval = true,
        public readonly ?string $applicableGender = null,
        public readonly int $minServiceDays = 0,
        public readonly bool $isActive = true,
        public readonly array $metadata = [],
        public readonly int $rowVersion = 1,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new static(
            tenantId: (int) $data['tenant_id'],
            name: (string) $data['name'],
            code: (string) $data['code'],
            description: isset($data['description']) ? (string) $data['description'] : '',
            maxDaysPerYear: isset($data['max_days_per_year']) ? (float) $data['max_days_per_year'] : 21.0,
            carryForwardDays: isset($data['carry_forward_days']) ? (float) $data['carry_forward_days'] : 0.0,
            isPaid: isset($data['is_paid']) ? (bool) $data['is_paid'] : true,
            requiresApproval: isset($data['requires_approval']) ? (bool) $data['requires_approval'] : true,
            applicableGender: isset($data['applicable_gender']) ? (string) $data['applicable_gender'] : null,
            minServiceDays: isset($data['min_service_days']) ? (int) $data['min_service_days'] : 0,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : true,
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : [],
            rowVersion: isset($data['row_version']) ? (int) $data['row_version'] : 1,
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
            'description' => $this->description,
            'max_days_per_year' => $this->maxDaysPerYear,
            'carry_forward_days' => $this->carryForwardDays,
            'is_paid' => $this->isPaid,
            'requires_approval' => $this->requiresApproval,
            'applicable_gender' => $this->applicableGender,
            'min_service_days' => $this->minServiceDays,
            'is_active' => $this->isActive,
            'metadata' => $this->metadata,
        ];
    }
}
