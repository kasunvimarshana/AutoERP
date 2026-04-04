<?php
namespace Modules\Inventory\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class InventoryValuationLayer extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly int $warehouseId,
        public readonly string $valuationMethod,
        public float $quantity,
        public float $remainingQuantity,
        public float $unitCost,
        public float $totalCost,
        public readonly ?int $batchId = null,
        public readonly ?\DateTimeImmutable $receiptDate = null,
        public readonly ?int $referenceId = null,
        public readonly ?string $referenceType = null,
    ) {
        parent::__construct($id);
    }

    /**
     * Consume up to $qty units from this layer.
     * Returns the cost consumed.
     *
     * @throws \DomainException if the layer has no remaining quantity
     */
    public function consume(float $qty): float
    {
        if ($this->remainingQuantity <= 0) {
            return 0.0;
        }

        $consumed = min($qty, $this->remainingQuantity);
        $cost = $consumed * $this->unitCost;

        $this->remainingQuantity -= $consumed;
        $this->totalCost = $this->remainingQuantity * $this->unitCost;

        return $cost;
    }

    /** Whether this layer still has stock available for consumption. */
    public function hasStock(): bool
    {
        return $this->remainingQuantity > 0;
    }
}
