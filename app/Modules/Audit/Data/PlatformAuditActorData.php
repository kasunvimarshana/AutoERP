<?php

declare(strict_types=1);

namespace Modules\Audit\Data;

final readonly class PlatformAuditActorData
{
    public function __construct(
        public string $actorType,
        public string $actorId,
        public string $actorName,
        public ?string $actorGuard = null,
        public ?string $actorProvider = null,
        public ?string $applicationId = null,
        public ?int $impersonatorUserId = null,
    ) {}
}
