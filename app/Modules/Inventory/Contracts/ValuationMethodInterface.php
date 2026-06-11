<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

use Modules\Inventory\DTOs\ValuationResultData;
use Modules\Inventory\Models\InventoryMovement;

interface ValuationMethodInterface
{
    public function receive(InventoryMovement $movement): ValuationResultData;

    public function issue(InventoryMovement $movement, string $quantity): ValuationResultData;

    public function reverse(InventoryMovement $movement, InventoryMovement $reversal): ValuationResultData;

    public function recalculate(InventoryMovement $movement): ValuationResultData;

    public function preview(InventoryMovement $movement, string $quantity): ValuationResultData;
}
