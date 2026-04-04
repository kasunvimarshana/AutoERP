<?php
namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Contracts\ConsumeValuationLayersServiceInterface;
use Modules\Inventory\Application\DTOs\ConsumeValuationLayersData;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryValuationLayerRepositoryInterface;
use Modules\Inventory\Domain\ValueObjects\ValuationMethod;

class ConsumeValuationLayersService implements ConsumeValuationLayersServiceInterface
{
    private const FLOAT_TOLERANCE = 0.0001;
    public function __construct(
        private readonly InventoryValuationLayerRepositoryInterface $repository
    ) {}

    public function execute(ConsumeValuationLayersData $data): float
    {
        return match ($data->valuationMethod) {
            ValuationMethod::FIFO     => $this->consumeOrdered($data, 'asc'),
            ValuationMethod::LIFO     => $this->consumeOrdered($data, 'desc'),
            ValuationMethod::AVERAGE  => $this->consumeWeightedAverage($data),
            ValuationMethod::STANDARD => $this->consumeOrdered($data, 'asc'),
            ValuationMethod::SPECIFIC => $this->consumeOrdered($data, 'asc'),
            default => throw new \DomainException("Unsupported valuation method: {$data->valuationMethod}"),
        };
    }

    /**
     * Consume layers in receipt_date order (asc = FIFO, desc = LIFO).
     */
    private function consumeOrdered(ConsumeValuationLayersData $data, string $direction): float
    {
        $layers = $this->repository->findByProductOrdered(
            $data->productId,
            $data->warehouseId,
            $direction
        );

        $remaining = $data->quantity;
        $totalCost = 0.0;

        foreach ($layers as $layer) {
            if ($remaining <= 0) {
                break;
            }

            $consumed = min($remaining, $layer->remainingQuantity);
            $totalCost += $consumed * $layer->unitCost;
            $remaining -= $consumed;

            $layer->remainingQuantity -= $consumed;
            $layer->totalCost = $layer->remainingQuantity * $layer->unitCost;

            $this->repository->save($layer);
        }

        if ($remaining > self::FLOAT_TOLERANCE) {
            throw new \DomainException(
                "Insufficient inventory layers to consume {$data->quantity} units "
                . "(shortfall: {$remaining})."
            );
        }

        return $totalCost;
    }

    /**
     * Weighted-average consumption: consume at the average unit cost across all remaining layers.
     */
    private function consumeWeightedAverage(ConsumeValuationLayersData $data): float
    {
        $layers = $this->repository->findByProductOrdered(
            $data->productId,
            $data->warehouseId,
            'asc'
        );

        $totalQty   = array_sum(array_map(fn($l) => $l->remainingQuantity, $layers));
        $totalValue = array_sum(array_map(fn($l) => $l->remainingQuantity * $l->unitCost, $layers));
        $avgCost    = $totalQty > 0 ? $totalValue / $totalQty : 0.0;

        if ($data->quantity > $totalQty + self::FLOAT_TOLERANCE) {
            throw new \DomainException(
                "Insufficient inventory layers to consume {$data->quantity} units "
                . "(available: {$totalQty})."
            );
        }

        // Reduce layers proportionally and persist changes
        $remaining = $data->quantity;
        foreach ($layers as $layer) {
            if ($remaining <= 0) {
                break;
            }

            $consume = min($remaining, $layer->remainingQuantity);
            $layer->remainingQuantity -= $consume;
            $layer->totalCost = $layer->remainingQuantity * $layer->unitCost;
            $remaining -= $consume;

            $this->repository->save($layer);
        }

        return $data->quantity * $avgCost;
    }
}
