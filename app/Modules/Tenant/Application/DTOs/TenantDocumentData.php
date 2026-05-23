<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\DTOs;

final readonly class TenantDocumentData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $tenantId,
        public string $name,
        public string $filePath,
        public ?string $mimeType = null,
        public ?int $size = null,
        public ?string $type = null,
        public ?array $metadata = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(int|string $tenantId, array $data): self
    {
        return new self(
            tenantId: (int) $tenantId,
            name: (string) $data['name'],
            filePath: (string) $data['file_path'],
            mimeType: $data['mime_type'] ?? null,
            size: isset($data['size']) ? (int) $data['size'] : null,
            type: $data['type'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
