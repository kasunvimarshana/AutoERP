<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class ApprovalWorkflowConfigData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $module,
        public readonly string $entityType,
        public readonly string $name,
        public readonly array $steps,
        public readonly ?float $minAmount = null,
        public readonly ?float $maxAmount = null,
        public readonly bool $isActive = true,
        public readonly int $rowVersion = 1,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            module: (string) $data['module'],
            entityType: (string) $data['entity_type'],
            name: (string) $data['name'],
            steps: (array) $data['steps'],
            minAmount: isset($data['min_amount']) ? (float) $data['min_amount'] : null,
            maxAmount: isset($data['max_amount']) ? (float) $data['max_amount'] : null,
            isActive: (bool) ($data['is_active'] ?? true),
            rowVersion: isset($data['row_version']) ? (int) $data['row_version'] : 1,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
