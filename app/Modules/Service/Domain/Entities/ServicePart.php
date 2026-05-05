<?php

declare(strict_types=1);

namespace Modules\Service\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;

class ServicePart
{
    private const VALID_PART_SOURCES = ['inventory', 'non_inventory', 'special_order'];

    public function __construct(
        private readonly int $tenantId,
        private readonly int $serviceWorkOrderId,
        private ?int $orgUnitId = null,
        private ?int $serviceTaskId = null,
        private ?int $productId = null,
        private string $partSource = 'inventory',
        private ?string $description = null,
        private float $quantity = 0.0,
        private ?int $uomId = null,
        private float $unitCost = 0.0,
        private float $unitPrice = 0.0,
        private float $lineAmount = 0.0,
        private bool $isReturned = false,
        private bool $isWarrantyCovered = false,
        private ?string $stockReferenceType = null,
        private ?int $stockReferenceId = null,
        private ?array $metadata = null,
        private int $rowVersion = 1,
        private ?DateTimeImmutable $createdAt = null,
        private ?DateTimeImmutable $updatedAt = null,
        private ?int $id = null,
    ) {
        if (! in_array($this->partSource, self::VALID_PART_SOURCES, true)) {
            throw new InvalidArgumentException("Invalid part source: {$this->partSource}");
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getOrgUnitId(): ?int { return $this->orgUnitId; }
    public function getServiceWorkOrderId(): int { return $this->serviceWorkOrderId; }
    public function getServiceTaskId(): ?int { return $this->serviceTaskId; }
    public function getProductId(): ?int { return $this->productId; }
    public function getPartSource(): string { return $this->partSource; }
    public function getDescription(): ?string { return $this->description; }
    public function getQuantity(): float { return $this->quantity; }
    public function getUomId(): ?int { return $this->uomId; }
    public function getUnitCost(): float { return $this->unitCost; }
    public function getUnitPrice(): float { return $this->unitPrice; }
    public function getLineAmount(): float { return $this->lineAmount; }
    public function isReturned(): bool { return $this->isReturned; }
    public function isWarrantyCovered(): bool { return $this->isWarrantyCovered; }
    public function getStockReferenceType(): ?string { return $this->stockReferenceType; }
    public function getStockReferenceId(): ?int { return $this->stockReferenceId; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function getRowVersion(): int { return $this->rowVersion; }
    public function getCreatedAt(): ?DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTimeImmutable { return $this->updatedAt; }
}
