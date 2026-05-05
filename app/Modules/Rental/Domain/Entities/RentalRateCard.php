<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\Entities;

class RentalRateCard
{
    private ?int $id;

    public function __construct(
        private int $tenantId,
        private string $code,
        private string $name,
        private string $billingUom,
        private string $rate,
        private ?int $orgUnitId = null,
        private ?int $assetId = null,
        private ?int $productId = null,
        private ?int $customerId = null,
        private ?string $depositPercentage = null,
        private int $priority = 100,
        private ?\DateTimeInterface $validFrom = null,
        private ?\DateTimeInterface $validTo = null,
        private string $status = 'active',
        private ?string $notes = null,
        private int $rowVersion = 1,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
        ?int $id = null,
    ) {
        $this->id = $id;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getOrgUnitId(): ?int { return $this->orgUnitId; }
    public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getBillingUom(): string { return $this->billingUom; }
    public function getRate(): string { return $this->rate; }
    public function getAssetId(): ?int { return $this->assetId; }
    public function getProductId(): ?int { return $this->productId; }
    public function getCustomerId(): ?int { return $this->customerId; }
    public function getDepositPercentage(): ?string { return $this->depositPercentage; }
    public function getPriority(): int { return $this->priority; }
    public function getValidFrom(): ?\DateTimeInterface { return $this->validFrom; }
    public function getValidTo(): ?\DateTimeInterface { return $this->validTo; }
    public function getStatus(): string { return $this->status; }
    public function getNotes(): ?string { return $this->notes; }
    public function getRowVersion(): int { return $this->rowVersion; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function update(array $fields): void
    {
        foreach ($fields as $field => $value) {
            if (property_exists($this, $field) && $field !== 'id' && $field !== 'tenantId') {
                $this->{$field} = $value;
            }
        }
        $this->rowVersion++;
    }
}
