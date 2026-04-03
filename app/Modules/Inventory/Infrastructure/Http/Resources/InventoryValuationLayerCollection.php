<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class InventoryValuationLayerCollection extends ResourceCollection
{
    public $collects = InventoryValuationLayerResource::class;
}
