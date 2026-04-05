<?php
declare(strict_types=1);
namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Contracts\ConsumeValuationLayersServiceInterface;
use Modules\Inventory\Domain\Entities\InventoryLevel;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryValuationLayerRepositoryInterface;

class ConsumeValuationLayersService implements ConsumeValuationLayersServiceInterface
{
    public function __construct(
        private readonly InventoryValuationLayerRepositoryInterface $layerRepo,
    ) {}

    public function execute(
        int $tenantId,
        int $productId,
        int $warehouseId,
        float $quantity,
        string $method,
    ): float {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException("Consume quantity must be positive.");
        }

        $layers    = $this->layerRepo->findLayersForConsumption($tenantId, $productId, $warehouseId, $method);
        $remaining = $quantity;
        $totalCost = 0.0;

        foreach ($layers as $layer) {
            if ($remaining <= InventoryLevel::FLOAT_TOLERANCE) break;
            $consume = min($remaining, $layer->getQuantityRemaining());
            $layer->consume($consume);
            $this->layerRepo->update($layer->getId(), [
                'quantity_remaining' => $layer->getQuantityRemaining(),
            ]);
            $totalCost += $consume * $layer->getUnitCost();
            $remaining -= $consume;
        }

        if ($remaining > InventoryLevel::FLOAT_TOLERANCE) {
            throw new \DomainException(
                "Insufficient valuation layers. Short by {$remaining} units."
            );
        }

        return $quantity > 0 ? $totalCost / $quantity : 0.0;
    }
}
