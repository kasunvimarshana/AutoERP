<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Entities;

class ProductCategory
{
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private ?int $parentId,
        private string $name,
        private string $slug,
        private ?string $description,
        private bool $isActive,
        private int $level,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getParentId(): ?int { return $this->parentId; }
    public function getName(): string { return $this->name; }
    public function getSlug(): string { return $this->slug; }
    public function getDescription(): ?string { return $this->description; }
    public function isActive(): bool { return $this->isActive; }
    public function getLevel(): int { return $this->level; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function activate(): void { $this->isActive = true; }
    public function deactivate(): void { $this->isActive = false; }
}
