<?php

declare(strict_types=1);

namespace Modules\Taxation\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Metadata;

class TaxRule
{
    private ?int $id;
    private int $tenantId;
    private string $name;
    private int $taxRateId;
    private string $entityType;
    private ?int $entityId;
    private ?string $jurisdiction;
    private int $priority;
    private bool $isActive;
    private ?string $description;
    private Metadata $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $name,
        int $taxRateId,
        string $entityType,
        ?int $entityId = null,
        ?string $jurisdiction = null,
        int $priority = 0,
        bool $isActive = true,
        ?string $description = null,
        ?Metadata $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->name = $name;
        $this->taxRateId = $taxRateId;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->jurisdiction = $jurisdiction;
        $this->priority = $priority;
        $this->isActive = $isActive;
        $this->description = $description;
        $this->metadata = $metadata ?? new Metadata([]);
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getTaxRateId(): int { return $this->taxRateId; }
    public function getEntityType(): string { return $this->entityType; }
    public function getEntityId(): ?int { return $this->entityId; }
    public function getJurisdiction(): ?string { return $this->jurisdiction; }
    public function getPriority(): int { return $this->priority; }
    public function isActive(): bool { return $this->isActive; }
    public function getDescription(): ?string { return $this->description; }
    public function getMetadata(): Metadata { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function updateDetails(
        ?string $name = null,
        ?int $taxRateId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $jurisdiction = null,
        ?int $priority = null,
        ?bool $isActive = null,
        ?string $description = null,
        ?Metadata $metadata = null,
    ): void {
        if ($name !== null) { $this->name = $name; }
        if ($taxRateId !== null) { $this->taxRateId = $taxRateId; }
        if ($entityType !== null) { $this->entityType = $entityType; }
        if ($entityId !== null) { $this->entityId = $entityId; }
        if ($jurisdiction !== null) { $this->jurisdiction = $jurisdiction; }
        if ($priority !== null) { $this->priority = $priority; }
        if ($isActive !== null) { $this->isActive = $isActive; }
        if ($description !== null) { $this->description = $description; }
        if ($metadata !== null) { $this->metadata = $metadata; }
        $this->updatedAt = new \DateTimeImmutable;
    }
}
