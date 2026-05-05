<?php

declare(strict_types=1);

namespace Modules\Tax\Application\DTOs;

class TaxGroupData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly int $rowVersion = 1,
        public readonly ?int $id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            name: (string) $data['name'],
            description: isset($data['description']) ? (string) $data['description'] : null,
            rowVersion: isset($data['row_version']) ? (int) $data['row_version'] : 1,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
