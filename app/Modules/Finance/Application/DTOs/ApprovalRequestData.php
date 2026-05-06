<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class ApprovalRequestData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $workflowConfigId,
        public readonly string $entityType,
        public readonly int $entityId,
        public readonly int $requestedByUserId,
        public readonly string $status = 'pending',
        public readonly int $current_step_order = 1,
        public readonly ?int $resolvedByUserId = null,
        public readonly ?string $comments = null,
        public readonly int $rowVersion = 1,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            workflowConfigId: (int) $data['workflow_config_id'],
            entityType: (string) $data['entity_type'],
            entityId: (int) $data['entity_id'],
            requestedByUserId: (int) $data['requested_by_user_id'],
            status: (string) ($data['status'] ?? 'pending'),
            current_step_order: (int) ($data['current_step_order'] ?? 1),
            resolvedByUserId: isset($data['resolved_by_user_id']) ? (int) $data['resolved_by_user_id'] : null,
            comments: isset($data['comments']) ? (string) $data['comments'] : null,
            rowVersion: isset($data['row_version']) ? (int) $data['row_version'] : 1,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
