<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class ApprovalWorkflowConfigData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $module,
        public readonly string $entity_type,
        public readonly string $name,
        public readonly array $steps,
        public readonly ?float $min_amount = null,
        public readonly ?float $max_amount = null,
        public readonly bool $is_active = true,
        public readonly int $row_version = 1,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            module: (string) $data['module'],
            entity_type: (string) $data['entity_type'],
            name: (string) $data['name'],
            steps: (array) $data['steps'],
            min_amount: isset($data['min_amount']) ? (float) $data['min_amount'] : null,
            max_amount: isset($data['max_amount']) ? (float) $data['max_amount'] : null,
            is_active: (bool) ($data['is_active'] ?? true),
            row_version: isset($data['row_version']) ? (int) $data['row_version'] : 1,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
