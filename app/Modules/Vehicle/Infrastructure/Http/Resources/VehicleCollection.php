<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Vehicle\Infrastructure\Http\Resources\VehicleResource;

class VehicleCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => VehicleResource::collection($this->collection),
        ];
    }
}
