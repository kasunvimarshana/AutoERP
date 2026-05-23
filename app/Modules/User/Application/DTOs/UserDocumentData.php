<?php

declare(strict_types=1);

namespace Modules\User\Application\DTOs;

final readonly class UserDocumentData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $userId,
        public string $name,
        public string $filePath,
        public ?string $mimeType = null,
        public ?int $size = null,
        public ?string $type = null,
        public ?int $tenantId = null,
        public ?int $organizationUnitId = null,
        public ?array $metadata = null,
    )
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) $data['user_id'],
            name: (string) $data['name'],
            filePath: (string) $data['file_path'],
            mimeType: $data['mime_type'] ?? null,
            size: isset($data['size']) ? (int) $data['size'] : null,
            type: $data['type'] ?? null,
            tenantId: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null,
            organizationUnitId: isset($data['organization_unit_id']) ? (int) $data['organization_unit_id'] : null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
