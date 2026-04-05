<?php
declare(strict_types=1);
namespace Modules\Configuration\Domain\Entities;

class OrgUnit
{
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private ?int $parentId,
        private string $name,
        private string $code,
        private string $type,
        private int $level,
        private bool $isActive,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getParentId(): ?int { return $this->parentId; }
    public function getName(): string { return $this->name; }
    public function getCode(): string { return $this->code; }
    public function getType(): string { return $this->type; }
    public function getLevel(): int { return $this->level; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function activate(): void { $this->isActive = true; }
    public function deactivate(): void { $this->isActive = false; }
    public function rename(string $name): void { $this->name = $name; }
    public function changeParent(?int $parentId): void { $this->parentId = $parentId; }
}
