<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class WarehouseCollection extends ResourceCollection
{
    public $collects = WarehouseResource::class;
}
