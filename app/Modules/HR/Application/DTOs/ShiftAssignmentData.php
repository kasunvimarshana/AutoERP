<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

class ShiftAssignmentData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $employeeId,
        public readonly int $shiftId,
        public readonly string $effectiveFrom,
        public readonly ?string $effectiveTo = null,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new static(
            tenantId: (int) $data['tenant_id'],
            employeeId: (int) $data['employee_id'],
            shiftId: (int) $data['shift_id'],
            effectiveFrom: (string) $data['effective_from'],
            effectiveTo: isset($data['effective_to']) ? (string) $data['effective_to'] : null,
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
            'shift_id' => $this->shiftId,
            'effective_from' => $this->effectiveFrom,
            'effective_to' => $this->effectiveTo,
        ];
    }
}
