<?php

declare(strict_types=1);

namespace Modules\Location\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LocationTreeResource extends JsonResource
{
    public function toArray($request)
    {
        return $this->resource->map(fn ($location) => [
            'id'       => $location->getId(),
            'name'     => $location->getName()->value(),
            'type'     => $location->getType(),
            'code'     => $location->getCode()?->value(),
            'children' => LocationTreeResource::collection($location->getChildren()),
        ]);
    }
}
