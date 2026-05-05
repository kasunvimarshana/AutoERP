<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class CostCenterData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $code,
        public readonly string $name,
        public readonly ?int $parent_id = null,
        public readonly ?string $description = null,
        public readonly bool $is_active = true,
        public readonly ?string $path = null,
        public readonly int $depth = 0,
        public readonly int $rowVersion = 1,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            code: (string) $data['code'],
            name: (string) $data['name'],
            parent_id: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            is_active: (bool) ($data['is_active'] ?? true),
            path: isset($data['path']) ? (string) $data['path'] : null,
            depth: (int) ($data['depth'] ?? 0),
            rowVersion: (int) ($data['row_version'] ?? 0),
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
