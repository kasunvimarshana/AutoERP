<?php

declare(strict_types=1);

namespace Modules\Finance\Application\DTOs;

class NumberingSequenceData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $module,
        public readonly string $documentType,
        public readonly ?string $prefix = null,
        public readonly ?string $suffix = null,
        public readonly int $next_number = 1,
        public readonly int $padding = 5,
        public readonly bool $isActive = true,
        public readonly int $rowVersion = 1,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            module: (string) $data['module'],
            documentType: (string) $data['document_type'],
            prefix: isset($data['prefix']) ? (string) $data['prefix'] : null,
            suffix: isset($data['suffix']) ? (string) $data['suffix'] : null,
            next_number: (int) ($data['next_number'] ?? 1),
            padding: (int) ($data['padding'] ?? 5),
            isActive: (bool) ($data['is_active'] ?? true),
            rowVersion: isset($data['row_version']) ? (int) $data['row_version'] : 1,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
