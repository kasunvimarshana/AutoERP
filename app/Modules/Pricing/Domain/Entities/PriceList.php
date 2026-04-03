<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;

class PriceList
{
    private ?int $id;
    private int $tenantId;
    private string $name;
    private string $code;
    private string $type;
    private string $pricingMethod;
    private string $currencyCode;
    private ?\DateTimeInterface $startDate;
    private ?\DateTimeInterface $endDate;
    private bool $isActive;
    private ?string $description;
    private Metadata $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $name,
        string $code,
        string $type,
        string $pricingMethod = 'fixed',
        string $currencyCode = 'USD',
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null,
        bool $isActive = true,
        ?string $description = null,
        ?Metadata $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id            = $id;
        $this->tenantId      = $tenantId;
        $this->name          = $name;
        $this->code          = $code;
        $this->type          = $type;
        $this->pricingMethod = $pricingMethod;
        $this->currencyCode  = $currencyCode;
        $this->startDate     = $startDate;
        $this->endDate       = $endDate;
        $this->isActive      = $isActive;
        $this->description   = $description;
        $this->metadata      = $metadata ?? new Metadata([]);
        $this->createdAt     = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt     = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getCode(): string { return $this->code; }
    public function getType(): string { return $this->type; }
    public function getPricingMethod(): string { return $this->pricingMethod; }
    public function getCurrencyCode(): string { return $this->currencyCode; }
    public function getStartDate(): ?\DateTimeInterface { return $this->startDate; }
    public function getEndDate(): ?\DateTimeInterface { return $this->endDate; }
    public function isActive(): bool { return $this->isActive; }
    public function getDescription(): ?string { return $this->description; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function activate(): void
    {
        $this->isActive  = true;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function deactivate(): void
    {
        $this->isActive  = false;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function isExpired(): bool
    {
        if ($this->endDate === null) {
            return false;
        }

        return $this->endDate < new \DateTimeImmutable;
    }

    public function isValid(): bool
    {
        if (! $this->isActive) {
            return false;
        }

        $now = new \DateTimeImmutable;

        if ($this->startDate !== null && $this->startDate > $now) {
            return false;
        }

        if ($this->endDate !== null && $this->endDate < $now) {
            return false;
        }

        return true;
    }

    public function updateDetails(
        string $name,
        string $code,
        string $type,
        string $pricingMethod,
        string $currencyCode,
        ?\DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate,
        bool $isActive,
        ?string $description,
        ?Metadata $metadata,
    ): void {
        $this->name          = $name;
        $this->code          = $code;
        $this->type          = $type;
        $this->pricingMethod = $pricingMethod;
        $this->currencyCode  = $currencyCode;
        $this->startDate     = $startDate;
        $this->endDate       = $endDate;
        $this->isActive      = $isActive;
        $this->description   = $description;
        if ($metadata !== null) {
            $this->metadata = $metadata;
        }
        $this->updatedAt = new \DateTimeImmutable;
    }
}
