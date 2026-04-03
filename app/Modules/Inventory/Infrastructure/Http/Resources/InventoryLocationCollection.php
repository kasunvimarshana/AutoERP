<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class InventoryLocationCollection extends ResourceCollection
{
    public $collects = InventoryLocationResource::class;
}
