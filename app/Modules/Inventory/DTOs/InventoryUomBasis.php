<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Inventory\DTOs;

final readonly class InventoryUomBasis
{
    public function __construct(
        public string $enteredQuantity,
        public string $enteredUnitCost,
        public ?int $enteredUomId,
        public string $baseQuantity,
        public string $baseUnitCost,
        public string $conversionFactor,
        public ?int $baseUomId,
    ) {}
}
