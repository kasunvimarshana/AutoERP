<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class WarehouseLocationCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => \Modules\Warehouse\Infrastructure\Http\Resources\WarehouseLocationResource::collection($this->collection),
        ];
    }
}
