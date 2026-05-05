<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class NumberingSequenceData
{
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $module,
        public readonly string $document_type,
        public readonly ?string $prefix = null,
        public readonly ?string $suffix = null,
        public readonly int $next_number = 1,
        public readonly int $padding = 5,
        public readonly bool $is_active = true,
        public readonly int $row_version = 1,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            module: (string) $data['module'],
            document_type: (string) $data['document_type'],
            prefix: isset($data['prefix']) ? (string) $data['prefix'] : null,
            suffix: isset($data['suffix']) ? (string) $data['suffix'] : null,
            next_number: (int) ($data['next_number'] ?? 1),
            padding: (int) ($data['padding'] ?? 5),
            is_active: (bool) ($data['is_active'] ?? true),
            row_version: isset($data['row_version']) ? (int) $data['row_version'] : 1,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
