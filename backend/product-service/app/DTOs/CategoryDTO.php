<?php

namespace App\DTOs;

use Illuminate\Support\Str;

class CategoryDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description = null,
        public readonly ?int $parentId = null,
        public readonly bool $isActive = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            name: $data['name'],
            slug: $data['slug'] ?? Str::slug($data['name']),
            description: $data['description'] ?? null,
            parentId: $data['parent_id'] ?? null,
            isActive: $data['is_active'] ?? true,
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'parent_id' => $this->parentId,
            'is_active' => $this->isActive,
        ];
    }
}
