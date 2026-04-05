<?php
declare(strict_types=1);
namespace Modules\Maintenance\Domain\Entities;

/** Parts/materials consumed during a service order. */
class ServiceOrderLine
{
    public function __construct(
        private ?int $id,
        private int $serviceOrderId,
        private string $description,
        private ?int $productId,
        private float $quantity,
        private float $unitCost,
        private float $totalCost,
        private ?\DateTimeInterface $createdAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getServiceOrderId(): int { return $this->serviceOrderId; }
    public function getDescription(): string { return $this->description; }
    public function getProductId(): ?int { return $this->productId; }
    public function getQuantity(): float { return $this->quantity; }
    public function getUnitCost(): float { return $this->unitCost; }
    public function getTotalCost(): float { return $this->totalCost; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
}
