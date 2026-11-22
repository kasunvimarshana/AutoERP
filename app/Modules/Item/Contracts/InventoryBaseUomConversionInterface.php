<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Item\Contracts;

use Modules\Item\Data\InventoryBaseUomConversionData;
use Modules\Item\Models\Item;

interface InventoryBaseUomConversionInterface
{
    public function convert(Item $item, int $newBaseUomId, string $conversionFactor): void;

    public function preview(Item $item, string $conversionFactor): InventoryBaseUomConversionData;
}
