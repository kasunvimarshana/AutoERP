<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class ApprovalRequestData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $workflow_config_id,
        public readonly string $entity_type,
        public readonly int $entity_id,
        public readonly int $requested_by_user_id,
        public readonly string $status = 'pending',
        public readonly int $current_step_order = 1,
        public readonly ?int $resolved_by_user_id = null,
        public readonly ?string $comments = null,
        public readonly int $row_version = 1,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            workflow_config_id: (int) $data['workflow_config_id'],
            entity_type: (string) $data['entity_type'],
            entity_id: (int) $data['entity_id'],
            requested_by_user_id: (int) $data['requested_by_user_id'],
            status: (string) ($data['status'] ?? 'pending'),
            current_step_order: (int) ($data['current_step_order'] ?? 1),
            resolved_by_user_id: isset($data['resolved_by_user_id']) ? (int) $data['resolved_by_user_id'] : null,
            comments: isset($data['comments']) ? (string) $data['comments'] : null,
            row_version: isset($data['row_version']) ? (int) $data['row_version'] : 1,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
