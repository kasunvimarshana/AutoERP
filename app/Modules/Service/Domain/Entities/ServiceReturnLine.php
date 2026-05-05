<?php

declare(strict_types=1);

namespace Modules\Service\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;

class ServiceReturnLine
{
    public function __construct(
        private readonly int $tenantId,
        private readonly int $serviceReturnId,
        private ?int $servicePartId = null,
        private ?int $productId = null,
        private ?string $description = null,
        private float $quantity = 0.0,
        private ?int $uomId = null,
        private float $unitAmount = 0.0,
        private float $lineAmount = 0.0,
        private ?string $stockReferenceType = null,
        private ?int $stockReferenceId = null,
        private ?array $metadata = null,
        private ?DateTimeImmutable $createdAt = null,
        private ?DateTimeImmutable $updatedAt = null,
        private ?int $id = null,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getServiceReturnId(): int { return $this->serviceReturnId; }
    public function getServicePartId(): ?int { return $this->servicePartId; }
    public function getProductId(): ?int { return $this->productId; }
    public function getDescription(): ?string { return $this->description; }
    public function getQuantity(): float { return $this->quantity; }
    public function getUomId(): ?int { return $this->uomId; }
    public function getUnitAmount(): float { return $this->unitAmount; }
    public function getLineAmount(): float { return $this->lineAmount; }
    public function getStockReferenceType(): ?string { return $this->stockReferenceType; }
    public function getStockReferenceId(): ?int { return $this->stockReferenceId; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function getCreatedAt(): ?DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTimeImmutable { return $this->updatedAt; }
}
