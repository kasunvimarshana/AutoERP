<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\Entities;
class Account {
    public function __construct(
        private ?int $id, private int $tenantId, private string $code, private string $name,
        private string $type, // asset|liability|equity|revenue|expense
        private string $subtype, private ?int $parentId, private float $balance,
        private string $currency, private bool $isActive, private ?string $description,
        private ?\DateTimeInterface $createdAt, private ?\DateTimeInterface $updatedAt,
    ) {}
    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getType(): string { return $this->type; }
    public function getSubtype(): string { return $this->subtype; }
    public function getParentId(): ?int { return $this->parentId; }
    public function getBalance(): float { return $this->balance; }
    public function getCurrency(): string { return $this->currency; }
    public function isActive(): bool { return $this->isActive; }
    public function getDescription(): ?string { return $this->description; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function debit(float $amount): void { $this->balance += $amount; }
    public function credit(float $amount): void { $this->balance -= $amount; }
}
