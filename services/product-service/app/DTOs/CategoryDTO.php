<?php

namespace App\DTOs;

use Illuminate\Support\Str;

class CategoryDTO
{
    public function __construct(
        public readonly string $tenantId,
        public readonly ?int $parentId,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly bool $isActive,
        public readonly int $sortOrder,
        public readonly ?array $metadata,
    ) {}

    public static function fromRequest(array $data, string $tenantId): self
    {
        return new self(
            tenantId:    $tenantId,
            parentId:    $data['parent_id'] ?? null,
            name:        $data['name'],
            slug:        $data['slug'] ?? Str::slug($data['name']),
            description: $data['description'] ?? null,
            isActive:    $data['is_active'] ?? true,
            sortOrder:   $data['sort_order'] ?? 0,
            metadata:    $data['metadata'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id'   => $this->tenantId,
            'parent_id'   => $this->parentId,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'is_active'   => $this->isActive,
            'sort_order'  => $this->sortOrder,
            'metadata'    => $this->metadata,
        ];
    }
}
