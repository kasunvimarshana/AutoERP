<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

use DateTimeInterface;

class Category
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly ?string $parentId,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly string $path,
        public readonly int $level,
        public readonly bool $isActive,
        public readonly DateTimeInterface $createdAt,
        public readonly DateTimeInterface $updatedAt,
    ) {}

    public function isDescendantOf(Category $category): bool
    {
        return str_starts_with($this->path, $category->path . '/');
    }

    public function isAncestorOf(Category $category): bool
    {
        return str_starts_with($category->path, $this->path . '/');
    }
}
