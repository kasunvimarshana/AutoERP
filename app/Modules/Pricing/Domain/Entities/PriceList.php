<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\Entities;

class PriceList
{
    private ?int $id;

    private int $tenantId;

    private string $name;

    private string $type;

    private int $currencyId;

    private bool $isDefault;

    private ?\DateTimeInterface $validFrom;

    private ?\DateTimeInterface $validTo;

    private bool $isActive;

    private int $rowVersion;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $name,
        string $type,
        int $currencyId,
        bool $isDefault = false,
        ?\DateTimeInterface $validFrom = null,
        ?\DateTimeInterface $validTo = null,
        bool $isActive = true,
        int $rowVersion = 1,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->assertType($type);
        $this->assertDateRange($validFrom, $validTo);

        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->name = trim($name);
        $this->type = $type;
        $this->currencyId = $currencyId;
        $this->isDefault = $isDefault;
        $this->validFrom = $validFrom;
        $this->validTo = $validTo;
        $this->isActive = $isActive;
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCurrencyId(): int
    {
        return $this->currencyId;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function getValidFrom(): ?\DateTimeInterface
    {
        return $this->validFrom;
    }

    public function getValidTo(): ?\DateTimeInterface
    {
        return $this->validTo;
    }

    public function isActive(): bool
    {
        return $this->isActive;
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
        string $type,
        int $currencyId,
        bool $isDefault,
        ?\DateTimeInterface $validFrom,
        ?\DateTimeInterface $validTo,
        bool $isActive,
    ): void {
        $this->assertType($type);
        $this->assertDateRange($validFrom, $validTo);

        $this->name = trim($name);
        $this->type = $type;
        $this->currencyId = $currencyId;
        $this->isDefault = $isDefault;
        $this->validFrom = $validFrom;
        $this->validTo = $validTo;
        $this->isActive = $isActive;
        $this->rowVersion++;
        $this->updatedAt = new \DateTimeImmutable;
    }

    private function assertType(string $type): void
    {
        if (! in_array($type, ['purchase', 'sales'], true)) {
            throw new \InvalidArgumentException('Price list type must be purchase or sales.');
        }
    }

    private function assertDateRange(?\DateTimeInterface $validFrom, ?\DateTimeInterface $validTo): void
    {
        if ($validFrom !== null && $validTo !== null && $validTo < $validFrom) {
            throw new \InvalidArgumentException('Price list valid_to cannot be earlier than valid_from.');
        }
    }
}
