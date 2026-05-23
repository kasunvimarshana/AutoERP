<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\DTOs;

final readonly class OrganizationUnitDocumentData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $tenantId,
        public int $organizationUnitId,
        public string $name,
        public string $filePath,
        public ?string $mimeType = null,
        public ?int $size = null,
        public ?string $type = null,
        public ?array $metadata = null,
    ) {}

    public static function fromArray(int|string $tenantId, int|string $organizationUnitId, array $data): self
    {
        return new self(
            tenantId: (int) $tenantId,
            organizationUnitId: (int) $organizationUnitId,
            name: (string) $data['name'],
            filePath: (string) $data['file_path'],
            mimeType: $data['mime_type'] ?? null,
            size: isset($data['size']) ? (int) $data['size'] : null,
            type: $data['type'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
