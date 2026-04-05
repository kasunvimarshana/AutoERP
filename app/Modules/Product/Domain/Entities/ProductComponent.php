<?php
declare(strict_types=1);
namespace Modules\Product\Domain\Entities;

/**
 * Represents a component line in a combo/kit product's Bill of Materials.
 *
 * Example: "Gift Box" (combo) → components: { "Chocolate Bar" × 2, "Mug" × 1 }
 */
class ProductComponent
{
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private int $parentProductId,
        private int $componentProductId,
        private float $quantity,
        private string $unit,
        private bool $isOptional,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getParentProductId(): int { return $this->parentProductId; }
    public function getComponentProductId(): int { return $this->componentProductId; }
    public function getQuantity(): float { return $this->quantity; }
    public function getUnit(): string { return $this->unit; }
    public function isOptional(): bool { return $this->isOptional; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function updateQuantity(float $quantity): void
    {
        if ($quantity <= 0) throw new \InvalidArgumentException("Component quantity must be positive.");
        $this->quantity = $quantity;
    }
}
