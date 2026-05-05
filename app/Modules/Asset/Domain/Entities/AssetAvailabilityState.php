<?php

declare(strict_types=1);

namespace Modules\Asset\Domain\Entities;

class AssetAvailabilityState
{
    private ?int $id;

    private int $tenantId;

    private ?int $orgUnitId;

    private int $assetId;

    private string $availabilityStatus;

    private ?string $reasonCode;

    private ?string $sourceType;

    private ?int $sourceId;

    private ?int $updatedBy;

    private \DateTimeInterface $effectiveFrom;

    private ?\DateTimeInterface $effectiveTo;

    private ?array $metadata;

    private int $rowVersion;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $assetId,
        string $availabilityStatus,
        ?int $orgUnitId = null,
        ?string $reasonCode = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?int $updatedBy = null,
        ?\DateTimeInterface $effectiveFrom = null,
        ?\DateTimeInterface $effectiveTo = null,
        ?array $metadata = null,
        int $rowVersion = 1,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->assertAvailabilityStatus($availabilityStatus);

        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->orgUnitId = $orgUnitId;
        $this->assetId = $assetId;
        $this->availabilityStatus = $availabilityStatus;
        $this->reasonCode = $reasonCode;
        $this->sourceType = $sourceType;
        $this->sourceId = $sourceId;
        $this->updatedBy = $updatedBy;
        $this->effectiveFrom = $effectiveFrom ?? new \DateTimeImmutable;
        $this->effectiveTo = $effectiveTo;
        $this->metadata = $metadata;
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

    public function getOrgUnitId(): ?int
    {
        return $this->orgUnitId;
    }

    public function getAssetId(): int
    {
        return $this->assetId;
    }

    public function getAvailabilityStatus(): string
    {
        return $this->availabilityStatus;
    }

    public function getReasonCode(): ?string
    {
        return $this->reasonCode;
    }

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    public function getSourceId(): ?int
    {
        return $this->sourceId;
    }

    public function getUpdatedBy(): ?int
    {
        return $this->updatedBy;
    }

    public function getEffectiveFrom(): \DateTimeInterface
    {
        return $this->effectiveFrom;
    }

    public function getEffectiveTo(): ?\DateTimeInterface
    {
        return $this->effectiveTo;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getRowVersion(): int
    {
        return $this->rowVersion;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    private function assertAvailabilityStatus(string $status): void
    {
        $allowed = ['available', 'reserved', 'rented', 'in_service', 'internal_use', 'blocked'];

        if (! in_array($status, $allowed, true)) {
            throw new \InvalidArgumentException('Asset availability status is invalid.');
        }
    }
}
