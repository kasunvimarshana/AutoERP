<?php
declare(strict_types=1);
namespace Modules\Pricing\Domain\Entities;
class PriceList {
    public function __construct(
        private ?int $id, private int $tenantId, private string $name, private string $currency,
        private float $discountPercent, private bool $isDefault, private bool $isActive,
        private ?string $validFrom, private ?string $validTo,
        private ?\DateTimeInterface $createdAt, private ?\DateTimeInterface $updatedAt,
    ) {}
    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getCurrency(): string { return $this->currency; }
    public function getDiscountPercent(): float { return $this->discountPercent; }
    public function isDefault(): bool { return $this->isDefault; }
    public function isActive(): bool { return $this->isActive; }
    public function getValidFrom(): ?string { return $this->validFrom; }
    public function getValidTo(): ?string { return $this->validTo; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
}
