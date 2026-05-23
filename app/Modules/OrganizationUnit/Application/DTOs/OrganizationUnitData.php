<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\DTOs;

final readonly class OrganizationUnitData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $tenantId,
        public string $name,
        public ?int $typeId = null,
        public ?int $parentId = null,
        public ?string $code = null,
        public ?string $imagePath = null,
        public ?string $path = null,
        public int $depth = 0,
        public bool $isActive = true,
        public ?string $description = null,
        public int $left = 0,
        public int $right = 0,
        public ?array $metadata = null,
    ) {}

    public static function fromArray(int|string $tenantId, array $data): self
    {
        return new self(
            tenantId: (int) $tenantId,
            name: (string) $data['name'],
            typeId: isset($data['type_id']) ? (int) $data['type_id'] : null,
            parentId: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            code: $data['code'] ?? null,
            imagePath: $data['image_path'] ?? null,
            path: $data['path'] ?? null,
            depth: (int) ($data['depth'] ?? 0),
            isActive: (bool) ($data['is_active'] ?? true),
            description: $data['description'] ?? null,
            left: (int) ($data['_lft'] ?? 0),
            right: (int) ($data['_rgt'] ?? 0),
            metadata: $data['metadata'] ?? null,
        );
    }
}
