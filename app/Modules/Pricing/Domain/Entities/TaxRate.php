<?php
declare(strict_types=1);
namespace Modules\Pricing\Domain\Entities;
class TaxRate {
    public function __construct(
        private ?int $id, private int $tenantId, private string $name, private string $code,
        private float $rate, private string $type, private bool $isCompound, private bool $isActive,
        private ?string $appliesTo, // product|service|all
        private ?\DateTimeInterface $createdAt, private ?\DateTimeInterface $updatedAt,
    ) {}
    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getCode(): string { return $this->code; }
    public function getRate(): float { return $this->rate; }
    public function getType(): string { return $this->type; }
    public function isCompound(): bool { return $this->isCompound; }
    public function isActive(): bool { return $this->isActive; }
    public function getAppliesTo(): ?string { return $this->appliesTo; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function calculate(float $baseAmount): float { return $baseAmount * ($this->rate / 100); }
}
