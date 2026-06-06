<?php

declare(strict_types=1);

namespace Modules\Audit\DTOs;

final readonly class AuditLogEntityChangeData
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string, mixed>|null  $metadata
     * @param  list<string>|null  $tags
     */
    public function __construct(
        public string $event,
        public string $entityType,
        public string $entityId,
        public ?array $oldValues = null,
        public ?array $newValues = null,
        public ?int $tenantId = null,
        public ?int $organizationUnitId = null,
        public ?int $userId = null,
        public ?array $metadata = null,
        public ?string $url = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?array $tags = null,
        public ?string $occurredAt = null,
    ) {}
}
