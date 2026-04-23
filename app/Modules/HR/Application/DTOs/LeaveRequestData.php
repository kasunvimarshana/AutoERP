<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

class LeaveRequestData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly int $employeeId,
        public readonly int $leaveTypeId,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly float $totalDays,
        public readonly string $reason,
        public readonly ?string $attachmentPath = null,
        public readonly array $metadata = [],
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new static(
            tenantId: (int) $data['tenant_id'],
            employeeId: (int) $data['employee_id'],
            leaveTypeId: (int) $data['leave_type_id'],
            startDate: (string) $data['start_date'],
            endDate: (string) $data['end_date'],
            totalDays: (float) $data['total_days'],
            reason: (string) $data['reason'],
            attachmentPath: isset($data['attachment_path']) ? (string) $data['attachment_path'] : null,
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
            'employee_id' => $this->employeeId,
            'leave_type_id' => $this->leaveTypeId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'total_days' => $this->totalDays,
            'reason' => $this->reason,
            'attachment_path' => $this->attachmentPath,
            'metadata' => $this->metadata,
        ];
    }
}
