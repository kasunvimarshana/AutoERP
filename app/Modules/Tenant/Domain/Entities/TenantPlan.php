<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Entities;

class TenantPlan
{
    private ?int $id;

    private string $name;

    private string $slug;

    /** @var array<string, mixed>|null */
    private ?array $features;

    /** @var array<string, mixed>|null */
    private ?array $limits;

    private string $price;

    private string $currencyCode;

    private string $billingInterval;

    private bool $isActive;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    /**
     * @param array<string, mixed>|null $features
     * @param array<string, mixed>|null $limits
     */
    public function __construct(
        string $name,
        string $slug,
        ?array $features = null,
        ?array $limits = null,
        string $price = '0.0000',
        string $currencyCode = 'USD',
        string $billingInterval = 'month',
        bool $isActive = true,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->slug = $slug;
        $this->features = $features;
        $this->limits = $limits;
        $this->price = $price;
        $this->currencyCode = $currencyCode;
        $this->billingInterval = $billingInterval;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getFeatures(): ?array
    {
        return $this->features;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getLimits(): ?array
    {
        return $this->limits;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function getBillingInterval(): string
    {
        return $this->billingInterval;
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

    /**
     * @param array<string, mixed>|null $features
     * @param array<string, mixed>|null $limits
     */
    public function update(
        string $name,
        string $slug,
        ?array $features,
        ?array $limits,
        string $price,
        string $currencyCode,
        string $billingInterval,
        bool $isActive
    ): void {
        $this->name = $name;
        $this->slug = $slug;
        $this->features = $features;
        $this->limits = $limits;
        $this->price = $price;
        $this->currencyCode = $currencyCode;
        $this->billingInterval = $billingInterval;
        $this->isActive = $isActive;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
