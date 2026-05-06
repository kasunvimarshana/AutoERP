<?php

declare(strict_types=1);

namespace Modules\Tax\Domain\Entities;

class TaxRule
{
    private ?int $id;

    private int $tenantId;

    private int $taxGroupId;

    private ?int $productCategoryId;

    private ?string $partyType;

    private ?string $region;

    private int $priority;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $taxGroupId,
        ?int $productCategoryId = null,
        ?string $partyType = null,
        ?string $region = null,
        int $priority = 0,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->assertPartyType($partyType);

        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->taxGroupId = $taxGroupId;
        $this->productCategoryId = $productCategoryId;
        $this->partyType = $partyType;
        $this->region = $region !== null ? trim($region) : null;
        $this->priority = max(0, $priority);
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getTaxGroupId(): int
    {
        return $this->taxGroupId;
    }

    public function getProductCategoryId(): ?int
    {
        return $this->productCategoryId;
    }

    public function getPartyType(): ?string
    {
        return $this->partyType;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function update(
        int $taxGroupId,
        ?int $productCategoryId,
        ?string $partyType,
        ?string $region,
        int $priority,
    ): void {
        $this->assertPartyType($partyType);

        $this->taxGroupId = $taxGroupId;
        $this->productCategoryId = $productCategoryId;
        $this->partyType = $partyType;
        $this->region = $region !== null ? trim($region) : null;
        $this->priority = max(0, $priority);
        $this->updatedAt = new \DateTimeImmutable;
    }

    private function assertPartyType(?string $partyType): void
    {
        if ($partyType === null) {
            return;
        }

        if (! in_array($partyType, ['customer', 'supplier'], true)) {
            throw new \InvalidArgumentException('Tax rule party_type must be customer or supplier.');
        }
    }
}
