<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts;

interface UomConversionServiceContract
{
    public function toBaseQuantity(
        int $tenantId,
        ?int $itemId,
        int $fromUomId,
        int $toUomId,
        float $quantity
    ): float;
}
