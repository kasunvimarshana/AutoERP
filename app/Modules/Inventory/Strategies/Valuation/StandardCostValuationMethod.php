<?php

declare(strict_types=1);

namespace Modules\Inventory\Strategies\Valuation;

use InvalidArgumentException;
use Modules\Inventory\Enums\ValuationMethod;
use Modules\Inventory\Models\InventoryMovement;

final class StandardCostValuationMethod extends AbstractLayerValuationMethod
{
    protected function method(): ValuationMethod
    {
        return ValuationMethod::Standard;
    }

    protected function receiptUnitCost(InventoryMovement $movement): string
    {
        $movement->loadMissing('item');
        $metadata = $movement->item?->metadata;
        $configured = is_array($metadata)
            ? data_get($metadata, 'inventory.standard_cost', $metadata['standard_cost'] ?? null)
            : null;
        $cost = $this->math->normalize(is_string($configured) ? $configured : (string) $movement->unit_cost);
        if ($this->math->compare($cost, '0.000000') <= 0) {
            throw new InvalidArgumentException('Standard-cost inventory requires a positive configured standard cost.');
        }

        return $cost;
    }
}
