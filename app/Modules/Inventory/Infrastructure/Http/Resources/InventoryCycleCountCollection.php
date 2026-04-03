<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class InventoryCycleCountCollection extends ResourceCollection
{
    public $collects = InventoryCycleCountResource::class;
}
