<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Strategies\Valuation;

use Modules\Inventory\Domain\Entities\InventoryCostLayer;
use Modules\Inventory\Domain\Exceptions\InsufficientAvailableStockException;

/**
 * Shared helper for strategies that consume cost layers sequentially.
 */
trait ConsumesLayersSequentially
{
    /**
     * Walk through the layers in the provided order, deducting the required
     * quantity one layer at a time.
     *
     * @param  InventoryCostLayer[]  $orderedLayers
     * @return InventoryCostLayer[] Only the layers that were actually touched
     */
    private function consumeSequential(string $quantityNeeded, array $orderedLayers): array
    {
        $remaining = $quantityNeeded;
        $touched = [];

        foreach ($orderedLayers as $layer) {
            if (bccomp($remaining, '0.000000', 6) <= 0) {
                break;
            }

            $available = $layer->getQuantityRemaining();

            if (bccomp($available, '0.000000', 6) <= 0) {
                continue;
            }

            $consume = bccomp($remaining, $available, 6) >= 0 ? $available : $remaining;
            $layer->deduct($consume);
            $remaining = bcsub($remaining, $consume, 6);
            $touched[] = $layer;
        }

        if (bccomp($remaining, '0.000000', 6) > 0) {
            throw new InsufficientAvailableStockException(
                sprintf(
                    'Insufficient stock: needed %s more but no open layers remain.',
                    $remaining,
                ),
            );
        }

        return $touched;
    }
}
