<?php

declare(strict_types=1);

namespace Modules\Tax\Domain\Entities;

class TransactionTax
{
    private ?int $id;

    private int $tenantId;

    private string $referenceType;

    private int $referenceId;

    private int $taxRateId;

    private string $taxableAmount;

    private string $taxAmount;

    private int $taxAccountId;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $referenceType,
        int $referenceId,
        int $taxRateId,
        string $taxableAmount,
        string $taxAmount,
        int $taxAccountId,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->referenceType = trim($referenceType);
        $this->referenceId = $referenceId;
        $this->taxRateId = $taxRateId;
        $this->taxableAmount = $taxableAmount;
        $this->taxAmount = $taxAmount;
        $this->taxAccountId = $taxAccountId;
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

    public function getReferenceType(): string
    {
        return $this->referenceType;
    }

    public function getReferenceId(): int
    {
        return $this->referenceId;
    }

    public function getTaxRateId(): int
    {
        return $this->taxRateId;
    }

    public function getTaxableAmount(): string
    {
        return $this->taxableAmount;
    }

    public function getTaxAmount(): string
    {
        return $this->taxAmount;
    }

    public function getTaxAccountId(): int
    {
        return $this->taxAccountId;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }
}
