<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

final class Category
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $slug,
        public readonly bool $isActive,
        public readonly int $sortOrder,
        public readonly ?int $parentId = null,
        public readonly ?string $description = null,
        public readonly ?string $imagePath = null,
        public readonly ?array $metadata = null,
        public readonly array $children = [],
    ) {}

    public function isRoot(): bool
    {
        return $this->parentId === null;
    }

    public function hasChildren(): bool
    {
        return ! empty($this->children);
    }
}
