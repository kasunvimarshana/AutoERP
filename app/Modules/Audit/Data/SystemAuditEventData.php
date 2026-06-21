<?php

declare(strict_types=1);

namespace Modules\Audit\Data;

final readonly class SystemAuditEventData
{
    public function __construct(
        public AuditEventData $event,
        public string $actorType,
        public string $actorId,
        public string $actorName,
        public int $tenantId,
        public ?int $organizationUnitId = null,
        public ?string $applicationId = null,
    ) {}
}
