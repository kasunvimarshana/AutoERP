<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class InventoryLevelCollection extends ResourceCollection
{
    public $collects = InventoryLevelResource::class;
}
