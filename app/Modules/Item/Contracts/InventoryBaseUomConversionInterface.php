<?php

declare(strict_types=1);

namespace Modules\Item\Contracts;

use Modules\Item\Data\InventoryBaseUomConversionData;
use Modules\Item\Models\Item;

interface InventoryBaseUomConversionInterface
{
    public function convert(Item $item, int $newBaseUomId, string $conversionFactor): void;

    public function preview(Item $item, string $conversionFactor): InventoryBaseUomConversionData;
}
