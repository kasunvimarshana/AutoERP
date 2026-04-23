<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

class RejectLeaveRequestData
{
    public function __construct(
        public readonly int $leaveRequestId,
        public readonly int $tenantId,
        public readonly int $approverId,
        public readonly string $reason,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new static(
            leaveRequestId: (int) $data['leave_request_id'],
            tenantId: (int) $data['tenant_id'],
            approverId: (int) $data['approver_id'],
            reason: (string) $data['reason'],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'leave_request_id' => $this->leaveRequestId,
            'tenant_id' => $this->tenantId,
            'approver_id' => $this->approverId,
            'reason' => $this->reason,
        ];
    }
}
