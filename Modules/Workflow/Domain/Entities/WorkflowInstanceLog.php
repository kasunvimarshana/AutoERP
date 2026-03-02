<?php

declare(strict_types=1);

namespace Modules\Workflow\Domain\Entities;

final class WorkflowInstanceLog
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $workflowInstanceId,
        public readonly int $tenantId,
        public readonly ?int $fromStateId,
        public readonly int $toStateId,
        public readonly ?int $transitionId,
        public readonly ?string $comment,
        public readonly int $actorUserId,
        public readonly string $actedAt,
        public readonly ?string $createdAt,
    ) {}
}
