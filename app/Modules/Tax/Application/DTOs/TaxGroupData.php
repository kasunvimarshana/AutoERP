<?php

declare(strict_types=1);

namespace Modules\Tax\Application\DTOs;

class TaxGroupData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly int $row_version = 1,
        public readonly ?int $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            name: (string) $data['name'],
            description: isset($data['description']) ? (string) $data['description'] : null,
            row_version: isset($data['row_version']) ? (int) $data['row_version'] : 1,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
