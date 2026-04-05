<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\Entities;

class ExpenseCategory
{
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private string $name,
        private string $code,
        private ?int $parentId,
        private ?int $accountId,
        private bool $isActive,
        private ?string $description,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getCode(): string { return $this->code; }
    public function getParentId(): ?int { return $this->parentId; }
    public function getAccountId(): ?int { return $this->accountId; }
    public function isActive(): bool { return $this->isActive; }
    public function getDescription(): ?string { return $this->description; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function isTopLevel(): bool { return $this->parentId === null; }
    public function deactivate(): void { $this->isActive = false; }
}
