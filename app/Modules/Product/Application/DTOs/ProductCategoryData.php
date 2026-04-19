<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

class ProductCategoryData
{
    /**
     * @param array<string, mixed>|null $attributes
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public readonly int $tenant_id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $image_path = null,
        public readonly ?int $parent_id = null,
        public readonly ?string $code = null,
        public readonly ?string $path = null,
        public readonly int $depth = 0,
        public readonly bool $is_active = true,
        public readonly ?string $description = null,
        public readonly ?array $attributes = null,
        public readonly ?array $metadata = null,
        public readonly ?int $id = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            name: (string) $data['name'],
            slug: (string) $data['slug'],
            image_path: isset($data['image_path']) ? (string) $data['image_path'] : null,
            parent_id: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            code: isset($data['code']) ? (string) $data['code'] : null,
            path: isset($data['path']) ? (string) $data['path'] : null,
            depth: isset($data['depth']) ? (int) $data['depth'] : 0,
            is_active: (bool) ($data['is_active'] ?? true),
            description: isset($data['description']) ? (string) $data['description'] : null,
            attributes: isset($data['attributes']) && is_array($data['attributes']) ? $data['attributes'] : null,
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }
}
