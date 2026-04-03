<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class InventoryBatchCollection extends ResourceCollection
{
    public $collects = InventoryBatchResource::class;
}
