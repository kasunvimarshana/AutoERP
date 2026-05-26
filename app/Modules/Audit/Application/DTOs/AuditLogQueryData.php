<?php

declare(strict_types=1);

namespace Modules\Audit\Application\DTOs;

final readonly class AuditLogQueryData
{
    public function __construct(
        public ?int $tenantId,
        public ?int $organizationUnitId,
        public ?int $userId,
        public ?string $event,
        public ?string $auditableType,
        public ?string $auditableId,
        public ?string $fromDate,
        public ?string $toDate,
        public int $perPage,
        public int $page,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            isset($payload['tenant_id']) && is_numeric($payload['tenant_id']) ? (int) $payload['tenant_id'] : null,
            isset($payload['organization_unit_id']) && is_numeric($payload['organization_unit_id'])
                ? (int) $payload['organization_unit_id']
                : null,
            isset($payload['user_id']) && is_numeric($payload['user_id']) ? (int) $payload['user_id'] : null,
            isset($payload['event']) ? (string) $payload['event'] : null,
            isset($payload['auditable_type']) ? (string) $payload['auditable_type'] : null,
            isset($payload['auditable_id']) ? (string) $payload['auditable_id'] : null,
            isset($payload['from_date']) ? (string) $payload['from_date'] : null,
            isset($payload['to_date']) ? (string) $payload['to_date'] : null,
            (int) ($payload['per_page'] ?? $payload['perPage'] ?? 0),
            (int) ($payload['page'] ?? 0),
        );
    }
}
