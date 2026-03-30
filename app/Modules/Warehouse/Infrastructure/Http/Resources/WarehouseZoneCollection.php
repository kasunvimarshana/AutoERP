<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class WarehouseZoneCollection extends ResourceCollection
{
    public $collects = WarehouseZoneResource::class;
}
