<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class Category
{
    public function __construct(
        private readonly int $id,
        private readonly int $tenantId,
        private string $name,
        private ?string $description,
        private ?int $parentId,
        private string $slug,
        private bool $isActive,
    ) {}

    public function getId(): int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getParentId(): ?int { return $this->parentId; }
    public function getSlug(): string { return $this->slug; }
    public function isActive(): bool { return $this->isActive; }
    public function isRootCategory(): bool { return $this->parentId === null; }
}
