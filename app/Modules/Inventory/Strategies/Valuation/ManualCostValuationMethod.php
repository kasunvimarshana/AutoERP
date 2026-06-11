<?php

declare(strict_types=1);

namespace Modules\Inventory\Strategies\Valuation;

use InvalidArgumentException;
use Modules\Inventory\Enums\ValuationMethod;
use Modules\Inventory\Models\InventoryMovement;

final class ManualCostValuationMethod extends AbstractLayerValuationMethod
{
    protected function method(): ValuationMethod
    {
        return ValuationMethod::Manual;
    }

    protected function receiptUnitCost(InventoryMovement $movement): string
    {
        $cost = parent::receiptUnitCost($movement);
        if ($this->math->compare($cost, '0.000000') <= 0) {
            throw new InvalidArgumentException('Manual-cost inventory receipts require a positive unit cost.');
        }

        return $cost;
    }
}
