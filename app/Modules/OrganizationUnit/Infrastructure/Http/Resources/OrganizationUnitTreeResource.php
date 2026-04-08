<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationUnitTreeResource extends JsonResource
{
    public function toArray($request)
    {
        // $resource = $this->resource;
        return $this->resource->map(fn ($unit) => [
            'id' => $unit->getId(),
            'name' => $unit->getName()->value(),
            'code' => $unit->getCode()?->value(),
            'children' => OrganizationUnitTreeResource::collection($unit->getChildren()),
        ]);
    }
}
