<?php

declare(strict_types=1);

namespace Modules\Tax\Domain\Entities;

class TaxRate
{
    private ?int $id;

    private int $tenantId;

    private int $taxGroupId;

    private string $name;

    private string $rate;

    private string $type;

    private ?int $accountId;

    private bool $isCompound;

    private bool $isActive;

    private ?\DateTimeInterface $validFrom;

    private ?\DateTimeInterface $validTo;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    private int $rowVersion;

    public function __construct(
        int $tenantId,
        int $taxGroupId,
        string $name,
        string $rate,
        string $type = 'percentage',
        ?int $accountId = null,
        bool $isCompound = false,
        bool $isActive = true,
        ?\DateTimeInterface $validFrom = null,
        ?\DateTimeInterface $validTo = null,
        int $rowVersion = 1,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->assertType($type);
        $this->assertRate($rate);
        $this->assertDateRange($validFrom, $validTo);

        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->taxGroupId = $taxGroupId;
        $this->name = trim($name);
        $this->rate = $rate;
        $this->type = $type;
        $this->accountId = $accountId;
        $this->isCompound = $isCompound;
        $this->isActive = $isActive;
        $this->validFrom = $validFrom;
        $this->validTo = $validTo;
        $this->rowVersion = $rowVersion;
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getRate(): string
    {
        return $this->rate;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAccountId(): ?int
    {
        return $this->accountId;
    }

    public function isCompound(): bool
    {
        return $this->isCompound;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getValidFrom(): ?\DateTimeInterface
    {
        return $this->validFrom;
    }

    public function getValidTo(): ?\DateTimeInterface
    {
        return $this->validTo;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getRowVersion(): int
    {
        return $this->rowVersion;
    }

    public function update(
        string $name,
        string $rate,
        string $type,
        ?int $accountId,
        bool $isCompound,
        bool $isActive,
        ?\DateTimeInterface $validFrom,
        ?\DateTimeInterface $validTo,
    ): void {
        $this->assertType($type);
        $this->assertRate($rate);
        $this->assertDateRange($validFrom, $validTo);

        $this->name = trim($name);
        $this->rate = $rate;
        $this->type = $type;
        $this->accountId = $accountId;
        $this->isCompound = $isCompound;
        $this->isActive = $isActive;
        $this->validFrom = $validFrom;
        $this->validTo = $validTo;
        $this->rowVersion++;
        $this->updatedAt = new \DateTimeImmutable;
    }

    private function assertType(string $type): void
    {
        if (! in_array($type, ['percentage', 'fixed'], true)) {
            throw new \InvalidArgumentException('Tax rate type must be percentage or fixed.');
        }
    }

    private function assertRate(string $rate): void
    {
        if (! is_numeric($rate) || (float) $rate < 0) {
            throw new \InvalidArgumentException('Tax rate must be a non-negative number.');
        }
    }

    private function assertDateRange(?\DateTimeInterface $validFrom, ?\DateTimeInterface $validTo): void
    {
        if ($validFrom !== null && $validTo !== null && $validTo < $validFrom) {
            throw new \InvalidArgumentException('Tax rate valid_to cannot be earlier than valid_from.');
        }
    }
}
